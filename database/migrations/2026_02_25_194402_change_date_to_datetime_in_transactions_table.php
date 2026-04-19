<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change date column from DATE to DATETIME using MySQL syntax
        DB::statement('ALTER TABLE transactions MODIFY COLUMN date DATETIME');
        
        // Migrate existing data: set time from created_at in MySQL
        DB::statement("UPDATE transactions SET date = CAST(CONCAT(DATE(date), ' ', TIME(created_at)) AS DATETIME) WHERE created_at IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE transactions MODIFY COLUMN date DATE');
    }
};
