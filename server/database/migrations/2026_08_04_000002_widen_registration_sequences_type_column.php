<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * registration_sequences.type was created as a hard DB enum limited to
 * STUDENT/EMPLOYEE/PAYMENT/INVOICE, so RegistrationNumberService can't
 * generate an ENROLLMENT-prefixed number without this migration. Widened to
 * a plain varchar instead of growing the enum indefinitely for every future
 * domain that needs a sequential number.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE registration_sequences DROP CONSTRAINT IF EXISTS registration_sequences_type_check');
            DB::statement('ALTER TABLE registration_sequences ALTER COLUMN type TYPE VARCHAR(20)');
        } else {
            // sqlite (test environment only): this table is always empty at
            // migration time (fresh test database), so a drop/recreate is safe
            // and avoids sqlite's lack of ALTER COLUMN / DROP CONSTRAINT support.
            Schema::dropIfExists('registration_sequences');
            Schema::create('registration_sequences', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
                $table->string('type', 20);
                $table->integer('year');
                $table->integer('last_number')->default(0);
                $table->timestamps();

                $table->unique(['school_id', 'type', 'year']);
            });
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: narrowing back to the enum would require
        // knowing no ENROLLMENT rows exist yet, which this migration cannot assert.
    }
};
