<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Government ID and registration numbers are encrypted at rest (Credential
 * model, "encrypted" cast). Ciphertext is longer than the original 100
 * characters, so the column is widened. No index uses this column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->string('credential_number', 512)->nullable()
                ->comment('Encrypted by the application. Shown masked (last characters only).')->change();
        });
    }

    public function down(): void
    {
        // Encrypted values do not fit in 100 characters; narrowing would lose data.
    }
};
