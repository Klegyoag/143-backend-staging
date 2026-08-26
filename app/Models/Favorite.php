<?php
namespace App\Models;
class Favorite extends BaseUuidModel
{
    protected $table = 'favorites';
    protected function casts(): array { return ['metadata'=>'array']; }
}
