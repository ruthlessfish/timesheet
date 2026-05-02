<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // Create partial unique index for databases that support it (Postgres, SQLite)
        if (in_array($driver, ['pgsql', 'sqlite'])) {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uniq_active_timer_user ON time_entries (user_id) WHERE end_time IS NULL');
        }

        // MySQL does not support partial indexes in the same way; skip for MySQL.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'])) {
            DB::statement('DROP INDEX IF EXISTS uniq_active_timer_user');
        }
    }
};
