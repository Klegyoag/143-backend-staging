<?php
namespace App\Services;

use App\Events\DomainRealtimeEvent;
use App\Jobs\GenerateDataExport;
use App\Models\{CoupleMember,Media,Memory,User};
use App\Support\{ApiResponse,RequestContext};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Crypt,DB,Http,Log,Storage};
use Illuminate\Support\Str;

class ComplexDomainService
{
    public function __construct(private AuthService $auth) {}

    private array $supported = ['calls','calls.decline','calls.end','together-rooms','jars','albums','calendar.events','journeys','journey-sessions','reconnection','reconnection.sessions','linked-devices','linked-devices.link-intents','linked-devices.qr-sessions','integrations.spotify','exports'];
    public function supports(string $resource): bool { return in_array($resource,$this->supported,true); }

    public function handle(string $op,string $resource,Request $r)
    {
        return match(true){
            str_starts_with($resource,'calls')=>$this->calls($op,$r),
            $resource==='together-rooms'=>$this->together($op,$r),
            $resource==='jars'=>$this->jars($op,$r),
            $resource==='albums'=>$this->albums($op,$r),
            $resource==='calendar.events'=>$this->calendar($op,$r),
            in_array($resource,['journeys','journey-sessions'],true)=>$this->journeys($op,$r),
            in_array($resource,['reconnection','reconnection.sessions'],true)=>$this->reconnection($op,$r),
            in_array($resource,['linked-devices','linked-devices.link-intents','linked-devices.qr-sessions'],true)=>$this->linkedDevices($op,$r),
            $resource==='integrations.spotify'=>$this->spotify($op,$r),
            $resource==='exports'=>$this->exports($op,$r),
            default=>ApiResponse::problem('Unsupported complex domain operation',$op,501,'not_implemented'),
        };
    }

    private function calls(string $op,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        if($op==='get_calls_ice_servers')return ApiResponse::ok(config('143.ice_servers'));
        if($op==='get_calls_incoming'){
            if(!$space)return ApiResponse::ok([]);
            $calls=DB::table('calls')->where('couple_space_id',$space)->where('status','ringing')
                ->where('created_at','>=',now()->subMinutes(2))->whereNull('deleted_at')
                ->orderByDesc('created_at')->limit(5)->get()->map(fn($row)=>$this->decode($row))
                ->filter(fn($row)=>($row['data']['initiator_id']??null)!==$r->user()->id)->values();
            return ApiResponse::ok($calls);
        }
        if($op==='post_calls'){
            $d=$r->validate(['type'=>'required|in:audio,video']);
            if(!$space || !CoupleMember::where('couple_space_id',$space)->where('user_id','!=',$r->user()->id)->exists())
                return ApiResponse::problem('Partner not connected','Connect your person before starting a call.',409,'partner_not_connected');
            $id=(string)Str::uuid();
            $row=$this->row($r,['id'=>$id,'type'=>$d['type'],'status'=>'ringing','data'=>json_encode(['initiator_id'=>$r->user()->id,'signaling'=>['ice_candidates'=>[]]])]);
            DB::table('calls')->insert($row);
            $this->broadcastCall('call.incoming',['call_id'=>$id,'type'=>$d['type'],'from_user_id'=>$r->user()->id],$space);
            return ApiResponse::created($this->one('calls',$id,$r));
        }
        $id=$r->route('callId');
        if(!is_string($id)||!Str::isUuid($id))return ApiResponse::problem('Call not found','Call does not exist.',404,'not_found');
        $call=$this->one('calls',$id,$r);if(!$call)return ApiResponse::problem('Call not found','Call does not exist.',404,'not_found');
        if($r->method()==='GET')return ApiResponse::ok($call);
        if(in_array($op,['post_calls_callid_offer','post_calls_callid_answer','post_calls_callid_ice_candidates'],true))
            return $this->publishCallSignal($op,$r,$call,$space);
        $status=match(true){str_contains($op,'accept')=>'active',str_contains($op,'decline')=>'declined',str_contains($op,'end')=>'ended',default=>'active'};
        DB::table('calls')->where('id',$id)->update(['status'=>$status,'completed_at'=>in_array($status,['declined','ended'])?now():null,'updated_at'=>now()]);
        $event=match($status){'active'=>'call.accepted','declined'=>'call.declined','ended'=>'call.ended',default=>'call.updated'};
        $this->broadcastCall($event,['call_id'=>$id,'status'=>$status],$space);
        return ApiResponse::ok($this->one('calls',$id,$r));
    }

