<?php
namespace App\Models;
class IdempotencyKey extends BaseUuidModel
{
    protected $table = 'idempotency_keys';
    protected function casts(): array { return ['response_headers'=>'array', 'expires_at'=>'datetime']; }
}
