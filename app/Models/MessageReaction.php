<?php
namespace App\Models;
class MessageReaction extends BaseUuidModel
{
    protected $table = 'message_reactions';
    protected function casts(): array { return []; }
}
