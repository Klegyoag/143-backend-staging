<?php
namespace App\Models;
class CryptoPrekey extends BaseUuidModel
{
    protected $table = 'crypto_prekeys';
    protected function casts(): array { return ['key_payload'=>'array', 'used_at'=>'datetime']; }
}
