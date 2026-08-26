<?php
namespace App\Services;

use App\Events\DomainRealtimeEvent;
use App\Models\{Backup,CoupleMember,CryptoDevice,CryptoPrekey,Favorite,Memory,Message,Notification,RefreshToken};
use App\Support\{ApiResponse,RequestContext};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,Http};
use Illuminate\Support\Str;

class DomainService
{
    public function __construct(private ComplexDomainService $complex, private RelationshipDomainService $relationship) {}
    private array $tableMap = [
        'calls'=>'calls','calls.decline'=>'calls','calls.end'=>'calls',
        'together-rooms'=>'together_rooms','together-rooms.leave'=>'together_rooms',
        'pings'=>'pings','pings.reactions'=>'pings','reminders'=>'reminders','check-ins'=>'check_ins','streaks'=>'streaks',
        'linked-devices'=>'linked_devices','linked-devices.link-intents'=>'device_link_intents','blocks'=>'user_blocks','reports'=>'user_reports',
        'exports'=>'data_exports','support.tickets'=>'support_tickets','feedback'=>'feedback_entries',
        'daily.questions'=>'daily_questions','daily.answers'=>'daily_answers','daily.activities'=>'daily_activities','daily'=>'daily_activities',
        'games'=>'games','game-sessions'=>'game_sessions','game-sessions.answers'=>'game_answers','xp'=>'xp_ledger','game-rewards'=>'achievement_unlocks',
        'achievements'=>'achievements','achievement-progress'=>'achievement_unlocks',
        'calendar.events'=>'calendar_events','long-distance'=>'long_distance_settings','travel-itineraries'=>'travel_itineraries','virtual-dates'=>'virtual_dates',
        'jars'=>'jars','bucket-list'=>'bucket_items','bucket-list.convert-to-milestone'=>'bucket_items','goals'=>'goals',
        'love-language'=>'love_languages','partner.preferences'=>'partner_preferences','moods'=>'moods','need-you'=>'need_you_events',
        'scheduled-messages'=>'scheduled_messages','surprises'=>'surprises','story.events'=>'story_events','albums'=>'albums','albums.lock'=>'albums',
        'couple-music'=>'couple_music','integrations.spotify'=>'spotify_connections','widgets'=>'widget_settings','widgets.ping'=>'pings',
        'migrations.device'=>'device_migrations','cool-down'=>'cool_down_sessions','reconnection.sessions'=>'reconnection_sessions','reconnection'=>'reconnection_answers',
        'journeys'=>'journeys','journey-sessions'=>'journey_sessions','ai'=>'ai_requests','shared-lists'=>'shared_lists','shared-lists.items'=>'shared_list_items',
        'budgets'=>'budgets','expenses'=>'expenses',
    ];

    public function handle(string $operation, string $resource, Request $request)
    {
        if ($this->complex->supports($resource)) return $this->complex->handle($operation,$resource,$request);
        if ($this->relationship->supports($resource)) return $this->relationship->handle($operation,$resource,$request);
        return match (true) {
            $operation === 'get_calls_ice_servers' => ApiResponse::ok(config('143.ice_servers')),
            $resource === 'home' => $this->home($request),
            $resource === 'search' => $this->search($request),
            $resource === 'activity' => $this->activity($request),
            $resource === 'unread-summary' => $this->unreadSummary($request),
            $resource === 'favorites' => $this->favorites($operation, $request),
            $resource === 'sessions' => $this->sessions($operation, $request),
            $resource === 'security' => $this->userArraySetting($request, 'security_settings'),
            $resource === 'privacy.permissions' => $this->userArraySetting($request, 'privacy_permissions'),
            $resource === 'preferences' => $this->userArraySetting($request, 'preferences'),
            $resource === 'couple-profile' => $this->coupleProfile($request),
            $resource === 'crypto.devices' || $resource === 'crypto.users' => $this->crypto($operation, $request),
            $resource === 'backups' => $this->backups($operation, $request),
            str_starts_with($resource, 'daily') => $this->daily($operation, $resource, $request),
            $resource === 'games' || str_starts_with($resource, 'game-sessions') || in_array($resource, ['xp','game-rewards'], true) => $this->games($operation, $resource, $request),
            str_starts_with($resource, 'insights') => $this->insights($resource, $request),
            $resource === 'ai' => $this->ai($operation, $request),
            $resource === 'romantic-suggestions' => $this->romanticSuggestions($request),
            $resource === 'date-generator' => $this->dateGenerator($operation, $request),
            $resource === 'date-plans' => $this->datePlans($operation, $request),
            $resource === 'countdowns' => $this->countdowns($request),
            $resource === 'story' => $this->story($request),
            $resource === 'budget-privacy' => $this->budgetPrivacy($request),
            $resource === 'achievements' || $resource === 'achievement-progress' => $this->achievements($resource, $request),
            $resource === 'about' => ApiResponse::ok(['name'=>'143','api_version'=>'v1','mobile_feature_baseline'=>514,'modules'=>36]),
            $resource === 'release-notes' => $this->staticRecords('release-notes', $request),
            $resource === 'help.articles' => $this->staticRecords('help.articles', $request),
            isset($this->tableMap[$resource]) => $this->domainCrud($operation, $resource, $this->tableMap[$resource], $request),
            default => ApiResponse::problem('Unsupported domain resource','No domain implementation is registered for '.$resource.'.',501,'domain_not_implemented'),
        };
    }

