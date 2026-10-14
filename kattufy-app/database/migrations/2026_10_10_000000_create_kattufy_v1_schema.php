<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the 39 Kattufy V1 tables from database/schema/kattufy-v1-schema.sql,
 * the reviewed design that was also loaded into the local database by hand.
 *
 * Tables that already exist are left untouched, so this is safe to run on the
 * database built from that script and on an empty one. Using the script itself
 * keeps one source of truth for columns, indexes and comments.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('schema/kattufy-v1-schema.sql'));

        preg_match_all('/CREATE TABLE `(\w+)`\s*\(.*?\n\)\s*ENGINE=[^\n]*;/s', $sql, $matches, PREG_SET_ORDER);

        foreach ($matches as [$statement, $table]) {
            if (! Schema::hasTable($table)) {
                DB::unprepared($statement);
            }
        }
    }

    public function down(): void
    {
        // Deliberately empty: these tables hold business data and are never
        // dropped by a rollback. Drop them by hand if you really mean to.
    }
};
