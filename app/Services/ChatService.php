<?php
namespace App\Services;
use App\Events\DomainRealtimeEvent;
use App\Models\{Conversation,Message,MessageReaction,MessageReceipt};
use App\Support\{ApiResponse,RequestContext};
use Illuminate\Http\Request;
class ChatService
{
 public function handle(string $op,Request $r){return match($op){
 'get_conversations_couple'=>$this->conversation($r),'get_conversations_conversationid_messages'=>$this->index($r),'post_conversations_conversationid_messages'=>$this->store($r),
 'get_messages_messageid'=>$this->show($r),'patch_messages_messageid'=>$this->update($r),'delete_messages_messageid'=>$this->destroy($r),
 'post_messages_messageid_reactions'=>$this->react($r),'delete_messages_messageid_reactions_reaction'=>$this->unreact($r),'post_messages_messageid_read'=>$this->readOne($r),
 'post_conversations_conversationid_read'=>$this->readAll($r),'get_conversations_conversationid_unread_count'=>$this->unread($r),default=>ApiResponse::problem('Unsupported chat operation',$op,501,'not_implemented')};}
 private function conversation(Request $r){$sid=RequestContext::coupleSpaceId($r);if(!$sid)return ApiResponse::problem('No couple space','Chat is available after partner linking.',409,'no_couple_space');$c=Conversation::firstOrCreate(['couple_space_id'=>$sid,'type'=>'couple'],['created_by'=>$r->user()->id,'metadata'=>[]]);return ApiResponse::ok($c);}
 private function conv(Request $r){return Conversation::where('id',$r->route('conversationId'))->where('couple_space_id',RequestContext::coupleSpaceId($r))->firstOrFail();}
 private function msg(Request $r){return Message::where('id',$r->route('messageId'))->where('couple_space_id',RequestContext::coupleSpaceId($r))->firstOrFail();}
 private function index(Request $r){$c=$this->conv($r);return ApiResponse::ok(Message::where('conversation_id',$c->id)->whereNull('deleted_at')->latest()->cursorPaginate(min((int)$r->input('limit',50),100)));}
 private function store(Request $r){$c=$this->conv($r);$d=$r->validate(['type'=>'required|in:text,image,video,file,document,voice,location','body'=>'nullable|string|max:20000','payload'=>'nullable|array','reply_to_id'=>'nullable|uuid']);$m=Message::create(array_merge($d,['conversation_id'=>$c->id,'couple_space_id'=>$c->couple_space_id,'sender_id'=>$r->user()->id]));event(new DomainRealtimeEvent('chat.message.created',$m->toArray(),null,$c->couple_space_id));return ApiResponse::created($m);}
 private function show(Request $r){return ApiResponse::ok($this->msg($r));}
 private function update(Request $r){$m=$this->msg($r);if($m->sender_id!==$r->user()->id)return ApiResponse::problem('Forbidden','Only the sender can edit a message.',403,'forbidden');$d=$r->validate(['body'=>'nullable|string|max:20000','payload'=>'nullable|array']);$m->fill($d)->fill(['edited_at'=>now()])->save();event(new DomainRealtimeEvent('chat.message.updated',$m->toArray(),null,$m->couple_space_id));return ApiResponse::ok($m);}
 private function destroy(Request $r){$m=$this->msg($r);if($m->sender_id!==$r->user()->id)return ApiResponse::problem('Forbidden','Only the sender can delete a message.',403,'forbidden');$m->update(['deleted_at'=>now(),'body'=>null,'payload'=>[]]);event(new DomainRealtimeEvent('chat.message.deleted',['id'=>$m->id],null,$m->couple_space_id));return ApiResponse::ok(['deleted'=>true]);}
 private function react(Request $r){$m=$this->msg($r);$d=$r->validate(['reaction'=>'required|string|max:50']);$x=MessageReaction::updateOrCreate(['message_id'=>$m->id,'user_id'=>$r->user()->id],['reaction'=>$d['reaction']]);event(new DomainRealtimeEvent('chat.message.reaction.updated',$x->toArray(),null,$m->couple_space_id));return ApiResponse::ok($x);}
 private function unreact(Request $r){$m=$this->msg($r);MessageReaction::where('message_id',$m->id)->where('user_id',$r->user()->id)->where('reaction',$r->route('reaction'))->delete();return ApiResponse::ok(['deleted'=>true]);}
 private function readOne(Request $r){$m=$this->msg($r);$x=MessageReceipt::updateOrCreate(['message_id'=>$m->id,'user_id'=>$r->user()->id],['read_at'=>now()]);event(new DomainRealtimeEvent('chat.message.read',['message_id'=>$m->id,'user_id'=>$r->user()->id],null,$m->couple_space_id));return ApiResponse::ok($x);}
 private function readAll(Request $r){$c=$this->conv($r);$ids=Message::where('conversation_id',$c->id)->where('sender_id','!=',$r->user()->id)->pluck('id');foreach($ids as $id)MessageReceipt::updateOrCreate(['message_id'=>$id,'user_id'=>$r->user()->id],['read_at'=>now()]);return ApiResponse::ok(['read'=>count($ids)]);}
 private function unread(Request $r){$c=$this->conv($r);$count=Message::where('conversation_id',$c->id)->where('sender_id','!=',$r->user()->id)->whereNull('deleted_at')->whereDoesntHave('receipts',fn($q)=>$q->where('user_id',$r->user()->id)->whereNotNull('read_at'))->count();return ApiResponse::ok(['count'=>$count]);}
}