    private function home(Request $r)
    {
        $space = RequestContext::coupleSpaceId($r);
        return ApiResponse::ok([
            'couple_space_id'=>$space,
            'partner'=> $space ? CoupleMember::where('couple_space_id',$space)->where('user_id','!=',$r->user()->id)->with('user:id,username,display_name,photo_path')->first()?->user : null,
            'recent_memories'=> $space ? Memory::where('couple_space_id',$space)->where('is_draft',false)->latest()->limit(5)->get() : [],
            'unread_messages'=> $space ? $this->countUnreadMessages($r) : 0,
            'unread_notifications'=> Notification::where('user_id',$r->user()->id)->whereNull('read_at')->count(),
            'today'=> DB::table('daily_activities')->where(fn($q)=>$q->where('user_id',$r->user()->id)->orWhere('couple_space_id',$space))->whereDate('created_at',today())->latest()->limit(5)->get(),
            'server_time'=> now()->toIso8601String(),
        ]);
    }

    private function search(Request $r)
    {
        $q = trim((string)$r->input('q',''));
        if (mb_strlen($q) < 2) return ApiResponse::ok(['memories'=>[],'messages'=>[]]);
        $space = RequestContext::coupleSpaceId($r);
        $memories = Memory::where('couple_space_id',$space)->where(fn($x)=>$x->where('title','ilike','%'.$q.'%')->orWhereRaw("content::text ilike ?", ['%'.$q.'%']))->limit(30)->get();
        $messages = Message::where('couple_space_id',$space)->whereNull('deleted_at')->where('body','ilike','%'.$q.'%')->latest()->limit(30)->get();
        return ApiResponse::ok(['memories'=>$memories,'messages'=>$messages]);
    }

    private function activity(Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        $events=DB::table('audit_logs')->where(function($q)use($r,$space){$q->where('user_id',$r->user()->id);if($space)$q->orWhere('couple_space_id',$space);})->latest()->limit(min((int)$r->input('limit',50),100))->get();
        return ApiResponse::ok($events);
    }

    private function unreadSummary(Request $r)
    {
        return ApiResponse::ok(['messages'=>$this->countUnreadMessages($r),'notifications'=>Notification::where('user_id',$r->user()->id)->whereNull('read_at')->count()]);
    }

    private function countUnreadMessages(Request $r): int
    {
        $space=RequestContext::coupleSpaceId($r); if(!$space)return 0;
        return Message::where('couple_space_id',$space)->where('sender_id','!=',$r->user()->id)->whereNull('deleted_at')
            ->whereDoesntHave('receipts',fn($q)=>$q->where('user_id',$r->user()->id)->whereNotNull('read_at'))->count();
    }

    private function favorites(string $op,Request $r)
    {
        if($r->method()==='GET') return ApiResponse::ok(Favorite::where('user_id',$r->user()->id)->latest()->get());
        if($r->method()==='DELETE'){Favorite::where('id',$r->route('favoriteId'))->where('user_id',$r->user()->id)->delete();return ApiResponse::ok(['deleted'=>true]);}
        $d=$r->validate(['type'=>'required|string|max:100','id'=>'required|uuid','metadata'=>'nullable|array']);
        $x=Favorite::firstOrCreate(['user_id'=>$r->user()->id,'favoritable_type'=>$d['type'],'favoritable_id'=>$d['id']],['couple_space_id'=>RequestContext::coupleSpaceId($r),'metadata'=>$d['metadata']??[]]);
        return ApiResponse::created($x);
    }

