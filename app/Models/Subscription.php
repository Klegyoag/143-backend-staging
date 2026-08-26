<?php
namespace App\Models;
class Subscription extends BaseUuidModel
{
    protected $table = 'subscriptions';
    protected function casts(): array { return ['metadata'=>'array', 'current_period_end'=>'datetime', 'cancelled_at'=>'datetime']; }
}
