<?php
namespace App\Models;
class MessageReceipt extends BaseUuidModel
{
    protected $table = 'message_receipts';
    protected function casts(): array { return ['read_at'=>'datetime']; }
}
