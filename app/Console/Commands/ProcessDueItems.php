<?php
namespace App\Console\Commands;

use App\Events\DomainRealtimeEvent;
use App\Models\{Conversation,Memory,Message};
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessDueItems extends Command
{
    protected $signature='143:process-due';
    protected $description='Process due Time Capsules, scheduled love, surprises, reminders, expiries and calendar reminders.';

    public function handle(NotificationService $notifications): int
    {
        Memory::where('type','time_capsule')->where('is_locked',true)->whereNotNull('reveal_at')->where('reveal_at','<=',now())
            ->chunkById(100,function($rows) use ($notifications) {
                foreach($rows as $m){
                    $m->update(['is_locked'=>false,'revealed_at'=>now()]);
                    event(new DomainRealtimeEvent('time_capsule.revealed',$m->toArray(),null,$m->couple_space_id));
                    $this->notifyPartner($notifications,$m->couple_space_id,$m->user_id ?? null,'time_capsule','Time Capsule revealed','A Time Capsule is ready to open.',['memory_id'=>$m->id]);
                }
            });

        DB::table('scheduled_messages')->whereNull('deleted_at')->whereIn('status',['scheduled','active'])->whereNotNull('scheduled_at')->where('scheduled_at','<=',now())
            ->orderBy('id')->chunkById(100,function($rows) use ($notifications) {
                foreach($rows as $row){
                    $conversation=Conversation::firstOrCreate(['couple_space_id'=>$row->couple_space_id,'type'=>'couple'],['created_by'=>$row->user_id,'metadata'=>[]]);
                    $data=json_decode($row->data??'{}',true)?:[];
                    $message=Message::create([
                        'conversation_id'=>$conversation->id,
                        'couple_space_id'=>$row->couple_space_id,
                        'sender_id'=>$row->user_id,
                        'type'=>$data['type']??'text',
                        'body'=>$row->body??($data['body']??null),
                        'payload'=>array_merge($data['payload']??[],['scheduled_message_id'=>$row->id]),
                    ]);
                    DB::table('scheduled_messages')->where('id',$row->id)->update(['status'=>'delivered','completed_at'=>now(),'metadata'=>json_encode(['message_id'=>$message->id]),'updated_at'=>now()]);
                    event(new DomainRealtimeEvent('chat.message.created',$message->toArray(),null,$row->couple_space_id));
                    event(new DomainRealtimeEvent('scheduled_message.delivered',['scheduled_message_id'=>$row->id,'message_id'=>$message->id],null,$row->couple_space_id));
                    $this->notifyPartner($notifications,$row->couple_space_id,$row->user_id,'scheduled_love','A message from your partner',$message->body?:'A scheduled love message arrived.',['message_id'=>$message->id]);
                }
            });

        DB::table('surprises')->whereNull('deleted_at')->whereIn('status',['scheduled','active'])->whereNotNull('scheduled_at')->where('scheduled_at','<=',now())
            ->orderBy('id')->chunkById(100,function($rows) use ($notifications) {
                foreach($rows as $row){
                    DB::table('surprises')->where('id',$row->id)->update(['status'=>'revealed','completed_at'=>now(),'updated_at'=>now()]);
                    event(new DomainRealtimeEvent('surprise.unlocked',(array)$row,null,$row->couple_space_id));
                    $this->notifyPartner($notifications,$row->couple_space_id,$row->user_id,'surprise','A surprise is ready','Your partner left a surprise for you.',['surprise_id'=>$row->id]);
                }
            });

        foreach(['reminders'=>'reminder','calendar_events'=>'calendar'] as $table=>$type){
            DB::table($table)->whereNull('deleted_at')->whereIn('status',['scheduled','active','planned'])->whereNotNull('scheduled_at')->where('scheduled_at','<=',now())
                ->orderBy('id')->chunkById(100,function($rows) use ($table,$type,$notifications) {
                    foreach($rows as $row){
                        DB::table($table)->where('id',$row->id)->update(['status'=>'triggered','completed_at'=>now(),'updated_at'=>now()]);
                        $event=$table==='calendar_events'?'calendar.reminder.due':'notification.created';
                        event(new DomainRealtimeEvent($event,(array)$row,null,$row->couple_space_id));
                        $title=$table==='calendar_events'?'143 calendar reminder':'143 reminder';
                        $notifications->notify($row->user_id,$type,$title,$row->body?:($row->title?:'You have a reminder.'),['resource_id'=>$row->id]);
                    }
                });
        }

        $expired=DB::table('location_shares')->where('status','active')->whereNotNull('expires_at')->where('expires_at','<=',now())->get();
        foreach($expired as $row){DB::table('location_shares')->where('id',$row->id)->update(['status'=>'expired','ended_at'=>now(),'updated_at'=>now()]);event(new DomainRealtimeEvent('location.share.ended',['share_id'=>$row->id,'reason'=>'expired'],null,$row->couple_space_id));}
        DB::table('device_link_intents')->where('status','pending')->whereNotNull('scheduled_at')->where('scheduled_at','<=',now())->update(['status'=>'expired','completed_at'=>now(),'updated_at'=>now()]);
        return self::SUCCESS;
    }

    private function notifyPartner(NotificationService $notifications, ?string $coupleSpaceId, ?string $senderId, string $type, string $title, string $body, array $data=[]): void
    {
        if(!$coupleSpaceId)return;
        $partner=DB::table('couple_members')->where('couple_space_id',$coupleSpaceId)->when($senderId,fn($q)=>$q->where('user_id','!=',$senderId))->value('user_id');
        if($partner)$notifications->notify($partner,$type,$title,$body,$data);
    }
}