    private function sessions(string $op,Request $r)
    {
        if($r->method()==='GET') return ApiResponse::ok(['access_tokens'=>$r->user()->tokens()->latest()->get(['id','name','last_used_at','expires_at','created_at']),'refresh_tokens'=>RefreshToken::where('user_id',$r->user()->id)->whereNull('revoked_at')->get(['id','device_name','expires_at','created_at'])]);
        if($r->method()==='DELETE' && $r->route('sessionId')){$id=$r->route('sessionId');$r->user()->tokens()->where('id',$id)->delete();RefreshToken::where('id',$id)->where('user_id',$r->user()->id)->update(['revoked_at'=>now()]);return ApiResponse::ok(['revoked'=>true]);}
        $r->user()->tokens()->delete();RefreshToken::where('user_id',$r->user()->id)->whereNull('revoked_at')->update(['revoked_at'=>now()]);return ApiResponse::ok(['revoked_all'=>true]);
    }

    private function userArraySetting(Request $r,string $column)
    {
        if($r->method()==='GET') return ApiResponse::ok($r->user()->{$column}?:[]);
        $payload=$r->validate(['settings'=>'nullable|array','permissions'=>'nullable|array','preferences'=>'nullable|array']);
        $incoming=$payload['settings']??$payload['permissions']??$payload['preferences']??$r->all();
        $merged=array_replace_recursive($r->user()->{$column}?:[],$incoming);$r->user()->update([$column=>$merged]);return ApiResponse::ok($merged);
    }

    private function coupleProfile(Request $r)
    {
        $space=RequestContext::coupleSpaceId($r); if(!$space)return ApiResponse::problem('No couple space','Link a partner first.',409,'no_couple_space');
        $row=DB::table('couple_spaces')->where('id',$space)->first();
        if($r->method()==='GET')return ApiResponse::ok($row);
        $data=$r->all();$settings=(array)json_decode($row->settings??'{}',true);$settings['profile']=array_replace_recursive($settings['profile']??[],$data);DB::table('couple_spaces')->where('id',$space)->update(['settings'=>json_encode($settings),'updated_at'=>now()]);return ApiResponse::ok($settings['profile']);
    }

    private function crypto(string $op,Request $r)
    {
        if($op==='get_calls_ice_servers') return ApiResponse::ok(config('143.ice_servers'));
        if($op==='post_crypto_devices_keys'){$d=$r->validate(['device_id'=>'required|string|max:255','identity_key'=>'required|array','signed_prekey'=>'nullable|array']);$x=CryptoDevice::updateOrCreate(['user_id'=>$r->user()->id,'device_id'=>$d['device_id']],['identity_key'=>$d['identity_key'],'signed_prekey'=>$d['signed_prekey']??null]);return ApiResponse::created($x);}
        if($op==='put_crypto_devices_keys_signed_prekey'){$d=$r->validate(['device_id'=>'required|string','signed_prekey'=>'required|array']);$x=CryptoDevice::where('user_id',$r->user()->id)->where('device_id',$d['device_id'])->firstOrFail();$x->update(['signed_prekey'=>$d['signed_prekey']]);return ApiResponse::ok($x);}
        if($op==='post_crypto_devices_keys_one_time_prekeys'){$d=$r->validate(['device_id'=>'required|string','prekeys'=>'required|array|min:1|max:100']);$dev=CryptoDevice::where('user_id',$r->user()->id)->where('device_id',$d['device_id'])->firstOrFail();foreach($d['prekeys'] as $p)CryptoPrekey::create(['crypto_device_id'=>$dev->id,'key_payload'=>$p]);return ApiResponse::created(['stored'=>count($d['prekeys'])]);}
        if($op==='get_crypto_users_userid_key_bundle'){$uid=$r->route('userId');$devs=CryptoDevice::where('user_id',$uid)->get()->map(function($d){$pre=CryptoPrekey::where('crypto_device_id',$d->id)->whereNull('used_at')->first();if($pre)$pre->update(['used_at'=>now()]);return ['device_id'=>$d->device_id,'identity_key'=>$d->identity_key,'signed_prekey'=>$d->signed_prekey,'one_time_prekey'=>$pre?->key_payload];});return ApiResponse::ok($devs);}
        if($op==='get_crypto_devices_keys_status')return ApiResponse::ok(CryptoDevice::where('user_id',$r->user()->id)->get()->map(fn($d)=>['device_id'=>$d->device_id,'unused_prekeys'=>CryptoPrekey::where('crypto_device_id',$d->id)->whereNull('used_at')->count()]));
        return ApiResponse::problem('Unsupported crypto operation',$op,501,'not_implemented');
    }

