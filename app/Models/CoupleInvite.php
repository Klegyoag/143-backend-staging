<?php
namespace App\Models;
class CoupleInvite extends BaseUuidModel
{
    protected $table = 'couple_invites';
    protected function casts(): array { return ['expires_at'=>'datetime', 'revoked_at'=>'datetime', 'accepted_at'=>'datetime']; }
}
