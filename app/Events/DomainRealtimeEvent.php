<?php
namespace App\Events;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
class DomainRealtimeEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;
    public function __construct(public string $eventName, public array $payload, public ?string $userId=null, public ?string $coupleSpaceId=null) {}
    public function broadcastOn(): array
    {
        $channels=[];
        if($this->userId) $channels[]=new PrivateChannel('user.'.$this->userId);
        if($this->coupleSpaceId) $channels[]=new PrivateChannel('couple.'.$this->coupleSpaceId);
        return $channels;
    }
    public function broadcastAs(): string { return $this->eventName; }
    public function broadcastWith(): array { return $this->payload; }
}