    private function backups(string $op,Request $r)
    {
        if($r->method()==='GET' && !$r->route('backupId'))return ApiResponse::ok(Backup::where('user_id',$r->user()->id)->latest()->get());
        if($op==='get_backups_status')return ApiResponse::ok(Backup::where('user_id',$r->user()->id)->latest()->first());
        if($r->method()==='POST' && !$r->route('backupId')){$d=$r->validate(['storage_key'=>'nullable|string','metadata'=>'nullable|array']);$x=Backup::create(['user_id'=>$r->user()->id,'couple_space_id'=>RequestContext::coupleSpaceId($r),'storage_key'=>$d['storage_key']??null,'status'=>'pending','metadata'=>$d['metadata']??[]]);return ApiResponse::created($x);}
        $x=Backup::where('id',$r->route('backupId'))->where('user_id',$r->user()->id)->firstOrFail();
        if($r->method()==='DELETE'){$x->delete();return ApiResponse::ok(['deleted'=>true]);}
        if(str_contains($op,'restore')){$x->update(['status'=>'restore_requested']);return ApiResponse::ok(['restore_requested'=>true,'backup'=>$x]);}
        return ApiResponse::ok($x);
    }

    private function daily(string $op,string $resource,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        if($op==='get_daily_today'){
            $question=DB::table('daily_questions')->where(fn($q)=>$q->where('couple_space_id',$space)->orWhereNull('couple_space_id'))->whereDate('scheduled_at',today())->orderByDesc('couple_space_id')->first();
            $answer=$question?DB::table('daily_answers')->where('parent_id',$question->id)->where('user_id',$r->user()->id)->first():null;
            return ApiResponse::ok(['question'=>$question,'my_answer'=>$answer,'activities'=>DB::table('daily_activities')->where('couple_space_id',$space)->whereDate('scheduled_at',today())->get()]);
        }
        if(str_contains($op,'answers') && $r->method()==='POST'){
            $qid=$r->route('questionId');$d=$r->validate(['answer'=>'required|string|max:10000']);$id=(string)Str::uuid();
            DB::table('daily_answers')->updateOrInsert(['user_id'=>$r->user()->id,'parent_id'=>$qid,'type'=>'answer'],['id'=>$id,'couple_space_id'=>$space,'body'=>$d['answer'],'status'=>'submitted','data'=>json_encode([]),'created_at'=>now(),'updated_at'=>now()]);
            $needed=CoupleMember::where('couple_space_id',$space)->count();$count=DB::table('daily_answers')->where('parent_id',$qid)->whereNull('deleted_at')->count();if($needed>0&&$count>=$needed)event(new DomainRealtimeEvent('daily.reveal.ready',['question_id'=>$qid],null,$space));return ApiResponse::ok(['submitted'=>true,'reveal_ready'=>$needed>0&&$count>=$needed]);
        }
        if(str_contains($op,'reveal')){
            $qid=$r->route('questionId');$needed=CoupleMember::where('couple_space_id',$space)->count();$answers=DB::table('daily_answers')->where('parent_id',$qid)->whereNull('deleted_at')->get();if($needed<2||$answers->count()<$needed)return ApiResponse::problem('Reveal locked','Both partners must answer before answers are revealed.',423,'daily_reveal_locked');return ApiResponse::ok($answers);
        }
        return $this->domainCrud($op,$resource,$this->tableMap[$resource]??'daily_activities',$r);
    }

