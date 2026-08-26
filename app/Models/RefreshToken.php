<?php
namespace App\Models;
class RefreshToken extends BaseUuidModel
{
    protected $table = 'refresh_tokens';
    protected function casts(): array { return ['expires_at'=>'datetime', 'revoked_at'=>'datetime']; }
}
