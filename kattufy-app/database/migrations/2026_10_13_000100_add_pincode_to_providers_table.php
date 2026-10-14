<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provider self-registration collects a business PIN code. The V1 schema has
 * an address line but no PIN column; locality_id is resolved from this value
 * when the PIN code is known (LocationResolver).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('providers', 'pincode')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->char('pincode', 6)->nullable()->after('address')
                    ->comment('Business address PIN code, as entered by the provider.');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('providers', 'pincode')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('pincode');
            });
        }
    }
};