    private function games(string $op,string $resource,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        if($resource==='games' && $r->method()==='GET')return ApiResponse::ok(DB::table('games')->whereNull('deleted_at')->orderBy('title')->get());
        if($op==='post_game_sessions'){$d=$r->validate(['game_key'=>'required|string|max:80','data'=>'nullable|array']);$id=(string)Str::uuid();DB::table('game_sessions')->insert($this->row($r,['id'=>$id,'type'=>$d['game_key'],'status'=>'active','data'=>json_encode($d['data']??[]) ]));event(new DomainRealtimeEvent('game.session.updated',['id'=>$id,'status'=>'active'],null,$space));return ApiResponse::created(DB::table('game_sessions')->where('id',$id)->first());}
        if(str_contains($op,'answers') && $r->method()==='POST'){$sid=$r->route('sessionId');$d=$r->validate(['question_key'=>'required|string|max:100','answer'=>'required']);$id=(string)Str::uuid();DB::table('game_answers')->updateOrInsert(['user_id'=>$r->user()->id,'parent_id'=>$sid,'type'=>$d['question_key']],array_merge($this->row($r,['id'=>$id,'parent_id'=>$sid,'type'=>$d['question_key'],'body'=>is_scalar($d['answer'])?(string)$d['answer']:null,'data'=>json_encode(['answer'=>$d['answer']])]),['updated_at'=>now()]));return ApiResponse::ok(['submitted'=>true]);}
        if(str_contains($op,'complete')){$sid=$r->route('sessionId');DB::table('game_sessions')->where('id',$sid)->where('couple_space_id',$space)->update(['status'=>'completed','completed_at'=>now(),'updated_at'=>now()]);$points=20;DB::table('xp_ledger')->insert($this->row($r,['id'=>(string)Str::uuid(),'parent_id'=>$sid,'type'=>'game_completed','data'=>json_encode(['points'=>$points])]));event(new DomainRealtimeEvent('game.result.ready',['session_id'=>$sid,'xp_awarded'=>$points],null,$space));return ApiResponse::ok(['completed'=>true,'xp_awarded'=>$points]);}
        if($resource==='xp'){$total=DB::table('xp_ledger')->where('couple_space_id',$space)->whereNull('deleted_at')->get()->sum(fn($x)=>(int)data_get(json_decode($x->data??'{}',true),'points',0));return ApiResponse::ok(['xp'=>$total]);}
        return $this->domainCrud($op,$resource,$this->tableMap[$resource]??'game_sessions',$r);
    }

    private function insights(string $resource,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        $base=[
            'messages'=>Message::where('couple_space_id',$space)->whereNull('deleted_at')->count(),
            'memories'=>Memory::where('couple_space_id',$space)->where('is_draft',false)->count(),
            'pings'=>DB::table('pings')->where('couple_space_id',$space)->whereNull('deleted_at')->count(),
            'completed_goals'=>DB::table('goals')->where('couple_space_id',$space)->where('status','completed')->whereNull('deleted_at')->count(),
            'games_completed'=>DB::table('game_sessions')->where('couple_space_id',$space)->where('status','completed')->whereNull('deleted_at')->count(),
        ];
        $base['period']=$resource; $base['generated_at']=now()->toIso8601String();
        return ApiResponse::ok($base);
    }

    private function ai(string $op,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        if($op==='get_ai_settings')return ApiResponse::ok(DB::table('ai_settings')->where('user_id',$r->user()->id)->whereNull('deleted_at')->first());
        if($op==='put_ai_settings'){
            $d=$r->validate(['enabled'=>'required|boolean','allowed_scopes'=>'nullable|array']);
            $scopes=array_values(array_intersect($d['allowed_scopes']??[],['preferences','calendar','memories_selected','goals','wishlist','location_coarse']));
            $id=(string)Str::uuid();DB::table('ai_settings')->updateOrInsert(['user_id'=>$r->user()->id,'couple_space_id'=>$space],$this->row($r,['id'=>$id,'type'=>'settings','status'=>$d['enabled']?'enabled':'disabled','data'=>json_encode(['enabled'=>$d['enabled'],'allowed_scopes'=>$scopes])]));return ApiResponse::ok(['enabled'=>$d['enabled'],'allowed_scopes'=>$scopes]);
        }
        $setting=DB::table('ai_settings')->where('user_id',$r->user()->id)->where('status','enabled')->whereNull('deleted_at')->first();
        if(!$setting)return ApiResponse::problem('AI disabled','Enable the optional 143 AI Assistant and choose allowed context scopes first.',403,'ai_opt_in_required');
        if(!filled(env('OPENAI_API_KEY')))return ApiResponse::problem('OpenAI not configured','Set OPENAI_API_KEY in Render environment variables.',503,'integration_not_configured');
        $d=$r->validate(['prompt'=>'nullable|string|max:5000','context'=>'nullable|array']);
        $settingData=json_decode($setting->data??'{}',true)?:[];$allowed=$settingData['allowed_scopes']??[];$context=$d['context']??[];
        $disallowed=array_values(array_diff(array_keys($context),$allowed));
        if($disallowed)return ApiResponse::problem('AI context not permitted','One or more requested context scopes are not enabled for AI.',403,'ai_scope_not_allowed',['disallowed_scopes'=>$disallowed]);
        $task=str_replace(['post_ai_suggestions_','_'],['',' '],$op);
        $contextText=$context?"\nUser-approved context (only for this request): ".json_encode($context,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):'';
        $prompt=trim(($d['prompt']??'')."\nTask: Suggest {$task} for a couple. Keep it supportive, non-diagnostic and privacy-respecting.".$contextText);
        $resp=Http::withToken(env('OPENAI_API_KEY'))->acceptJson()->timeout(30)->post(rtrim(env('OPENAI_BASE_URL','https://api.openai.com/v1'),'/').'/responses',['model'=>env('OPENAI_MODEL','gpt-5.4'),'input'=>$prompt]);
        if(!$resp->successful())return ApiResponse::problem('AI provider error','The AI provider request failed.',502,'ai_provider_error',['provider_status'=>$resp->status()]);
        $json=$resp->json();$text='';foreach($json['output']??[] as $item){if(($item['type']??null)!=='message')continue;foreach($item['content']??[] as $part){if(in_array($part['type']??null,['output_text','text'],true)&&isset($part['text'])){$text.=(string)$part['text'];}}}
        DB::table('ai_requests')->insert($this->row($r,['id'=>(string)Str::uuid(),'type'=>$task,'status'=>'completed','data'=>json_encode(['provider_request_id'=>$json['id']??null,'used_scopes'=>array_keys($context)]) ]));
        return ApiResponse::ok(['text'=>trim($text),'provider'=>'openai','model'=>$json['model']??env('OPENAI_MODEL'),'used_scopes'=>array_keys($context)]);
    }

