<?php
namespace App\Models;
class Presence extends BaseUuidModel
{
    protected $table = 'presences';
    protected function casts(): array { return ['last_seen_at'=>'datetime', 'metadata'=>'array']; }
}
