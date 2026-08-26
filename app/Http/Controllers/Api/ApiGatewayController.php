<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\{AuthService,BillingService,ChatService,CoupleService,DomainService,MediaService,MemoryService,NotificationService,ProfileService,SystemService};
use Illuminate\Http\Request;

class ApiGatewayController extends Controller
{
    public function handle(
        Request $request,
        AuthService $auth,
        CoupleService $couple,
        MemoryService $memory,
        ChatService $chat,
        NotificationService $notification,
        ProfileService $profile,
        MediaService $media,
        BillingService $billing,
        SystemService $system,
        DomainService $domain,
    ) {
        $op=$request->route('_operation');$handler=$request->route('_handler');$resource=$request->route('_resource');
        return match($handler){
            'auth'=>$auth->handle($op,$request),
            'couple'=>$couple->handle($op,$request),
            'memory'=>$memory->handle($op,$request),
            'chat'=>$chat->handle($op,$request),
            'notification'=>$notification->handle($op,$request),
            'profile'=>$profile->handle($op,$request),
            'media'=>$media->handle($op,$request),
            'billing'=>$billing->handle($op,$request),
            'system'=>$system->handle($op,$request),
            'domain'=>$domain->handle($op,$resource,$request),
            default=>throw new \LogicException("Unknown API handler: {$handler}"),
        };
    }
}