    private function romanticSuggestions(Request $r){return ApiResponse::ok(['suggestions'=>['Write one specific thing you appreciated today.','Plan a low-pressure date around a shared favorite.','Revisit a favorite memory and add a new note to it.']]);}

    private function dateGenerator(string $op,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        if($r->method()==='GET'){
            $rows=DB::table('ai_requests')->where('user_id',$r->user()->id)->where('couple_space_id',$space)->where('type','smart-date-generator')->whereNull('deleted_at')->latest()->limit(50)->get()->map(fn($x)=>$this->decode($x));
            return ApiResponse::ok($rows);
        }
        $d=$r->validate(['budget'=>'nullable|string|max:50','mood'=>'nullable|string|max:80','place'=>'nullable|in:home,outside,either','duration_minutes'=>'nullable|integer|min:15|max:1440']);
        $ideas=[
            ['title'=>'Memory Night','place'=>'home','duration_minutes'=>90,'budget'=>'low'],
            ['title'=>'Sunset Walk + Favorite Snack','place'=>'outside','duration_minutes'=>120,'budget'=>'low'],
            ['title'=>'Cook Each Other a Surprise Dish','place'=>'home','duration_minutes'=>120,'budget'=>'medium'],
            ['title'=>'Photo Scavenger Date','place'=>'outside','duration_minutes'=>150,'budget'=>'low'],
        ];
        if(($d['place']??'either')!=='either')$ideas=array_values(array_filter($ideas,fn($x)=>$x['place']===$d['place']));
        $id=(string)Str::uuid();
        DB::table('ai_requests')->insert($this->row($r,['id'=>$id,'type'=>'smart-date-generator','status'=>'completed','data'=>json_encode(['filters'=>$d,'suggestions'=>$ideas])]));
        return ApiResponse::ok(['id'=>$id,'suggestions'=>$ideas,'filters'=>$d]);
    }

