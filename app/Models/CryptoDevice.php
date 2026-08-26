<?php
namespace App\Models;
class CryptoDevice extends BaseUuidModel
{
    protected $table = 'crypto_devices';
    protected function casts(): array { return ['signed_prekey'=>'array', 'identity_key'=>'array']; }
}
