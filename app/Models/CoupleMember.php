<?php
namespace App\Models;
class CoupleMember extends BaseUuidModel
{
    protected $table='couple_members';
    protected function casts(): array { return ['joined_at'=>'datetime']; }
    public function user(){ return $this->belongsTo(User::class); }
}
