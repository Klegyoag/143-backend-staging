<?php
namespace App\Models;
class Notification extends BaseUuidModel
{
    protected $table = 'notifications';
    protected function casts(): array { return ['data'=>'array', 'read_at'=>'datetime']; }
}
