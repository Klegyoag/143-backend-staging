<?php
namespace App\Services;

use App\Models\{BillingReceipt,Subscription};
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BillingService
{
    public function handle(string $op,Request $r){return match($op){
        'get_subscription_plans'=>ApiResponse::ok(config('143.plans')),
        'get_subscription'=>ApiResponse::ok($r->user()?Subscription::where('user_id',$r->user()->id)->latest()->first():null),
        'post_subscription'=>$this->create($r),
        'post_subscription_cancel'=>$this->cancel($r),
        'post_subscription_renew'=>$this->renew($r),
        'post_subscription_restore'=>$this->restore($r),
        'post_subscription_sync'=>$this->sync($r),
        'get_billing_history'=>ApiResponse::ok(BillingReceipt::where('user_id',$r->user()->id)->latest()->get()),
        'get_billing_receipts_receiptid'=>ApiResponse::ok(BillingReceipt::where('id',$r->route('receiptId'))->where('user_id',$r->user()->id)->firstOrFail()),
        'post_webhooks_paypal'=>$this->webhook($r),
        default=>ApiResponse::problem('Unsupported billing operation',$op,501,'not_implemented')};}

    private function base(): string{return env('PAYPAL_ENV','sandbox')==='live'?'https://api-m.paypal.com':'https://api-m.sandbox.paypal.com';}
    private function configured():bool{return filled(env('PAYPAL_CLIENT_ID'))&&filled(env('PAYPAL_CLIENT_SECRET'));}
    private function token():?string{
        if(!$this->configured())return null;
        $resp=Http::asForm()->withBasicAuth(env('PAYPAL_CLIENT_ID'),env('PAYPAL_CLIENT_SECRET'))->post($this->base().'/v1/oauth2/token',['grant_type'=>'client_credentials']);
        return $resp->successful()?$resp->json('access_token'):null;
    }
    private function create(Request $r){
        $d=$r->validate(['plan_key'=>'required|string']);$plan=config('143.plans.'.$d['plan_key']);
        if(!$plan)return ApiResponse::problem('Unknown plan','The selected plan does not exist.',422,'invalid_plan');
        if(!$this->configured()||blank($plan['paypal_plan_id']??null))return ApiResponse::problem('PayPal not configured','Add PayPal credentials and the selected PayPal plan ID in Render.',503,'integration_not_configured');
        $token=$this->token();if(!$token)return ApiResponse::problem('PayPal unavailable','Could not authenticate with PayPal.',502,'paypal_auth_failed');
        $payload=['plan_id'=>$plan['paypal_plan_id'],'custom_id'=>$r->user()->id,'application_context'=>['brand_name'=>'143','user_action'=>'SUBSCRIBE','return_url'=>env('PAYPAL_RETURN_URL','143://billing/success'),'cancel_url'=>env('PAYPAL_CANCEL_URL','143://billing/cancel')]];
        $resp=Http::withToken($token)->acceptJson()->post($this->base().'/v1/billing/subscriptions',$payload);
        if(!$resp->successful())return ApiResponse::problem('PayPal checkout failed','PayPal could not create the subscription.',502,'paypal_create_failed',['provider_status'=>$resp->status()]);
        $j=$resp->json();$sub=Subscription::create(['user_id'=>$r->user()->id,'plan_key'=>$d['plan_key'],'provider'=>'paypal','provider_subscription_id'=>$j['id']??null,'status'=>strtolower($j['status']??'approval_pending'),'metadata'=>['provider'=>$j]]);
        $approve=collect($j['links']??[])->firstWhere('rel','approve')['href']??null;
        return ApiResponse::created(['subscription'=>$sub,'approval_url'=>$approve]);
    }
    private function current(Request $r):Subscription{return Subscription::where('user_id',$r->user()->id)->latest()->firstOrFail();}
    private function cancel(Request $r){$s=$this->current($r);if(!$s->provider_subscription_id)return ApiResponse::problem('Provider subscription missing','No PayPal subscription id is attached.',409,'provider_subscription_missing');$token=$this->token();$resp=Http::withToken($token)->post($this->base().'/v1/billing/subscriptions/'.$s->provider_subscription_id.'/cancel',['reason'=>'Cancelled by user']);if(!$resp->successful()&&$resp->status()!==204)return ApiResponse::problem('Cancellation failed','PayPal rejected the cancellation request.',502,'paypal_cancel_failed');$s->update(['status'=>'cancelled','cancelled_at'=>now()]);return ApiResponse::ok($s);}
    private function renew(Request $r){$s=$this->current($r);if($s->status==='active')return ApiResponse::ok($s);return ApiResponse::problem('New approval required','A cancelled or expired PayPal subscription must be created again so the user can approve it.',409,'new_subscription_required',['plan_key'=>$s->plan_key]);}
    private function restore(Request $r){$d=$r->validate(['provider_subscription_id'=>'required|string|max:255']);$token=$this->token();if(!$token)return ApiResponse::problem('PayPal not configured','Add PayPal credentials.',503,'integration_not_configured');$resp=Http::withToken($token)->get($this->base().'/v1/billing/subscriptions/'.$d['provider_subscription_id']);if(!$resp->successful())return ApiResponse::problem('Subscription not found','PayPal could not verify this subscription.',404,'paypal_subscription_not_found');$j=$resp->json();if(($j['custom_id']??null)!==$r->user()->id)return ApiResponse::problem('Subscription mismatch','This subscription is not linked to the current 143 account.',403,'subscription_owner_mismatch');$s=Subscription::updateOrCreate(['provider_subscription_id'=>$d['provider_subscription_id']],['user_id'=>$r->user()->id,'plan_key'=>$this->planKeyFromProvider($j['plan_id']??null),'provider'=>'paypal','status'=>strtolower($j['status']??'unknown'),'metadata'=>['provider'=>$j]]);return ApiResponse::ok($s);}
    private function sync(Request $r){$s=$this->current($r);if(!$s->provider_subscription_id)return ApiResponse::ok(['synced'=>false,'subscription'=>$s]);$token=$this->token();if(!$token)return ApiResponse::problem('PayPal not configured','Add PayPal credentials.',503,'integration_not_configured');$resp=Http::withToken($token)->get($this->base().'/v1/billing/subscriptions/'.$s->provider_subscription_id);if(!$resp->successful())return ApiResponse::problem('PayPal sync failed','PayPal subscription lookup failed.',502,'paypal_sync_failed');$j=$resp->json();$s->update(['status'=>strtolower($j['status']??$s->status),'current_period_end'=>data_get($j,'billing_info.next_billing_time'),'metadata'=>['provider'=>$j]]);return ApiResponse::ok(['synced'=>true,'subscription'=>$s]);}
    private function webhook(Request $r){
        if(!$this->configured()||blank(env('PAYPAL_WEBHOOK_ID')))return ApiResponse::problem('PayPal webhook not configured','Set PAYPAL_WEBHOOK_ID and PayPal credentials.',503,'integration_not_configured');
        $token=$this->token();$verify=Http::withToken($token)->post($this->base().'/v1/notifications/verify-webhook-signature',['auth_algo'=>$r->header('PAYPAL-AUTH-ALGO'),'cert_url'=>$r->header('PAYPAL-CERT-URL'),'transmission_id'=>$r->header('PAYPAL-TRANSMISSION-ID'),'transmission_sig'=>$r->header('PAYPAL-TRANSMISSION-SIG'),'transmission_time'=>$r->header('PAYPAL-TRANSMISSION-TIME'),'webhook_id'=>env('PAYPAL_WEBHOOK_ID'),'webhook_event'=>$r->all()]);
        if(!$verify->successful()||$verify->json('verification_status')!=='SUCCESS')return ApiResponse::problem('Invalid webhook signature','PayPal webhook verification failed.',400,'invalid_webhook_signature');
        $event=$r->all();$id=data_get($event,'id');if(!$id)return ApiResponse::problem('Invalid webhook','Missing PayPal event ID.',400,'invalid_webhook');
        if(BillingReceipt::where('provider_event_id',$id)->exists())return ApiResponse::ok(['received'=>true,'duplicate'=>true]);
        $type=data_get($event,'event_type','unknown');$resource=data_get($event,'resource',[]);$providerId=data_get($resource,'id')?:data_get($resource,'billing_agreement_id')?:data_get($resource,'supplementary_data.related_ids.subscription_id');$s=$providerId?Subscription::where('provider_subscription_id',$providerId)->first():null;
        if($s){$map=['BILLING.SUBSCRIPTION.CREATED'=>'approval_pending','BILLING.SUBSCRIPTION.ACTIVATED'=>'active','BILLING.SUBSCRIPTION.UPDATED'=>'active','BILLING.SUBSCRIPTION.CANCELLED'=>'cancelled','BILLING.SUBSCRIPTION.SUSPENDED'=>'suspended','BILLING.SUBSCRIPTION.EXPIRED'=>'expired','BILLING.SUBSCRIPTION.PAYMENT.FAILED'=>'past_due'];if(isset($map[$type]))$s->update(['status'=>$map[$type],'cancelled_at'=>$map[$type]==='cancelled'?now():$s->cancelled_at]);BillingReceipt::create(['user_id'=>$s->user_id,'subscription_id'=>$s->id,'provider_event_id'=>$id,'currency'=>data_get($resource,'amount_with_breakdown.gross_amount.currency_code')?:data_get($resource,'amount.currency_code'),'amount'=>data_get($resource,'amount_with_breakdown.gross_amount.value')?:data_get($resource,'amount.value'),'payload'=>$event]);}
        return ApiResponse::ok(['received'=>true]);
    }
    private function planKeyFromProvider(?string $id):string{foreach(config('143.plans') as $key=>$plan)if(($plan['paypal_plan_id']??null)===$id)return $key;return 'unknown';}
}
