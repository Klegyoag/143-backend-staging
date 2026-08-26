<?php
namespace App\Models;
class RecoveryCode extends BaseUuidModel
{
    protected $table = 'recovery_codes';
    protected function casts(): array { return ['used_at'=>'datetime']; }
}
