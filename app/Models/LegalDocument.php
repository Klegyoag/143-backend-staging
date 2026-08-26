<?php
namespace App\Models;
class LegalDocument extends BaseUuidModel
{
    protected $table = 'legal_documents';
    protected function casts(): array { return ['effective_at'=>'datetime', 'is_active'=>'boolean']; }
}
