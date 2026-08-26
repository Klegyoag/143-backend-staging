<?php
namespace App\Models;
class LocationShare extends BaseUuidModel
{
    protected $table = 'location_shares';
    protected function casts(): array { return ['expires_at'=>'datetime', 'ended_at'=>'datetime', 'metadata'=>'array']; }
}
