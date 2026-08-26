<?php
use App\Models\CoupleMember;
use Illuminate\Support\Facades\Broadcast;
Broadcast::channel('user.{userId}', fn ($user, string $userId) => (string)$user->id === $userId);
Broadcast::channel('couple.{coupleSpaceId}', fn ($user, string $coupleSpaceId) => CoupleMember::where('couple_space_id',$coupleSpaceId)->where('user_id',$user->id)->exists());
