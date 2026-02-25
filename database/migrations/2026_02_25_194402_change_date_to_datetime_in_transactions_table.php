<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change date column from DATE to TIMESTAMP using PostgreSQL syntax
        DB::statement('ALTER TABLE transactions ALTER COLUMN date TYPE TIMESTAMP USING date::timestamp');

        // Migrate existing data: set time from created_at
        DB::statement('UPDATE transactions SET date = (date::date || \' \' || created_at::time)::timestamp WHERE created_at IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE transactions ALTER COLUMN date TYPE DATE USING date::date');
    }
};
