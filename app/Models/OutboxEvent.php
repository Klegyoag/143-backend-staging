<?php
namespace App\Models;
class OutboxEvent extends BaseUuidModel
{
    protected $table = 'outbox_events';
    protected function casts(): array { return ['payload'=>'array', 'published_at'=>'datetime']; }
}
