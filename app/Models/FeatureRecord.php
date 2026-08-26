<?php
namespace App\Models;
class FeatureRecord extends BaseUuidModel
{
    protected $table = 'feature_records';
    protected function casts(): array { return ['data'=>'array', 'metadata'=>'array', 'occurred_at'=>'datetime']; }
}