    private function publishCallSignal(string $op,Request $r,array $call,?string $space)
    {
        if(in_array($call['status'],['ended','declined'],true))
            return ApiResponse::problem('Call ended','This call is no longer active.',409,'call_ended');
        $kind=match($op){
            'post_calls_callid_offer'=>'offer',
            'post_calls_callid_answer'=>'answer',
            default=>'ice_candidate',
        };
        $data=$kind==='ice_candidate'
            ? $r->validate(['candidate'=>'required|string|max:4096','sdp_mid'=>'nullable|string|max:80','sdp_mline_index'=>'nullable|integer|min:0|max:100'])
            : $r->validate(['sdp'=>'required|string|max:100000','type'=>'nullable|in:offer,answer']);
        $signal=['from_user_id'=>$r->user()->id,...$data];
        DB::transaction(function()use($call,$kind,$signal){
            $row=DB::table('calls')->where('id',$call['id'])->lockForUpdate()->first();
            $payload=json_decode($row->data??'{}',true);
            if(!is_array($payload))$payload=[];
            $signaling=$payload['signaling']??[];
            if($kind==='ice_candidate'){
                $candidates=$signaling['ice_candidates']??[];
                $candidates[]=$signal;
                $signaling['ice_candidates']=array_slice($candidates,-96);
            }else $signaling[$kind]=$signal;
            $payload['signaling']=$signaling;
            DB::table('calls')->where('id',$call['id'])->update(['data'=>json_encode($payload),'updated_at'=>now()]);
        });
        $this->broadcastCall('call.'.$kind,['call_id'=>$call['id'],...$signal],$space);
        return ApiResponse::ok(['published'=>true]);
    }

    private function broadcastCall(string $event,array $payload,?string $space):void
    {
        try{event(new DomainRealtimeEvent($event,$payload,null,$space));}
        catch(\Throwable $error){
            // The phone also polls call state, so a missing websocket host must not break signaling.
            Log::warning('Call realtime delivery unavailable',['event'=>$event,'call_id'=>$payload['call_id']??null,'error'=>$error->getMessage()]);
        }
    }

    private function together(string $op,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        if($op==='post_together_rooms'){$id=(string)Str::uuid();DB::table('together_rooms')->insert($this->row($r,['id'=>$id,'type'=>'together','status'=>'active','data'=>json_encode(['playback'=>['state'=>'paused','position'=>0],'provider'=>null])]));return ApiResponse::created($this->one('together_rooms',$id,$r));}
        if($op==='get_together_rooms_current')return ApiResponse::ok($this->decode(DB::table('together_rooms')->where('couple_space_id',$space)->where('status','active')->whereNull('deleted_at')->latest()->first()));
        $roomId=$r->route('roomId');$room=$this->one('together_rooms',$roomId,$r);if(!$room)return ApiResponse::problem('Room not found','Together Room does not exist.',404,'not_found');
        if(str_contains($op,'_join')){DB::table('together_room_members')->updateOrInsert(['user_id'=>$r->user()->id,'parent_id'=>$roomId,'type'=>'member'],$this->row($r,['id'=>(string)Str::uuid(),'parent_id'=>$roomId,'type'=>'member','status'=>'joined']));event(new DomainRealtimeEvent('together.member.joined',['room_id'=>$roomId,'user_id'=>$r->user()->id],null,$space));return ApiResponse::ok(['joined'=>true]);}
        if(str_contains($op,'_leave')){DB::table('together_room_members')->where('parent_id',$roomId)->where('user_id',$r->user()->id)->update(['status'=>'left','completed_at'=>now(),'updated_at'=>now()]);event(new DomainRealtimeEvent('together.member.left',['room_id'=>$roomId,'user_id'=>$r->user()->id],null,$space));return ApiResponse::ok(['left'=>true]);}
        if(str_contains($op,'_state')){
            if($r->method()==='GET')return ApiResponse::ok($room);
            $old=$room['data']??[];$d=$r->validate(['state'=>'required|array']);$merged=array_replace_recursive($old,$d['state']);DB::table('together_rooms')->where('id',$roomId)->update(['data'=>json_encode($merged),'updated_at'=>now()]);event(new DomainRealtimeEvent('together.state.updated',['room_id'=>$roomId,'state'=>$merged],null,$space));return ApiResponse::ok($merged);
        }
        if(str_contains($op,'_queue')){
            if($r->method()==='GET')return ApiResponse::ok(DB::table('together_queue_items')->where('parent_id',$roomId)->whereNull('deleted_at')->orderBy('occurred_at')->get()->map(fn($x)=>$this->decode($x)));
            if($r->method()==='DELETE'){$item=$r->route('itemId');DB::table('together_queue_items')->where('id',$item)->where('parent_id',$roomId)->update(['deleted_at'=>now(),'updated_at'=>now()]);event(new DomainRealtimeEvent('together.queue.updated',['room_id'=>$roomId],null,$space));return ApiResponse::ok(['deleted'=>true]);}
            if(str_contains($op,'reorder')){$d=$r->validate(['item_ids'=>'required|array']);foreach($d['item_ids'] as $i=>$id)DB::table('together_queue_items')->where('id',$id)->where('parent_id',$roomId)->update(['occurred_at'=>now()->addMilliseconds($i),'updated_at'=>now()]);event(new DomainRealtimeEvent('together.queue.updated',['room_id'=>$roomId],null,$space));return ApiResponse::ok(['reordered'=>true]);}
            $d=$r->validate(['provider'=>'required|string|max:30','media_id'=>'required|string|max:255','title'=>'nullable|string|max:255','metadata'=>'nullable|array']);$id=(string)Str::uuid();DB::table('together_queue_items')->insert($this->row($r,['id'=>$id,'parent_id'=>$roomId,'type'=>$d['provider'],'title'=>$d['title']??null,'data'=>json_encode($d),'occurred_at'=>now()]));event(new DomainRealtimeEvent('together.queue.updated',['room_id'=>$roomId],null,$space));return ApiResponse::created($this->decode(DB::table('together_queue_items')->where('id',$id)->first()));
        }
        if(str_contains($op,'resync')){event(new DomainRealtimeEvent('together.resync.requested',['room_id'=>$roomId,'requested_by'=>$r->user()->id],null,$space));return ApiResponse::ok(['requested'=>true,'state'=>$room]);}
        return ApiResponse::problem('Unsupported Together Room operation',$op,501,'not_implemented');
    }

