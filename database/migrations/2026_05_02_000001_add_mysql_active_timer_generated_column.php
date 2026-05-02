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

        if ($driver === 'mysql') {
            // Create a generated column that equals user_id when end_time IS NULL, else NULL.
            // Then add a unique index on that column. Multiple NULLs are allowed in MySQL unique indexes,
            // so this enforces at most one active (end_time IS NULL) row per user.
            DB::statement(<<<'SQL'
                ALTER TABLE `time_entries`
                ADD COLUMN `active_user_key` INT GENERATED ALWAYS AS (CASE WHEN `end_time` IS NULL THEN `user_id` ELSE NULL END) STORED,
                ADD UNIQUE INDEX `uniq_active_timer_user` (`active_user_key`);
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `time_entries` DROP INDEX `uniq_active_timer_user`');
            DB::statement('ALTER TABLE `time_entries` DROP COLUMN `active_user_key`');
        }
    }
};
