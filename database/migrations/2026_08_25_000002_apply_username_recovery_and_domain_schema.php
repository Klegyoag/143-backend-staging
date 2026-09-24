<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->migratePostgres();

            return;
        }

        $this->migratePortableDatabase();
    }

    /**
     * Supabase's pooler adds noticeable latency to every schema statement. Build
     * the domain schema as one PostgreSQL request so first deployment completes
     * within the host's health-check window. Every operation is restart-safe.
     */
    private function migratePostgres(): void
    {
        $statements = [
            'ALTER TABLE users ADD COLUMN IF NOT EXISTS recovery_confirmed_at TIMESTAMPTZ NULL',
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS account_state VARCHAR(40) NOT NULL DEFAULT 'recovery_setup_pending'",
            'CREATE INDEX IF NOT EXISTS users_account_state_index ON users (account_state)',
            'DROP TABLE IF EXISTS otp_challenges',
            'ALTER TABLE users DROP COLUMN IF EXISTS email',
            'ALTER TABLE users DROP COLUMN IF EXISTS phone',
            'ALTER TABLE users DROP COLUMN IF EXISTS email_verified_at',
            'ALTER TABLE users DROP COLUMN IF EXISTS phone_verified_at',
            "UPDATE users SET username = lower(COALESCE(username, 'user_' || replace(id::text, '-', '')))",
            'ALTER TABLE users ALTER COLUMN username SET NOT NULL',
        ];

        $domainTables = require config_path('domain_tables.php');
        foreach ($domainTables as $table) {
            if (! preg_match('/^[a-z][a-z0-9_]*$/', $table)) {
                throw new RuntimeException("Unsafe domain table name: {$table}");
            }

            $statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS "{$table}" (
    id UUID PRIMARY KEY,
    user_id UUID NULL,
    couple_space_id UUID NULL,
    parent_id UUID NULL,
    type VARCHAR(80) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'active',
    title VARCHAR(255) NULL,
    body TEXT NULL,
    data JSONB NULL,
    metadata JSONB NULL,
    scheduled_at TIMESTAMPTZ NULL,
    occurred_at TIMESTAMPTZ NULL,
    completed_at TIMESTAMPTZ NULL,
    created_at TIMESTAMPTZ NULL,
    updated_at TIMESTAMPTZ NULL,
    deleted_at TIMESTAMPTZ NULL
)
SQL;

            foreach (['user_id', 'couple_space_id', 'parent_id', 'type', 'status', 'scheduled_at', 'occurred_at'] as $column) {
                $statements[] = $this->postgresIndex($table, [$column]);
            }
            $statements[] = $this->postgresIndex($table, ['couple_space_id', 'created_at']);
            $statements[] = $this->postgresIndex($table, ['user_id', 'created_at']);

            if (in_array($table, ['daily_answers', 'game_answers', 'achievement_unlocks', 'journey_step_progress'], true)) {
                $statements[] = $this->postgresIndex($table, ['user_id', 'parent_id', 'type'], true);
            }
            if (in_array($table, ['notification_preferences', 'long_distance_settings', 'love_languages', 'partner_preferences', 'couple_music', 'widget_settings', 'ai_settings'], true)) {
                $statements[] = $this->postgresIndex($table, ['user_id', 'couple_space_id'], true);
            }
            if ($table === 'linked_devices') {
                $statements[] = $this->postgresIndex($table, ['user_id', 'type'], true);
            }
        }

        DB::unprepared(implode(";\n", $statements).';');
    }

    private function postgresIndex(string $table, array $columns, bool $unique = false): string
    {
        $signature = $table.'_'.implode('_', $columns).($unique ? '_unique' : '_index');
        $name = 'domain_'.substr(hash('sha256', $signature), 0, 24);
        $columnList = implode(', ', array_map(static fn (string $column): string => '"'.$column.'"', $columns));
        $kind = $unique ? 'UNIQUE ' : '';

        return "CREATE {$kind}INDEX IF NOT EXISTS \"{$name}\" ON \"{$table}\" ({$columnList})";
    }

    private function migratePortableDatabase(): void
    {
        if (! Schema::hasColumn('users', 'recovery_confirmed_at') || ! Schema::hasColumn('users', 'account_state')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'recovery_confirmed_at')) {
                    $table->timestampTz('recovery_confirmed_at')->nullable();
                }
                if (! Schema::hasColumn('users', 'account_state')) {
                    $table->string('account_state', 40)->default('recovery_setup_pending')->index();
                }
            });
        }

        Schema::dropIfExists('otp_challenges');
        $columnsToDrop = array_values(array_filter(
            ['email', 'phone', 'email_verified_at', 'phone_verified_at'],
            static fn (string $column): bool => Schema::hasColumn('users', $column),
        ));
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('users', function (Blueprint $table) use ($columnsToDrop) {
                if (in_array('email', $columnsToDrop, true)) {
                    $table->dropUnique(['email']);
                }
                if (in_array('phone', $columnsToDrop, true)) {
                    $table->dropUnique(['phone']);
                }
            });
        }
        if ($columnsToDrop !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($columnsToDrop));
        }

        foreach (require config_path('domain_tables.php') as $name) {
            if (Schema::hasTable($name)) {
                continue;
            }

            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable()->index();
                $table->uuid('couple_space_id')->nullable()->index();
                $table->uuid('parent_id')->nullable()->index();
                $table->string('type', 80)->nullable()->index();
                $table->string('status', 40)->default('active')->index();
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->json('data')->nullable();
                $table->json('metadata')->nullable();
                $table->timestampTz('scheduled_at')->nullable()->index();
                $table->timestampTz('occurred_at')->nullable()->index();
                $table->timestampTz('completed_at')->nullable();
                $table->timestampsTz();
                $table->softDeletesTz();
                $table->index(['couple_space_id', 'created_at']);
                $table->index(['user_id', 'created_at']);

                if (in_array($name, ['daily_answers', 'game_answers', 'achievement_unlocks', 'journey_step_progress'], true)) {
                    $table->unique(['user_id', 'parent_id', 'type']);
                }
                if (in_array($name, ['notification_preferences', 'long_distance_settings', 'love_languages', 'partner_preferences', 'couple_music', 'widget_settings', 'ai_settings'], true)) {
                    $table->unique(['user_id', 'couple_space_id']);
                }
                if ($name === 'linked_devices') {
                    $table->unique(['user_id', 'type']);
                }
            });
        }
    }

    public function down(): void
    {
        $domainTables = require config_path('domain_tables.php');
        foreach (array_reverse($domainTables) as $name) {
            Schema::dropIfExists($name);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique();
            $table->string('phone', 32)->nullable()->unique();
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('phone_verified_at')->nullable();
            $table->dropColumn(['recovery_confirmed_at', 'account_state']);
        });
    }
};
