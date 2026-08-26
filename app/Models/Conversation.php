<?php
namespace App\Models;
class Conversation extends BaseUuidModel
{
    protected $table = 'conversations';
    protected function casts(): array { return ['metadata'=>'array']; }
}
