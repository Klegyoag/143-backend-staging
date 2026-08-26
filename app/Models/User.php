<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;
    protected $guarded=[];
    protected $hidden=['password'];
    protected function casts(): array { return ['recovery_confirmed_at'=>'datetime','preferences'=>'array','security_settings'=>'array','privacy_permissions'=>'array','password'=>'hashed']; }
}
