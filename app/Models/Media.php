<?php
namespace App\Models;
class Media extends BaseUuidModel
{
    protected $table = 'media';
    protected function casts(): array { return ['metadata'=>'array']; }
}
