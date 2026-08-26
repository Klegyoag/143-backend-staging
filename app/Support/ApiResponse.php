<?php
namespace App\Support;
use Illuminate\Http\JsonResponse;
class ApiResponse
{
    public static function ok(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json(['ok'=>true,'data'=>$data,'meta'=>(object)$meta,'trace_id'=>request()->header('X-Request-ID')], $status);
    }
    public static function created(mixed $data = null): JsonResponse { return self::ok($data, [], 201); }
    public static function problem(string $title, string $detail, int $status, string $code='api_error', array $extra=[]): JsonResponse
    {
        return response()->json(array_merge(['type'=>'https://143.app/problems/'.$code,'title'=>$title,'status'=>$status,'detail'=>$detail,'code'=>$code,'trace_id'=>request()->header('X-Request-ID')],$extra), $status, ['Content-Type'=>'application/problem+json']);
    }
}
