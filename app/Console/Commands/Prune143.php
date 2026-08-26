<?php
namespace App\Console\Commands;
use App\Models\{IdempotencyKey,RefreshToken};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class Prune143 extends Command
{
 protected $signature='143:prune'; protected $description='Prune expired transient security, session, and realtime records.';
 public function handle():int
 {
   IdempotencyKey::where('expires_at','<',now())->delete();
   RefreshToken::where('expires_at','<',now())->delete();
   DB::table('device_link_intents')->where('completed_at','<',now()->subDays(7))->delete();
   DB::table('presences')->where('last_seen_at','<',now()->subDays(30))->update(['status'=>'offline','metadata'=>json_encode([]),'updated_at'=>now()]);
   return self::SUCCESS;
 }
}
