<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncPostgresSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync-sequences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all PostgreSQL sequences with the table data to prevent duplicate key violations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('database.default') !== 'pgsql') {
            $this->error('This command is only for PostgreSQL database.');
            return 1;
        }

        $tables = DB::select("SELECT table_name 
                            FROM information_schema.tables 
                            WHERE table_schema = 'public'");

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            // Get the primary key column (usually 'id')
            $pk = DB::select("SELECT a.attname
                             FROM pg_index i
                             JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
                             WHERE i.indrelid = '{$tableName}'::regclass
                             AND i.indisprimary");

            if (empty($pk)) continue;

            $pkColumn = $pk[0]->attname;

            // Check if it has a sequence
            $sequence = DB::select("SELECT pg_get_serial_sequence('{$tableName}', '{$pkColumn}') as seq");

            if (empty($sequence) || !$sequence[0]->seq) {
                continue;
            }

            $this->info("Syncing sequence for table: {$tableName}...");

            DB::statement("SELECT setval(
                pg_get_serial_sequence('{$tableName}', '{$pkColumn}'), 
                coalesce(max({$pkColumn}), 0) + 1, 
                false
            ) FROM {$tableName}");
        }

        $this->info('All PostgreSQL sequences have been synchronized.');
    }
}