    private function jars(string $op,Request $r)
    {
        $jarId=$r->route('jarId');
        if(!$jarId)return $this->simpleCrud('jars',$r);
        $jar=$this->one('jars',$jarId,$r);if(!$jar)return ApiResponse::problem('Jar not found','Jar does not exist.',404,'not_found');
        if(str_contains($op,'entries')){
            $entryId=$r->route('entryId');
            if($r->method()==='GET')return ApiResponse::ok(DB::table('jar_entries')->where('parent_id',$jarId)->whereNull('deleted_at')->latest()->get()->map(fn($x)=>$this->decode($x)));
            if($r->method()==='POST'){$d=$r->validate(['body'=>'required|string|max:5000','type'=>'nullable|string|max:80']);$id=(string)Str::uuid();DB::table('jar_entries')->insert($this->row($r,['id'=>$id,'parent_id'=>$jarId,'type'=>$d['type']??'entry','body'=>$d['body']]));return ApiResponse::created($this->decode(DB::table('jar_entries')->where('id',$id)->first()));}
            if(!$entryId)return ApiResponse::problem('Entry required','Entry id is required.',422,'missing_identifier');
            if($r->method()==='DELETE'){DB::table('jar_entries')->where('id',$entryId)->where('parent_id',$jarId)->update(['deleted_at'=>now(),'updated_at'=>now()]);return ApiResponse::ok(['deleted'=>true]);}
            $d=$r->validate(['body'=>'nullable|string|max:5000','type'=>'nullable|string|max:80']);DB::table('jar_entries')->where('id',$entryId)->where('parent_id',$jarId)->update(['body'=>$d['body']??null,'type'=>$d['type']??'entry','updated_at'=>now()]);return ApiResponse::ok($this->decode(DB::table('jar_entries')->where('id',$entryId)->first()));
        }
        if(str_contains($op,'random_pick')){$entry=DB::table('jar_entries')->where('parent_id',$jarId)->whereNull('deleted_at')->inRandomOrder()->first();return ApiResponse::ok($this->decode($entry));}
        return $this->simpleCrud('jars',$r);
    }

    private function albums(string $op,Request $r)
    {
        $albumId=$r->route('albumId');if(!$albumId)return $this->simpleCrud('albums',$r);
        if(str_contains($op,'photos')){
            $photoId=$r->route('photoId');
            if($r->method()==='POST'){$d=$r->validate(['media_id'=>'required|uuid','caption'=>'nullable|string|max:2000']);Media::where('id',$d['media_id'])->where('couple_space_id',RequestContext::coupleSpaceId($r))->firstOrFail();$id=(string)Str::uuid();DB::table('album_items')->insert($this->row($r,['id'=>$id,'parent_id'=>$albumId,'type'=>'photo','body'=>$d['caption']??null,'data'=>json_encode(['media_id'=>$d['media_id']])]));return ApiResponse::created($this->decode(DB::table('album_items')->where('id',$id)->first()));}
            if($r->method()==='DELETE'){DB::table('album_items')->where('id',$photoId)->where('parent_id',$albumId)->update(['deleted_at'=>now(),'updated_at'=>now()]);return ApiResponse::ok(['deleted'=>true]);}
            $d=$r->validate(['caption'=>'nullable|string|max:2000','favorite'=>'nullable|boolean']);$row=$this->decode(DB::table('album_items')->where('id',$photoId)->where('parent_id',$albumId)->first());$data=array_replace_recursive($row['data']??[],$d);DB::table('album_items')->where('id',$photoId)->update(['body'=>$d['caption']??$row['body'],'data'=>json_encode($data),'updated_at'=>now()]);return ApiResponse::ok($this->decode(DB::table('album_items')->where('id',$photoId)->first()));
        }
        if(str_contains($op,'export'))return ApiResponse::ok(['export_requested'=>true,'album_id'=>$albumId]);
        if(str_contains($op,'lock')){DB::table('albums')->where('id',$albumId)->update(['status'=>'locked','updated_at'=>now()]);return ApiResponse::ok($this->one('albums',$albumId,$r));}
        return $this->simpleCrud('albums',$r);
    }

