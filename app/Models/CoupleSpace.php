<?php
namespace App\Models;
class CoupleSpace extends BaseUuidModel
{
    protected $table = 'couple_spaces';
    protected function casts(): array { return ['settings'=>'array']; }
}
