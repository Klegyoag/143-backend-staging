<?php
namespace App\Models;
class MemoryComment extends BaseUuidModel
{
    protected $table = 'memory_comments';
    protected function casts(): array { return []; }
}
