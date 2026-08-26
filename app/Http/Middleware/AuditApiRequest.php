<?php
namespace App\Http\Middleware;
use App\Models\AuditLog;
use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class AuditApiRequest
{
 public function handle(Request $request,Closure $next):Response
 {
   $response=$next($request);
   if($request->is('api/*')&&in_array($request->method(),['POST','PUT','PATCH','DELETE'],true)&&$request->user()){
     try{AuditLog::create(['user_id'=>$request->user()->id,'couple_space_id'=>RequestContext::coupleSpaceId($request),'action'=>$request->route('_operation')?:$request->method().' '.$request->path(),'subject_type'=>$request->route('_resource'),'subject_id'=>RequestContext::routeId($request),'context'=>['status'=>$response->getStatusCode(),'request_id'=>$request->header('X-Request-ID'),'ip_hash'=>hash('sha256',(string)$request->ip()),'user_agent'=>substr((string)$request->userAgent(),0,255)]]);}catch(\Throwable $e){report($e);}
   }
   return $response;
 }
}
