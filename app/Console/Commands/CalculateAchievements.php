<?php
namespace App\Console\Commands;

use App\Events\DomainRealtimeEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CalculateAchievements extends Command
{
    protected $signature='143:achievements';
    protected $description='Calculate trusted server-side couple achievements.';

    public function handle(): int
    {
        DB::table('couple_spaces')->orderBy('id')->chunkById(100,function($spaces){foreach($spaces as $space)$this->calculate($space->id);});
        return self::SUCCESS;
    }
    private function calculate(string $space): void
    {
        $rules=[
            'first-memory'=>DB::table('memories')->where('couple_space_id',$space)->whereNull('deleted_at')->count()>=1,
            '100-pings'=>DB::table('pings')->where('couple_space_id',$space)->whereNull('deleted_at')->count()>=100,
            '100-memories'=>DB::table('memories')->where('couple_space_id',$space)->whereNull('deleted_at')->count()>=100,
            'game-night'=>DB::table('game_sessions')->where('couple_space_id',$space)->where('status','completed')->whereNull('deleted_at')->count()>=1,
            'first-trip'=>DB::table('travel_itineraries')->where('couple_space_id',$space)->whereNull('deleted_at')->count()>=1,
            '50-love-notes'=>DB::table('memories')->where('couple_space_id',$space)->where('type','love_note')->whereNull('deleted_at')->count()>=50,
            'shared-goal'=>DB::table('goals')->where('couple_space_id',$space)->where('status','completed')->whereNull('deleted_at')->count()>=1,
        ];
        foreach($rules as $key=>$passed){if(!$passed)continue;$exists=DB::table('achievement_unlocks')->where('couple_space_id',$space)->where('type',$key)->whereNull('deleted_at')->exists();if($exists)continue;DB::table('achievement_unlocks')->insert(['id'=>(string)Str::uuid(),'user_id'=>null,'couple_space_id'=>$space,'parent_id'=>null,'type'=>$key,'status'=>'unlocked','title'=>null,'body'=>null,'data'=>json_encode(['unlocked_at'=>now()->toIso8601String()]),'metadata'=>json_encode([]),'scheduled_at'=>null,'occurred_at'=>now(),'completed_at'=>now(),'created_at'=>now(),'updated_at'=>now(),'deleted_at'=>null]);event(new DomainRealtimeEvent('achievement.unlocked',['key'=>$key],null,$space));}
    }
}
