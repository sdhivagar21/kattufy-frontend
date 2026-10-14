<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Lookups first, then the fictional sample directory, then the demo accounts.
 * Every seeder is safe to run again: existing rows are updated or skipped.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            GeographySeeder::class,
            DirectorySeeder::class,
            VerificationLookupSeeder::class,
            ContentSeeder::class,
            SampleDirectorySeeder::class,
            DemoAccountsSeeder::class,
        ]);
    }
}
