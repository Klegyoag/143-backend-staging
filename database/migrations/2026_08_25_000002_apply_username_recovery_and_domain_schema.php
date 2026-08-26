<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestampTz('recovery_confirmed_at')->nullable()->after('password');
            $table->string('account_state', 40)->default('recovery_setup_pending')->index()->after('recovery_confirmed_at');
        });

        if (Schema::hasTable('otp_challenges')) Schema::drop('otp_challenges');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email','phone','email_verified_at','phone_verified_at']);
        });

        // Username-only authentication is the approved 143 baseline override.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE users SET username = lower(username) WHERE username IS NOT NULL");
            DB::statement("ALTER TABLE users ALTER COLUMN username SET NOT NULL");
        }

        $domainTables = require config_path('domain_tables.php');
        foreach ($domainTables as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable()->index();
                $table->uuid('couple_space_id')->nullable()->index();
                $table->uuid('parent_id')->nullable()->index();
                $table->string('type', 80)->nullable()->index();
                $table->string('status', 40)->default('active')->index();
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->jsonb('data')->nullable();
                $table->jsonb('metadata')->nullable();
                $table->timestampTz('scheduled_at')->nullable()->index();
                $table->timestampTz('occurred_at')->nullable()->index();
                $table->timestampTz('completed_at')->nullable();
                $table->timestampsTz();
                $table->softDeletesTz();
                $table->index(['couple_space_id','created_at']);
                $table->index(['user_id','created_at']);

                if (in_array($name, ['daily_answers','game_answers','achievement_unlocks','journey_step_progress'], true)) {
                    $table->unique(['user_id','parent_id','type']);
                }
                if (in_array($name, ['notification_preferences','long_distance_settings','love_languages','partner_preferences','couple_music','widget_settings','ai_settings'], true)) {
                    $table->unique(['user_id','couple_space_id']);
                }
                if ($name === 'linked_devices') $table->unique(['user_id','type']);
            });
        }
    }

    public function down(): void
    {
        $domainTables = require config_path('domain_tables.php');
        foreach (array_reverse($domainTables) as $name) Schema::dropIfExists($name);
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique();
            $table->string('phone',32)->nullable()->unique();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('phone_verified_at')->nullable();
            $table->dropColumn(['recovery_confirmed_at','account_state']);
        });
    }
};
