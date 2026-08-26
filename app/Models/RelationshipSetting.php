<?php
namespace App\Models;
class RelationshipSetting extends BaseUuidModel
{
    protected $table = 'relationship_settings';
    protected function casts(): array { return ['anniversary_date'=>'date', 'settings'=>'array']; }
}
