<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmPushService
{
    private function serviceAccount(): array
    {
        $raw=env('FCM_SERVICE_ACCOUNT_JSON');
        if(!$raw)throw new RuntimeException('FCM_SERVICE_ACCOUNT_JSON is not configured.');
        if(!str_starts_with(trim($raw),'{'))$raw=base64_decode($raw,true)?:$raw;
        $json=json_decode($raw,true);
        if(!is_array($json)||empty($json['client_email'])||empty($json['private_key']))throw new RuntimeException('Invalid FCM service account JSON.');
        return $json;
    }
    private function base64url(string $data):string{return rtrim(strtr(base64_encode($data),'+/','-_'),'=');}
    private function accessToken(): string
    {
        $sa=$this->serviceAccount();$now=time();$header=$this->base64url(json_encode(['alg'=>'RS256','typ'=>'JWT']));$claims=$this->base64url(json_encode(['iss'=>$sa['client_email'],'scope'=>'https://www.googleapis.com/auth/firebase.messaging','aud'=>$sa['token_uri']??'https://oauth2.googleapis.com/token','iat'=>$now,'exp'=>$now+3600]));$input=$header.'.'.$claims;
        if(!openssl_sign($input,$signature,$sa['private_key'],OPENSSL_ALGO_SHA256))throw new RuntimeException('Unable to sign FCM OAuth assertion.');
        $jwt=$input.'.'.$this->base64url($signature);
        $resp=Http::asForm()->timeout(15)->post($sa['token_uri']??'https://oauth2.googleapis.com/token',['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt]);
        if(!$resp->successful()||!$resp->json('access_token'))throw new RuntimeException('Unable to obtain FCM OAuth token.');
        return $resp->json('access_token');
    }
    public function send(string $deviceToken,string $title,string $body,array $data=[]): array
    {
        $project=env('FCM_PROJECT_ID');if(!$project)throw new RuntimeException('FCM_PROJECT_ID is not configured.');
        $payload=['message'=>['token'=>$deviceToken,'notification'=>['title'=>$title,'body'=>$body],'data'=>collect($data)->mapWithKeys(fn($v,$k)=>[$k=>is_scalar($v)?(string)$v:json_encode($v)])->all(),'android'=>['priority'=>'high'],'apns'=>['headers'=>['apns-priority'=>'10'],'payload'=>['aps'=>['sound'=>'default']]]]];
        $resp=Http::withToken($this->accessToken())->acceptJson()->timeout(15)->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send",$payload);
        if(!$resp->successful())throw new RuntimeException('FCM send failed with status '.$resp->status());
        return $resp->json();
    }
}
