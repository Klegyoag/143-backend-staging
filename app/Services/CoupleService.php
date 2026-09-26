<?php
namespace App\Services;
use App\Events\DomainRealtimeEvent;
use App\Models\{CoupleSpace,CoupleMember,CoupleInvite,RelationshipSetting,Presence};
use App\Support\{ApiResponse,RequestContext};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Log};
use Illuminate\Support\Str;
class CoupleService
{
    public function handle(string $op, Request $r)
    {
        return match($op){
            'post_couple_spaces'=>$this->create($r),'get_couple_spaces_current'=>$this->current($r),'post_couple_spaces_current_invites'=>$this->invite($r),
            'get_couple_invites_token_preview'=>$this->preview($r),'post_couple_invites_token_accept'=>$this->accept($r),
            'post_couple_invites_inviteid_revoke'=>$this->revoke($r),'post_couple_invites_inviteid_regenerate'=>$this->regenerate($r),
            'post_couple_spaces_join_code'=>$this->joinCode($r),'delete_couple_spaces_current_membership'=>$this->leave($r),
            'get_couple_spaces_current_relationship'=>$this->relationshipGet($r),'patch_couple_spaces_current_relationship'=>$this->relationshipPatch($r),
            'get_couple_spaces_current_settings'=>$this->settingsGet($r),'patch_couple_spaces_current_settings'=>$this->settingsPatch($r),
            'get_couple_spaces_current_presence'=>$this->presenceList($r),'put_presence'=>$this->presencePut($r),
            default=>ApiResponse::problem('Unsupported couple operation',$op,501,'not_implemented')};
    }
    private function create(Request $r){if(RequestContext::coupleSpaceId($r))return ApiResponse::problem('Already linked','Leave the current couple space before creating another.',409,'already_linked');$d=$r->validate(['name'=>'nullable|string|max:100']);return DB::transaction(function()use($r,$d){$s=CoupleSpace::create(['created_by'=>$r->user()->id,'name'=>$d['name']??null,'settings'=>[]]);CoupleMember::create(['couple_space_id'=>$s->id,'user_id'=>$r->user()->id,'role'=>'owner','joined_at'=>now()]);RelationshipSetting::create(['couple_space_id'=>$s->id,'settings'=>[]]);return ApiResponse::created($s);});}
    private function current(Request $r){$id=RequestContext::coupleSpaceId($r);if(!$id)return ApiResponse::ok(null);$s=CoupleSpace::find($id);$members=CoupleMember::where('couple_space_id',$id)->with('user:id,display_name,username,photo_path')->get();return ApiResponse::ok(['space'=>$s,'members'=>$members]);}
    private function invite(Request $r){$id=RequestContext::coupleSpaceId($r);if(!$id)return ApiResponse::problem('No couple space','Create a couple space first.',409,'no_couple_space');if(CoupleMember::where('couple_space_id',$id)->count()>=2)return ApiResponse::problem('Couple space full','A 143 couple space can contain at most two users.',409,'couple_space_full');$raw=Str::random(48);$code=strtoupper(Str::random(8));$i=CoupleInvite::create(['couple_space_id'=>$id,'created_by'=>$r->user()->id,'token_hash'=>hash('sha256',$raw),'join_code'=>$code,'expires_at'=>now()->addHours(config('143.invite_hours'))]);return ApiResponse::created(['invite_id'=>$i->id,'token'=>$raw,'join_code'=>$code,'deep_link'=>'143://invite/'.$raw,'expires_at'=>$i->expires_at]);}
    private function findToken(string $raw){return CoupleInvite::where('token_hash',hash('sha256',$raw))->whereNull('revoked_at')->whereNull('accepted_at')->where('expires_at','>',now())->first();}
    private function preview(Request $r){$i=$this->findToken($r->route('token'));if(!$i)return ApiResponse::problem('Invalid invite','Invite is invalid, expired, revoked, or already used.',404,'invalid_invite');return ApiResponse::ok(['invite_id'=>$i->id,'couple_space'=>CoupleSpace::find($i->couple_space_id),'expires_at'=>$i->expires_at]);}
    private function accept(Request $r){$i=$this->findToken($r->route('token'));if(!$i)return ApiResponse::problem('Invalid invite','Invite is invalid, expired, revoked, or already used.',404,'invalid_invite');return $this->join($r,$i);}
    private function joinCode(Request $r){$d=$r->validate(['code'=>'required|string']);$i=CoupleInvite::whereRaw('upper(join_code)=?', [strtoupper($d['code'])])->whereNull('revoked_at')->whereNull('accepted_at')->where('expires_at','>',now())->first();if(!$i)return ApiResponse::problem('Invalid code','Join code is invalid or expired.',404,'invalid_invite');return $this->join($r,$i);}
    private function join(Request $r,CoupleInvite $i)
    {
        if(RequestContext::coupleSpaceId($r))return ApiResponse::problem('Already linked','Leave the current couple space before joining another.',409,'already_linked');
        $result=DB::transaction(function()use($r,$i){
            // Lock the space, not COUNT(*): PostgreSQL rejects FOR UPDATE on aggregates.
            CoupleSpace::whereKey($i->couple_space_id)->lockForUpdate()->firstOrFail();
            $count=CoupleMember::where('couple_space_id',$i->couple_space_id)->count();
            if($count>=2)return ApiResponse::problem('Couple space full','This couple space already has two members.',409,'couple_space_full');
            CoupleMember::create(['couple_space_id'=>$i->couple_space_id,'user_id'=>$r->user()->id,'role'=>'partner','joined_at'=>now()]);
            $i->update(['accepted_at'=>now(),'accepted_by'=>$r->user()->id]);
            return ApiResponse::ok(['joined'=>true,'couple_space_id'=>$i->couple_space_id]);
        });
        if($result->getStatusCode()===200){
            try{event(new DomainRealtimeEvent('couple.invite.accepted',['user_id'=>$r->user()->id],null,$i->couple_space_id));}
            catch(\Throwable $error){Log::warning('Couple invite realtime delivery unavailable',['invite_id'=>$i->id,'error'=>$error->getMessage()]);}
        }
        return $result;
    }
    private function revoke(Request $r){$i=CoupleInvite::where('id',$r->route('inviteId'))->where('couple_space_id',RequestContext::coupleSpaceId($r))->firstOrFail();$i->update(['revoked_at'=>now()]);return ApiResponse::ok(['revoked'=>true]);}
    private function regenerate(Request $r){$this->revoke($r);return $this->invite($r);}
    private function leave(Request $r){$id=RequestContext::coupleSpaceId($r);if($id)CoupleMember::where('couple_space_id',$id)->where('user_id',$r->user()->id)->delete();return ApiResponse::ok(['left'=>true]);}
    private function relationshipGet(Request $r){$id=RequestContext::coupleSpaceId($r);return ApiResponse::ok($id?RelationshipSetting::firstOrCreate(['couple_space_id'=>$id],['settings'=>[]]):null);}
    private function relationshipPatch(Request $r){$id=RequestContext::coupleSpaceId($r);if(!$id)return ApiResponse::problem('No couple space','Link a partner first.',409,'no_couple_space');$d=$r->validate(['anniversary_date'=>'nullable|date','absence_mode'=>'nullable|boolean','distance_status'=>'nullable|string|max:50','settings'=>'nullable|array']);$row=RelationshipSetting::firstOrCreate(['couple_space_id'=>$id],['settings'=>[]]);$row->fill($d)->save();event(new DomainRealtimeEvent('couple.relationship.updated',$row->toArray(),null,$id));return ApiResponse::ok($row);}
    private function settingsGet(Request $r){$id=RequestContext::coupleSpaceId($r);return ApiResponse::ok($id?CoupleSpace::find($id)?->settings:null);}
    private function settingsPatch(Request $r){$id=RequestContext::coupleSpaceId($r);$d=$r->validate(['settings'=>'required|array']);$s=CoupleSpace::findOrFail($id);$s->update(['settings'=>array_replace_recursive($s->settings?:[],$d['settings'])]);return ApiResponse::ok($s->settings);}
    private function presenceList(Request $r){$id=RequestContext::coupleSpaceId($r);$uids=CoupleMember::where('couple_space_id',$id)->pluck('user_id');return ApiResponse::ok(Presence::whereIn('user_id',$uids)->get());}
    private function presencePut(Request $r){$d=$r->validate(['status'=>'required|string|max:50','metadata'=>'nullable|array']);$id=RequestContext::coupleSpaceId($r);$p=Presence::updateOrCreate(['user_id'=>$r->user()->id],['status'=>$d['status'],'metadata'=>$d['metadata']??[],'last_seen_at'=>now()]);event(new DomainRealtimeEvent('presence.updated',$p->toArray(),null,$id));return ApiResponse::ok($p);}
}
