<?php
namespace App\Services;
use App\Models\{LegalDocument,UserConsent};
use App\Support\ApiResponse;
use Illuminate\Http\Request;
class SystemService
{
 public function handle(string $op,Request $r){return match($op){
 'get_bootstrap'=>$this->bootstrap($r),'get_legal_documents'=>ApiResponse::ok(LegalDocument::where('is_active',true)->get()),'get_legal_documents_type'=>ApiResponse::ok(LegalDocument::where('type',$r->route('type'))->where('is_active',true)->latest('effective_at')->firstOrFail()),
 'get_consents'=>ApiResponse::ok($r->user()?UserConsent::where('user_id',$r->user()->id)->with('document')->get():[]),'post_consents'=>$this->consent($r),'get_health'=>ApiResponse::ok(['status'=>'ok','service'=>'143-api']),'get_server_time'=>ApiResponse::ok(['utc'=>now()->utc()->toIso8601String(),'unix'=>now()->timestamp]),
 'get_sync_state'=>ApiResponse::ok(['server_time'=>now()->toIso8601String(),'cursor'=>base64_encode((string)microtime(true))]),'post_sync_reconcile'=>ApiResponse::ok(['accepted'=>true,'server_time'=>now()->toIso8601String()]),default=>ApiResponse::problem('Unsupported system operation',$op,501,'not_implemented')};}
 private function bootstrap(Request $r){return ApiResponse::ok(['api_version'=>'v1','mobile_feature_baseline'=>514,'modules'=>36,'http_operations'=>351,'route_paths'=>265,'realtime_events'=>53,'legal_versions'=>LegalDocument::where('is_active',true)->pluck('version','type'),'supported_locales'=>['en'],'server_time'=>now()->toIso8601String()]);}
 private function consent(Request $r){$d=$r->validate(['legal_document_id'=>'required|uuid|exists:legal_documents,id','accepted'=>'required|boolean','metadata'=>'nullable|array']);$x=UserConsent::updateOrCreate(['user_id'=>$r->user()->id,'legal_document_id'=>$d['legal_document_id']],['accepted_at'=>$d['accepted']?now():null,'revoked_at'=>$d['accepted']?null:now(),'metadata'=>$d['metadata']??[]]);return ApiResponse::ok($x);}
}