    private function datePlans(string $op,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);[$target]= $this->targetAndParentIds($r);
        $q=DB::table('calendar_events')->where('couple_space_id',$space)->where('type','date-plan')->whereNull('deleted_at');
        if($r->method()==='GET'){
            if($target){$row=(clone $q)->where('id',$target)->first();return $row?ApiResponse::ok($this->decode($row)):ApiResponse::problem('Date plan not found','Date plan does not exist.',404,'not_found');}
            return ApiResponse::ok($q->orderByDesc('scheduled_at')->limit(100)->get()->map(fn($x)=>$this->decode($x)));
        }
        if($r->method()==='DELETE'){$q->where('id',$target)->update(['deleted_at'=>now(),'updated_at'=>now()]);return ApiResponse::ok(['deleted'=>true]);}
        if(in_array($r->method(),['PATCH','PUT'],true)){
            $existing=$this->decode((clone $q)->where('id',$target)->first());if(!$existing)return ApiResponse::problem('Date plan not found','Date plan does not exist.',404,'not_found');
            $data=array_replace_recursive($existing['data']??[],$r->all());$q->where('id',$target)->update(['title'=>$r->input('title',$existing['title']??null),'body'=>$r->input('body',$existing['body']??null),'scheduled_at'=>$r->input('scheduled_at',$existing['scheduled_at']??null),'data'=>json_encode($data),'updated_at'=>now()]);return ApiResponse::ok($this->decode(DB::table('calendar_events')->where('id',$target)->first()));
        }
        $d=$r->validate(['title'=>'required|string|max:255','scheduled_at'=>'nullable|date','body'=>'nullable|string|max:10000','location'=>'nullable|array','data'=>'nullable|array']);$id=(string)Str::uuid();
        DB::table('calendar_events')->insert($this->row($r,['id'=>$id,'type'=>'date-plan','status'=>'planned','title'=>$d['title'],'body'=>$d['body']??null,'scheduled_at'=>$d['scheduled_at']??null,'data'=>json_encode(['location'=>$d['location']??null,'data'=>$d['data']??[]])]));
        return ApiResponse::created($this->decode(DB::table('calendar_events')->where('id',$id)->first()));
    }

    private function countdowns(Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);if(!$space)return ApiResponse::ok([]);
        $events=DB::table('calendar_events')->where('couple_space_id',$space)->whereNull('deleted_at')->whereNotNull('scheduled_at')->where('scheduled_at','>=',now())->orderBy('scheduled_at')->limit(50)->get()->map(function($x){$row=$this->decode($x);$at=\Illuminate\Support\Carbon::parse($x->scheduled_at);$row['seconds_remaining']=max(0,now()->diffInSeconds($at,false));return $row;});
        $relationship=DB::table('relationship_settings')->where('couple_space_id',$space)->first();
        return ApiResponse::ok(['events'=>$events,'relationship'=>$relationship]);
    }

    private function story(Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);$rows=DB::table('story_events')->where('couple_space_id',$space)->whereNull('deleted_at')->orderBy('occurred_at')->orderBy('created_at')->get()->map(fn($x)=>$this->decode($x));
        return ApiResponse::ok(['events'=>$rows,'count'=>$rows->count()]);
    }

    private function budgetPrivacy(Request $r)
    {
        $prefs=$r->user()->privacy_permissions?:[];
        if($r->method()==='GET')return ApiResponse::ok($prefs['budget']??['visible_to_partner'=>true]);
        $d=$r->validate(['visible_to_partner'=>'required|boolean','share_totals'=>'nullable|boolean','share_item_details'=>'nullable|boolean']);$prefs['budget']=$d;$r->user()->update(['privacy_permissions'=>$prefs]);return ApiResponse::ok($d);
    }

    private function achievements(string $resource,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);
        $catalog=DB::table('achievements')->whereNull('deleted_at')->get();
        $unlocked=DB::table('achievement_unlocks')->where('couple_space_id',$space)->whereNull('deleted_at')->get();
        if($resource==='achievements')return ApiResponse::ok(['catalog'=>$catalog,'unlocked'=>$unlocked]);
        return ApiResponse::ok(['unlocked_count'=>$unlocked->count(),'total'=>$catalog->count()]);
    }

    private function staticRecords(string $type,Request $r){return ApiResponse::ok(DB::table('feature_records')->where('resource',$type)->whereNull('deleted_at')->orderBy('created_at')->get());}

    private function domainCrud(string $op,string $resource,string $table,Request $r)
    {
        $space=RequestContext::coupleSpaceId($r);[$target,$parent]=$this->targetAndParentIds($r);
        $query=DB::table($table)->whereNull('deleted_at')->where(function($q)use($r,$space){$q->where('user_id',$r->user()->id);if($space)$q->orWhere('couple_space_id',$space);});
        if($r->method()==='GET'){
            if($target){$row=(clone $query)->where('id',$target)->first();if(!$row)return ApiResponse::problem('Not found','Resource does not exist.',404,'not_found');return ApiResponse::ok($this->decode($row));}
            $rows=$query->orderByDesc('created_at')->limit(min((int)$r->input('limit',50),100))->get()->map(fn($x)=>$this->decode($x));return ApiResponse::ok($rows);
        }
        if($r->method()==='DELETE'){
            if(!$target)return ApiResponse::problem('Missing identifier','A resource identifier is required.',422,'missing_identifier');
            $query->where('id',$target)->update(['deleted_at'=>now(),'updated_at'=>now()]);$this->emitFor($resource,['id'=>$target,'deleted'=>true],$space);return ApiResponse::ok(['deleted'=>true]);
        }
        $payload=$r->all();$action=$this->actionFromPath($r->path());
        if(in_array($r->method(),['PATCH','PUT'],true) && $target){$existing=(clone $query)->where('id',$target)->first();if(!$existing)return ApiResponse::problem('Not found','Resource does not exist.',404,'not_found');$old=json_decode($existing->data??'{}',true)?:[];$merged=array_replace_recursive($old,$payload);$query->where('id',$target)->update(['data'=>json_encode($merged),'status'=>$payload['status']??$existing->status,'title'=>$payload['title']??$existing->title,'body'=>$payload['body']??$existing->body,'updated_at'=>now()]);$row=DB::table($table)->where('id',$target)->first();$this->emitFor($resource,$this->decode($row),$space);return ApiResponse::ok($this->decode($row));}
        if($target && $action){$status=$this->statusForAction($action);$query->where('id',$target)->update(['status'=>$status,'completed_at'=>in_array($status,['completed','revealed','ended'],true)?now():null,'metadata'=>json_encode(['last_action'=>$action,'payload'=>$payload]),'updated_at'=>now()]);$row=DB::table($table)->where('id',$target)->first();$this->emitFor($resource,$this->decode($row),$space);return ApiResponse::ok($this->decode($row));}
        $id=(string)Str::uuid();$row=$this->row($r,['id'=>$id,'parent_id'=>$parent,'type'=>$payload['type']??$this->inferType($resource),'status'=>$payload['status']??'active','title'=>$payload['title']??null,'body'=>$payload['body']??null,'data'=>json_encode($payload),'metadata'=>json_encode(['operation'=>$op]),'scheduled_at'=>$payload['scheduled_at']??$payload['reveal_at']??null,'occurred_at'=>now()]);DB::table($table)->insert($row);$created=DB::table($table)->where('id',$id)->first();$this->emitFor($resource,$this->decode($created),$space);return ApiResponse::created($this->decode($created));
    }

    private function row(Request $r,array $overrides=[]): array
    {
        return array_merge(['id'=>(string)Str::uuid(),'user_id'=>$r->user()?->id,'couple_space_id'=>RequestContext::coupleSpaceId($r),'parent_id'=>null,'type'=>null,'status'=>'active','title'=>null,'body'=>null,'data'=>json_encode([]),'metadata'=>json_encode([]),'scheduled_at'=>null,'occurred_at'=>now(),'completed_at'=>null,'created_at'=>now(),'updated_at'=>now(),'deleted_at'=>null],$overrides);
    }

    private function targetAndParentIds(Request $r): array
    {
        $ids=[];foreach($r->route()->parameters() as $k=>$v)if(is_string($v)&&preg_match('/Id$/',$k))$ids[]=$v;
        return [count($ids)?$ids[count($ids)-1]:null,count($ids)>1?$ids[count($ids)-2]:null];
    }
    private function actionFromPath(string $path):?string{$last=basename($path);return str_contains($last,'{')?null:$last;}
    private function inferType(string $resource):string{return str_replace(['.','_'],['-','-'],$resource);}
    private function statusForAction(string $a):string{return match($a){'complete'=>'completed','read','read-all'=>'read','cancel'=>'cancelled','renew','restore','accept'=>'active','decline'=>'declined','end','leave'=>'ended','lock'=>'locked','reveal','unlock'=>'revealed','join'=>'joined','confirm'=>'confirmed','prepare'=>'prepared','start'=>'started','respond'=>'responded',default=>$a};}
    private function decode($row){if(!$row)return null;$x=(array)$row;foreach(['data','metadata'] as $k)if(isset($x[$k])&&is_string($x[$k]))$x[$k]=json_decode($x[$k],true)??$x[$k];return $x;}
    private function emitFor(string $resource,$payload,?string $space):void{$event=match(true){str_starts_with($resource,'calls')=>'call.updated',str_starts_with($resource,'together')=>'together.state.updated',str_starts_with($resource,'pings')=>'ping.received',$resource==='moods'=>'mood.updated',$resource==='need-you'=>'need_you.received',$resource==='calendar.events'=>'calendar.event.updated',$resource==='goals'=>'goal.progress.updated',$resource==='journey-sessions'=>'journey.progress.updated',default=>null};if($event)event(new DomainRealtimeEvent($event,(array)$payload,null,$space));}
}
