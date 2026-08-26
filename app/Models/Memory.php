<?php
namespace App\Models;
class Memory extends BaseUuidModel
{
    protected $table = 'memories';
    protected function casts(): array { return ['content'=>'array', 'tags'=>'array', 'reveal_at'=>'datetime', 'revealed_at'=>'datetime', 'is_draft'=>'boolean', 'is_locked'=>'boolean']; }
}
