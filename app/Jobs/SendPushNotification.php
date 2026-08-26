<?php
namespace App\Jobs;

use App\Models\PushDevice;
use App\Services\FcmPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=3;
    public function __construct(public string $userId,public string $title,public string $body,public array $data=[]){ }
    public function handle(FcmPushService $fcm):void
    {
        foreach(PushDevice::where('user_id',$this->userId)->get() as $device){
            try{$fcm->send($device->token,$this->title,$this->body,$this->data);}
            catch(\Throwable $e){report($e);}
        }
    }
}
