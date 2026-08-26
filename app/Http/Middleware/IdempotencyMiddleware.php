<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\IdempotencyKey;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key=$request->header('Idempotency-Key');
        if (!$key || !in_array($request->method(),['POST','PUT','PATCH','DELETE'],true)) return $next($request);
        $userId=optional($request->user())->id;
        $hash=hash('sha256', $request->method().'|'.$request->path().'|'.$request->getContent());
        $existing=IdempotencyKey::where('key',$key)->where('user_id',$userId)->first();
        if ($existing) {
            if (!hash_equals($existing->request_hash,$hash)) return response()->json(['type'=>'https://143.app/problems/idempotency-conflict','title'=>'Idempotency conflict','status'=>409,'detail'=>'This Idempotency-Key was already used with a different request.'],409,['Content-Type'=>'application/problem+json']);
            return response($existing->response_body,$existing->response_status, array_merge(['Content-Type'=>'application/json'], $existing->response_headers ?: []));
        }
        $response=$next($request);
        if ($response->getStatusCode()<500) IdempotencyKey::create(['key'=>$key,'user_id'=>$userId,'request_hash'=>$hash,'response_status'=>$response->getStatusCode(),'response_body'=>$response->getContent(),'response_headers'=>[],'expires_at'=>now()->addDay()]);
        return $response;
    }
}
