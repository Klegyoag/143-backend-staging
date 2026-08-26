<?php
namespace App\Models;
class Backup extends BaseUuidModel
{
    protected $table = 'backups';
    protected function casts(): array { return ['metadata'=>'array', 'completed_at'=>'datetime']; }
}
