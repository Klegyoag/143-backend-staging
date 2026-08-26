<?php
namespace App\Models;
class AuditLog extends BaseUuidModel
{
    protected $table = 'audit_logs';
    protected function casts(): array { return ['context'=>'array']; }
}
