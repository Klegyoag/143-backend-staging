<?php
namespace App\Models;
class Message extends BaseUuidModel
{
 protected $table='messages'; public $timestamps=true;
 protected function casts(): array{return ['payload'=>'array','edited_at'=>'datetime','deleted_at'=>'datetime'];}
 public function receipts(){return $this->hasMany(MessageReceipt::class);}
}
