<?php
namespace App\Models;
class UserConsent extends BaseUuidModel
{
 protected $table='user_consents'; protected function casts(): array{return ['accepted_at'=>'datetime','revoked_at'=>'datetime','metadata'=>'array'];}
 public function document(){return $this->belongsTo(LegalDocument::class,'legal_document_id');}
}
