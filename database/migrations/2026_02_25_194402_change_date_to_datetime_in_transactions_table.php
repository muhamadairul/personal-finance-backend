<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change date column from DATE to DATETIME using MySQL syntax
        DB::statement('ALTER TABLE transactions MODIFY COLUMN `date` DATETIME NULL');

        // Migrate existing data: set time from created_at
        DB::statement('UPDATE transactions SET `date` = CONCAT(DATE(`date`), \' \', TIME(created_at)) WHERE created_at IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE transactions MODIFY COLUMN `date` DATE NULL');
    }
};