    private function calendar(string $op,Request $r)
    {
        $eventId=$r->route('eventId');if(!$eventId)return $this->simpleCrud('calendar_events',$r);
        if(str_contains($op,'comments')){
            if($r->method()==='GET')return ApiResponse::ok(DB::table('calendar_comments')->where('parent_id',$eventId)->whereNull('deleted_at')->oldest()->get()->map(fn($x)=>$this->decode($x)));
            $d=$r->validate(['body'=>'required|string|max:5000']);$id=(string)Str::uuid();DB::table('calendar_comments')->insert($this->row($r,['id'=>$id,'parent_id'=>$eventId,'type'=>'comment','body'=>$d['body']]));return ApiResponse::created($this->decode(DB::table('calendar_comments')->where('id',$id)->first()));
        }
        if(str_contains($op,'photos')){
            $mediaId=$r->route('mediaId')??$r->input('media_id');if(!$mediaId)return ApiResponse::problem('Media required','media_id is required.',422,'missing_media');
            if($r->method()==='DELETE'){DB::table('feature_records')->where('resource','calendar.event.photo')->where('parent_id',$eventId)->whereRaw("data->>'media_id' = ?",[$mediaId])->update(['deleted_at'=>now(),'updated_at'=>now()]);return ApiResponse::ok(['deleted'=>true]);}
            Media::where('id',$mediaId)->where('couple_space_id',RequestContext::coupleSpaceId($r))->firstOrFail();$id=(string)Str::uuid();DB::table('feature_records')->insert(['id'=>$id,'resource'=>'calendar.event.photo','user_id'=>$r->user()->id,'couple_space_id'=>RequestContext::coupleSpaceId($r),'parent_id'=>$eventId,'status'=>'active','data'=>json_encode(['media_id'=>$mediaId]),'metadata'=>json_encode([]),'occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);return ApiResponse::created(['id'=>$id,'media_id'=>$mediaId]);
        }
        if(str_contains($op,'convert_to_memory')){$event=$this->one('calendar_events',$eventId,$r);$m=Memory::create(['couple_space_id'=>RequestContext::coupleSpaceId($r),'user_id'=>$r->user()->id,'type'=>'milestone','title'=>$event['title']??'Completed date','content'=>['calendar_event_id'=>$eventId,'event'=>$event['data']??[]],'tags'=>['calendar'],'is_draft'=>false,'is_locked'=>false]);return ApiResponse::created($m);}
        if(str_contains($op,'reflection')){$d=$r->validate(['reflection'=>'required|string|max:5000']);DB::table('feature_records')->insert(['id'=>(string)Str::uuid(),'resource'=>'calendar.event.reflection','user_id'=>$r->user()->id,'couple_space_id'=>RequestContext::coupleSpaceId($r),'parent_id'=>$eventId,'status'=>'active','data'=>json_encode($d),'metadata'=>json_encode([]),'occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);return ApiResponse::ok(['saved'=>true]);}
        if(str_contains($op,'complete')){DB::table('calendar_events')->where('id',$eventId)->update(['status'=>'completed','completed_at'=>now(),'updated_at'=>now()]);event(new DomainRealtimeEvent('calendar.event.updated',['event_id'=>$eventId,'status'=>'completed'],null,RequestContext::coupleSpaceId($r)));return ApiResponse::ok($this->one('calendar_events',$eventId,$r));}
        return $this->simpleCrud('calendar_events',$r);
    }

    private function journeys(string $op,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        if(str_starts_with($op,'get_journeys')){$id=$r->route('journeyId');$q=DB::table('journeys')->whereNull('deleted_at');return ApiResponse::ok($this->decode($id?$q->where('id',$id)->first():$q->orderBy('title')->get()));}
        if(str_contains($op,'journeys_journeyid_start')){$journeyId=$r->route('journeyId');$id=(string)Str::uuid();DB::table('journey_sessions')->insert($this->row($r,['id'=>$id,'parent_id'=>$journeyId,'type'=>'journey-session','status'=>'started']));return ApiResponse::created($this->decode(DB::table('journey_sessions')->where('id',$id)->first()));}
        if($op==='get_journey_sessions')return ApiResponse::ok(DB::table('journey_sessions')->where('couple_space_id',$space)->whereNull('deleted_at')->latest()->get()->map(fn($x)=>$this->decode($x)));
        $sid=$r->route('sessionId');if($op==='get_journey_sessions_sessionid')return ApiResponse::ok($this->one('journey_sessions',$sid,$r));
        if(str_contains($op,'steps')){$step=$r->route('stepId');DB::table('journey_step_progress')->updateOrInsert(['user_id'=>$r->user()->id,'parent_id'=>$sid,'type'=>$step],$this->row($r,['id'=>(string)Str::uuid(),'parent_id'=>$sid,'type'=>$step,'status'=>'completed','completed_at'=>now()]));event(new DomainRealtimeEvent('journey.progress.updated',['session_id'=>$sid,'step_id'=>$step],null,$space));return ApiResponse::ok(['completed'=>true]);}
        if(str_contains($op,'complete')){DB::table('journey_sessions')->where('id',$sid)->where('couple_space_id',$space)->update(['status'=>'completed','completed_at'=>now(),'updated_at'=>now()]);event(new DomainRealtimeEvent('journey.progress.updated',['session_id'=>$sid,'status'=>'completed'],null,$space));return ApiResponse::ok(['completed'=>true]);}
        return ApiResponse::problem('Unsupported journey operation',$op,501,'not_implemented');
    }

    private function reconnection(string $op,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        if($op==='get_reconnection_prompts')return ApiResponse::ok(['prompts'=>['What do you want your partner to understand right now?','What would help you feel ready to reconnect?','Name one thing you still appreciate about each other.']]);
        if($op==='post_reconnection_sessions'){$id=(string)Str::uuid();DB::table('reconnection_sessions')->insert($this->row($r,['id'=>$id,'type'=>'reconnection','status'=>'active']));return ApiResponse::created($this->one('reconnection_sessions',$id,$r));}
        $sid=$r->route('sessionId');
        if(str_contains($op,'answers')){$d=$r->validate(['answer'=>'required|string|max:10000']);DB::table('reconnection_answers')->updateOrInsert(['user_id'=>$r->user()->id,'parent_id'=>$sid,'type'=>'answer'],$this->row($r,['id'=>(string)Str::uuid(),'parent_id'=>$sid,'type'=>'answer','body'=>$d['answer'],'status'=>'submitted']));return ApiResponse::ok(['submitted'=>true]);}
        if(str_contains($op,'reveal')){$needed=DB::table('couple_members')->where('couple_space_id',$space)->count();$answers=DB::table('reconnection_answers')->where('parent_id',$sid)->whereNull('deleted_at')->get();if($needed<2||$answers->count()<$needed)return ApiResponse::problem('Reveal locked','Both partners must answer before reveal.',423,'reconnection_reveal_locked');return ApiResponse::ok($answers->map(fn($x)=>$this->decode($x)));}
        return ApiResponse::problem('Unsupported reconnection operation',$op,501,'not_implemented');
    }

    private function linkedDevices(string $op,Request $r)
    {
        if($op==='post_linked_devices_qr_sessions'){
            $d=$r->validate(['device_name'=>'nullable|string|max:100','platform'=>'nullable|in:web,desktop,pwa','browser'=>'nullable|string|max:80']);
            $id=(string)Str::uuid();$browserSecret=Str::random(64);$approvalToken=Str::random(64);$deviceId='web-'.Str::uuid();
            $pairingCode=Str::upper(substr(Str::random(3),0,3).'-'.substr(Str::random(3),0,3));$expires=now()->addMinutes(2);
            DB::table('device_link_intents')->insert($this->row($r,[
                'id'=>$id,'type'=>hash('sha256',$approvalToken),'status'=>'waiting','scheduled_at'=>$expires,
                'data'=>json_encode(['kind'=>'browser_qr','browser_secret_hash'=>hash('sha256',$browserSecret),'device_id'=>$deviceId,'device_name'=>$d['device_name']??'143 Web','platform'=>$d['platform']??'web','browser'=>$d['browser']??null,'pairing_code'=>$pairingCode]),
            ]));
            return ApiResponse::created(['session_id'=>$id,'browser_secret'=>$browserSecret,'pairing_code'=>$pairingCode,'qr_payload'=>'143://device-link/'.$id.'?token='.urlencode($approvalToken),'expires_at'=>$expires->toIso8601String(),'status'=>'waiting']);
        }
        if($op==='get_linked_devices_qr_sessions_sessionid_preview'){
            $d=$r->validate(['approval_token'=>'required|string|min:32|max:200']);$session=$this->qrSession($r->route('sessionId'));
            if(!$session||!$this->validQrApproval($session,$d['approval_token']))return ApiResponse::problem('Pairing request unavailable','This QR code is invalid or has expired.',422,'invalid_qr_session');
            $data=$this->decode($session)['data']??[];
            return ApiResponse::ok(['session_id'=>$session->id,'device_name'=>$data['device_name']??'143 Web','platform'=>$data['platform']??'web','browser'=>$data['browser']??null,'expires_at'=>$session->scheduled_at]);
        }
        if($op==='post_linked_devices_qr_sessions_sessionid_approve'){
            $d=$r->validate(['approval_token'=>'required|string|min:32|max:200','approving_device_name'=>'nullable|string|max:100']);
            return DB::transaction(function()use($r,$d){
                $session=DB::table('device_link_intents')->where('id',$r->route('sessionId'))->lockForUpdate()->first();
                if(!$session||!$this->validQrApproval($session,$d['approval_token']))return ApiResponse::problem('Pairing request unavailable','This QR code is invalid, used, or expired.',422,'invalid_qr_session');
                $data=$this->decode($session)['data']??[];$grant=Str::random(80);
                $data['exchange_grant_hash']=hash('sha256',$grant);$data['exchange_grant_encrypted']=Crypt::encryptString($grant);$data['approved_by_device']=$d['approving_device_name']??'143 mobile';
                DB::table('device_link_intents')->where('id',$session->id)->update(['user_id'=>$r->user()->id,'status'=>'approved','data'=>json_encode($data),'updated_at'=>now()]);
                return ApiResponse::ok(['approved'=>true,'device_name'=>$data['device_name']??'143 Web']);
            });
        }
        if($op==='get_linked_devices_qr_sessions_sessionid'){
            $d=$r->validate(['browser_secret'=>'required|string|min:32|max:200']);$session=$this->qrSession($r->route('sessionId'));
            if(!$session||!$this->validBrowserSecret($session,$d['browser_secret']))return ApiResponse::problem('Pairing request unavailable','This pairing session is invalid.',404,'qr_session_not_found');
            $data=$this->decode($session)['data']??[];$status=$session->scheduled_at<=now()&&$session->status==='waiting'?'expired':$session->status;
            $payload=['session_id'=>$session->id,'status'=>$status,'expires_at'=>$session->scheduled_at];
            if($status==='approved'&&!empty($data['exchange_grant_encrypted']))$payload['exchange_grant']=Crypt::decryptString($data['exchange_grant_encrypted']);
            return ApiResponse::ok($payload);
        }
        if($op==='post_linked_devices_qr_sessions_sessionid_exchange'){
            $d=$r->validate(['browser_secret'=>'required|string|min:32|max:200','exchange_grant'=>'required|string|min:32|max:200']);
            return DB::transaction(function()use($r,$d){
                $session=DB::table('device_link_intents')->where('id',$r->route('sessionId'))->lockForUpdate()->first();$data=$this->decode($session)['data']??[];
                $valid=$session&&$session->status==='approved'&&$session->scheduled_at>now()&&$this->validBrowserSecret($session,$d['browser_secret'])&&!empty($data['exchange_grant_hash'])&&hash_equals($data['exchange_grant_hash'],hash('sha256',$d['exchange_grant']));
                if(!$valid)return ApiResponse::problem('Pairing exchange denied','This pairing approval is invalid, expired, or was already used.',422,'invalid_pairing_exchange');
                $user=User::find($session->user_id);if(!$user)return ApiResponse::problem('Account unavailable','The approving account is unavailable.',404,'account_unavailable');
                $deviceName=$data['device_name']??'143 Web';$deviceId=$data['device_id']??('web-'.Str::uuid());$tokenName=$deviceName.' ('.$deviceId.')';
                DB::table('device_link_intents')->where('id',$session->id)->update(['status'=>'exchanged','completed_at'=>now(),'data'=>json_encode(array_diff_key($data,array_flip(['exchange_grant_hash','exchange_grant_encrypted']))),'updated_at'=>now()]);
                DB::table('linked_devices')->updateOrInsert(['user_id'=>$user->id,'type'=>$deviceId],['id'=>(string)Str::uuid(),'user_id'=>$user->id,'couple_space_id'=>null,'parent_id'=>null,'type'=>$deviceId,'status'=>'active','title'=>$deviceName,'body'=>null,'data'=>json_encode(['platform'=>$data['platform']??'web','browser'=>$data['browser']??null,'token_name'=>$tokenName]),'metadata'=>json_encode([]),'scheduled_at'=>null,'occurred_at'=>now(),'completed_at'=>null,'created_at'=>now(),'updated_at'=>now(),'deleted_at'=>null]);
                return ApiResponse::ok($this->auth->issueForLinkedDevice($user,$tokenName));
            });
        }
        if($op==='get_linked_devices')return ApiResponse::ok(DB::table('linked_devices')->where('user_id',$r->user()->id)->whereNull('deleted_at')->latest()->get()->map(fn($x)=>$this->decode($x)));
        if($op==='post_linked_devices_link_intents'){$token='143-LINK-'.Str::upper(Str::random(32));$id=(string)Str::uuid();DB::table('device_link_intents')->insert($this->row($r,['id'=>$id,'type'=>hash('sha256',$token),'status'=>'pending','scheduled_at'=>now()->addMinutes(10),'data'=>json_encode(['created_by_device'=>$r->input('device_name')])]));return ApiResponse::created(['intent_id'=>$id,'link_token'=>$token,'qr_payload'=>'143://device-link/'.$id.'?token='.urlencode($token),'expires_at'=>now()->addMinutes(10)]);}
        if(str_contains($op,'intentid_confirm')){$id=$r->route('intentId');$d=$r->validate(['link_token'=>'required|string','device_id'=>'required|string|max:255','device_name'=>'nullable|string|max:100','platform'=>'required|in:android,ios,web']);$intent=DB::table('device_link_intents')->where('id',$id)->where('user_id',$r->user()->id)->where('status','pending')->where('scheduled_at','>',now())->first();if(!$intent||!hash_equals($intent->type,hash('sha256',$d['link_token'])))return ApiResponse::problem('Invalid link intent','The device link token is invalid or expired.',422,'invalid_link_intent');DB::table('device_link_intents')->where('id',$id)->update(['status'=>'confirmed','completed_at'=>now(),'updated_at'=>now()]);DB::table('linked_devices')->updateOrInsert(['user_id'=>$r->user()->id,'type'=>$d['device_id']],$this->row($r,['id'=>(string)Str::uuid(),'type'=>$d['device_id'],'title'=>$d['device_name']??$d['platform'],'status'=>'active','data'=>json_encode(['platform'=>$d['platform']])]));return ApiResponse::ok(['linked'=>true]);}
        if(str_contains($op,'link_intents_intentid')){$x=DB::table('device_link_intents')->where('id',$r->route('intentId'))->where('user_id',$r->user()->id)->first();return ApiResponse::ok($this->decode($x));}
        if($r->method()==='DELETE'){$device=DB::table('linked_devices')->where('id',$r->route('deviceId'))->where('user_id',$r->user()->id)->first();if(!$device)return ApiResponse::problem('Device not found','This linked device does not exist.',404,'not_found');$tokenName=($this->decode($device)['data']??[])['token_name']??null;DB::table('linked_devices')->where('id',$device->id)->update(['deleted_at'=>now(),'status'=>'revoked','updated_at'=>now()]);if($tokenName){$r->user()->tokens()->where('name',$tokenName)->delete();DB::table('refresh_tokens')->where('user_id',$r->user()->id)->where('device_name',$tokenName)->whereNull('revoked_at')->update(['revoked_at'=>now()]);}return ApiResponse::ok(['revoked'=>true]);}
        return ApiResponse::problem('Unsupported linked-device operation',$op,501,'not_implemented');
    }

    private function qrSession(?string $id){return $id?DB::table('device_link_intents')->where('id',$id)->whereNull('deleted_at')->first():null;}
    private function validQrApproval($session,string $token):bool{return $session&&$session->status==='waiting'&&$session->scheduled_at>now()&&hash_equals((string)$session->type,hash('sha256',$token));}
    private function validBrowserSecret($session,string $secret):bool{$data=$this->decode($session)['data']??[];return !empty($data['browser_secret_hash'])&&hash_equals($data['browser_secret_hash'],hash('sha256',$secret));}

    private function spotify(string $op,Request $r)
    {
        $client=env('SPOTIFY_CLIENT_ID');$secret=env('SPOTIFY_CLIENT_SECRET');$redirect=env('SPOTIFY_REDIRECT_URI');
        if($op==='post_integrations_spotify_connect'){
            if(!$client||!$redirect)return ApiResponse::problem('Spotify not configured','Add Spotify client configuration.',503,'integration_not_configured');
            $state=Str::random(48);$id=(string)Str::uuid();DB::table('spotify_connections')->insert($this->row($r,['id'=>$id,'type'=>hash('sha256',$state),'status'=>'pending','data'=>json_encode(['redirect_uri'=>$redirect])]));$scopes='user-read-playback-state playlist-read-private playlist-read-collaborative';$url='https://accounts.spotify.com/authorize?'.http_build_query(['response_type'=>'code','client_id'=>$client,'scope'=>$scopes,'redirect_uri'=>$redirect,'state'=>$state]);return ApiResponse::ok(['authorize_url'=>$url]);
        }
        if($op==='get_integrations_spotify_callback'){
            $state=(string)$r->query('state');$code=(string)$r->query('code');$conn=DB::table('spotify_connections')->where('type',hash('sha256',$state))->where('status','pending')->first();if(!$conn||!$code)return ApiResponse::problem('Spotify authorization failed','Invalid OAuth state or code.',422,'spotify_oauth_failed');
            $resp=Http::asForm()->withBasicAuth($client,$secret)->post('https://accounts.spotify.com/api/token',['grant_type'=>'authorization_code','code'=>$code,'redirect_uri'=>$redirect]);if(!$resp->successful())return ApiResponse::problem('Spotify token exchange failed','Spotify rejected the authorization code.',502,'spotify_token_error');$j=$resp->json();DB::table('spotify_connections')->where('id',$conn->id)->update(['status'=>'active','data'=>json_encode(['access_token'=>Crypt::encryptString($j['access_token']),'refresh_token'=>isset($j['refresh_token'])?Crypt::encryptString($j['refresh_token']):null,'expires_at'=>now()->addSeconds((int)($j['expires_in']??3600))->toIso8601String()]),'updated_at'=>now()]);return ApiResponse::ok(['connected'=>true]);
        }
        $conn=DB::table('spotify_connections')->where('user_id',$r->user()->id)->where('status','active')->whereNull('deleted_at')->latest()->first();if(!$conn)return ApiResponse::problem('Spotify not connected','Connect Spotify first.',409,'spotify_not_connected');
        if($r->method()==='DELETE'){DB::table('spotify_connections')->where('id',$conn->id)->update(['status'=>'revoked','deleted_at'=>now(),'updated_at'=>now()]);return ApiResponse::ok(['disconnected'=>true]);}
        $data=json_decode($conn->data??'{}',true);$token=Crypt::decryptString($data['access_token']);$endpoint=str_contains($op,'playlists')?'https://api.spotify.com/v1/me/playlists':'https://api.spotify.com/v1/me/player/currently-playing';$resp=Http::withToken($token)->get($endpoint);if($resp->status()===204)return ApiResponse::ok(null);if(!$resp->successful())return ApiResponse::problem('Spotify API error','Spotify request failed.',502,'spotify_api_error',['provider_status'=>$resp->status()]);return ApiResponse::ok($resp->json());
    }

    private function exports(string $op,Request $r)
    {
        if($op==='post_exports'){$d=$r->validate(['format'=>'required|in:json,pdf']);$id=(string)Str::uuid();DB::table('data_exports')->insert($this->row($r,['id'=>$id,'type'=>$d['format'],'status'=>'queued']));GenerateDataExport::dispatch($id,$r->user()->id,RequestContext::coupleSpaceId($r),$d['format']);return response()->json(['ok'=>true,'data'=>['id'=>$id,'status'=>'queued']],202);}
        if($op==='get_exports')return ApiResponse::ok(DB::table('data_exports')->where('user_id',$r->user()->id)->whereNull('deleted_at')->latest()->get()->map(fn($x)=>$this->decode($x)));
        $id=$r->route('exportId');$row=$this->decode(DB::table('data_exports')->where('id',$id)->where('user_id',$r->user()->id)->whereNull('deleted_at')->first());if(!$row)return ApiResponse::problem('Export not found','Export does not exist.',404,'not_found');
        if(str_contains($op,'download')){if(($row['status']??null)!=='completed')return ApiResponse::problem('Export not ready','The export is still being generated.',409,'export_not_ready');$key=data_get($row,'data.storage_key');$url=Storage::disk(config('143.upload_disk'))->temporaryUrl($key,now()->addMinutes(10));return ApiResponse::ok(['url'=>$url,'expires_in'=>600]);}
        return ApiResponse::ok($row);
    }

    private function simpleCrud(string $table,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);$id=null;foreach($r->route()->parameters() as $k=>$v)if(is_string($v)&&preg_match('/Id$/',$k))$id=$v;
        $scope=DB::table($table)->whereNull('deleted_at')->where(function($q)use($r,$space){$q->where('user_id',$r->user()->id);if($space)$q->orWhere('couple_space_id',$space);});
        if($r->method()==='GET'){if($id)return ApiResponse::ok($this->decode((clone $scope)->where('id',$id)->first()));return ApiResponse::ok($scope->latest()->limit(min((int)$r->input('limit',50),100))->get()->map(fn($x)=>$this->decode($x)));}
        if($r->method()==='DELETE'){$scope->where('id',$id)->update(['deleted_at'=>now(),'updated_at'=>now()]);return ApiResponse::ok(['deleted'=>true]);}
        if(in_array($r->method(),['PATCH','PUT'],true)){$old=$this->decode((clone $scope)->where('id',$id)->first());if(!$old)return ApiResponse::problem('Not found','Resource does not exist.',404,'not_found');$data=array_replace_recursive($old['data']??[],$r->all());$scope->where('id',$id)->update(['data'=>json_encode($data),'title'=>$r->input('title',$old['title']??null),'body'=>$r->input('body',$old['body']??null),'status'=>$r->input('status',$old['status']??'active'),'updated_at'=>now()]);return ApiResponse::ok($this->decode(DB::table($table)->where('id',$id)->first()));}
        $new=(string)Str::uuid();DB::table($table)->insert($this->row($r,['id'=>$new,'type'=>$r->input('type'),'title'=>$r->input('title'),'body'=>$r->input('body'),'status'=>$r->input('status','active'),'data'=>json_encode($r->all()),'scheduled_at'=>$r->input('scheduled_at')]));return ApiResponse::created($this->decode(DB::table($table)->where('id',$new)->first()));
    }

    private function one(string $table,?string $id,Request $r):?array{if(!$id)return null;$space=RequestContext::coupleSpaceId($r);$row=DB::table($table)->where('id',$id)->whereNull('deleted_at')->where(function($q)use($r,$space){$q->where('user_id',$r->user()->id);if($space)$q->orWhere('couple_space_id',$space);})->first();return $this->decode($row);}
    private function row(Request $r,array $overrides=[]):array{return array_merge(['id'=>(string)Str::uuid(),'user_id'=>$r->user()?->id,'couple_space_id'=>RequestContext::coupleSpaceId($r),'parent_id'=>null,'type'=>null,'status'=>'active','title'=>null,'body'=>null,'data'=>json_encode([]),'metadata'=>json_encode([]),'scheduled_at'=>null,'occurred_at'=>now(),'completed_at'=>null,'created_at'=>now(),'updated_at'=>now(),'deleted_at'=>null],$overrides);}
    private function decode($row){if(!$row)return null;if($row instanceof \Illuminate\Support\Collection)return $row->map(fn($x)=>$this->decode($x));$x=(array)$row;foreach(['data','metadata'] as $k)if(isset($x[$k])&&is_string($x[$k]))$x[$k]=json_decode($x[$k],true)??$x[$k];return $x;}
}
