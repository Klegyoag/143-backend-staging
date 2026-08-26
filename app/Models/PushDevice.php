<?php
namespace App\Models;
class PushDevice extends BaseUuidModel
{
    protected $table = 'push_devices';
    protected function casts(): array { return ['metadata'=>'array']; }
}
