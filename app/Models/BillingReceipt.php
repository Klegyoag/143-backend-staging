<?php
namespace App\Models;
class BillingReceipt extends BaseUuidModel
{
    protected $table = 'billing_receipts';
    protected function casts(): array { return ['payload'=>'array']; }
}
