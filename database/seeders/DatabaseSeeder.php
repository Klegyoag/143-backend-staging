<?php
namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $legal = [
            'terms' => resource_path('legal/terms-v1.0.md'),
            'privacy' => resource_path('legal/privacy-v1.0.md'),
        ];
        foreach($legal as $type=>$file){
            LegalDocument::updateOrCreate(['type'=>$type,'version'=>'1.0'],['content'=>file_get_contents($file),'effective_at'=>'2026-08-26 00:00:00+00','is_active'=>true]);
        }

        $games=[
            'how-well-do-you-know-me'=>'How Well Do You Know Me','this-or-that'=>'This or That','would-you-rather'=>'Would You Rather',
            'who-is-more-likely'=>'Who Is More Likely To','guess-my-answer'=>'Guess My Answer','couple-trivia'=>'Couple Trivia','memory-quiz'=>'Memory Quiz',
            'finish-my-sentence'=>'Finish My Sentence','relationship-bingo'=>'Relationship Bingo','rapid-fire'=>'Rapid Fire','emoji-guess'=>'Emoji Guess',
            'two-truths-and-a-lie'=>'Two Truths and a Lie','compatibility-challenge'=>'Compatibility Challenge'
        ];
        foreach($games as $key=>$title)$this->catalog('games',$key,$title);

        $achievements=[
            'first-memory'=>'First Memory','100-pings'=>'100 Pings','30-day-streak'=>'30-Day Streak','100-memories'=>'100 Memories',
            'game-night'=>'Game Night','first-trip'=>'First Trip','50-love-notes'=>'50 Love Notes','anniversary'=>'Anniversary','shared-goal'=>'Shared Goal'
        ];
        foreach($achievements as $key=>$title)$this->catalog('achievements',$key,$title);

        $journeys=[
            '7-days-appreciation'=>'7 Days of Appreciation','reconnect'=>'Reconnect','long-distance'=>'Long-Distance','communication'=>'Communication',
            'new-relationship'=>'New Relationship','anniversary-week'=>'Anniversary Week','30-day-couple-challenge'=>'30-Day Couple Challenge'
        ];
        foreach($journeys as $key=>$title)$this->catalog('journeys',$key,$title);

        foreach([
            ['Privacy & Security','Learn how 143 protects couple data and how to revoke linked devices.'],
            ['Recovery Code','Your recovery code is shown once. Keep it private and safe.'],
            ['Location Sharing','Location sharing is optional, time-limited where configured, and can be stopped at any time.'],
        ] as [$title,$body]){
            DB::table('feature_records')->updateOrInsert(['resource'=>'help.articles','status'=>Str::slug($title)],['id'=>(string)Str::uuid(),'user_id'=>null,'couple_space_id'=>null,'parent_id'=>null,'data'=>json_encode(['title'=>$title,'body'=>$body]),'metadata'=>json_encode([]),'occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }
        DB::table('feature_records')->updateOrInsert(['resource'=>'release-notes','status'=>'1.0.0'],['id'=>(string)Str::uuid(),'data'=>json_encode(['version'=>'1.0.0','title'=>'143 Backend Baseline','items'=>['Username/password + recovery-code auth','Private couple spaces','Realtime/event contract','R2 media architecture','OpenAI opt-in architecture']]),'metadata'=>json_encode([]),'occurred_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
    }

    private function catalog(string $table,string $key,string $title): void
    {
        DB::table($table)->updateOrInsert(['type'=>$key,'user_id'=>null,'couple_space_id'=>null],['id'=>(string)Str::uuid(),'parent_id'=>null,'status'=>'active','title'=>$title,'body'=>null,'data'=>json_encode(['key'=>$key,'enabled'=>true]),'metadata'=>json_encode([]),'scheduled_at'=>null,'occurred_at'=>now(),'completed_at'=>null,'created_at'=>now(),'updated_at'=>now(),'deleted_at'=>null]);
    }
}
