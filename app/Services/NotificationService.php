<?php
namespace App\Services;
use App\Jobs\SendPushNotification;
use App\Events\DomainRealtimeEvent;
use App\Models\{Notification,PushDevice};
use App\Support\ApiResponse;
use Illuminate\Http\Request;
class NotificationService
{

 public function notify(string $userId,string $type,string $title,string $body,array $data=[]): Notification
 {
   $n=Notification::create(['user_id'=>$userId,'type'=>$type,'title'=>$title,'body'=>$body,'data'=>$data]);
   event(new DomainRealtimeEvent('notification.created',$n->toArray(),$userId,null));
   SendPushNotification::dispatch($userId,$title,$body,array_merge($data,['notification_id'=>$n->id,'type'=>$type]));
   return $n;
 }

 public function handle(string $op,Request $r){return match($op){
 'get_notifications'=>ApiResponse::ok(Notification::where('user_id',$r->user()->id)->latest()->cursorPaginate(min((int)$r->input('limit',30),100))),
 'get_notifications_unread_count'=>ApiResponse::ok(['count'=>Notification::where('user_id',$r->user()->id)->whereNull('read_at')->count()]),
 'post_notifications_notificationid_read'=>$this->read($r),'post_notifications_read_all'=>$this->readAll($r),
 'get_notification_preferences'=>ApiResponse::ok($r->user()->preferences['notifications']??[]),'put_notification_preferences'=>$this->prefs($r),
 'post_push_devices'=>$this->device($r),'delete_push_devices_deviceid'=>$this->removeDevice($r),default=>ApiResponse::problem('Unsupported notification operation',$op,501,'not_implemented')};}
 private function read(Request $r){$n=Notification::where('id',$r->route('notificationId'))->where('user_id',$r->user()->id)->firstOrFail();$n->update(['read_at'=>now()]);return ApiResponse::ok($n);}
 private function readAll(Request $r){Notification::where('user_id',$r->user()->id)->whereNull('read_at')->update(['read_at'=>now()]);return ApiResponse::ok(['read_all'=>true]);}
 private function prefs(Request $r){$d=$r->validate(['preferences'=>'required|array']);$p=$r->user()->preferences?:[];$p['notifications']=$d['preferences'];$r->user()->update(['preferences'=>$p]);return ApiResponse::ok($p['notifications']);}
 private function device(Request $r){$d=$r->validate(['token'=>'required|string|max:4096','platform'=>'required|in:android,ios,web','device_name'=>'nullable|string|max:100','metadata'=>'nullable|array']);$x=PushDevice::updateOrCreate(['token_hash'=>hash('sha256',$d['token'])],['user_id'=>$r->user()->id,'token'=>$d['token'],'platform'=>$d['platform'],'device_name'=>$d['device_name']??null,'metadata'=>$d['metadata']??[],'last_seen_at'=>now()]);return ApiResponse::created($x->makeHidden('token'));}
 private function removeDevice(Request $r){PushDevice::where('id',$r->route('deviceId'))->where('user_id',$r->user()->id)->delete();return ApiResponse::ok(['deleted'=>true]);}
}
