<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignUuid('school_calendar_id')->constrained('school_calendars')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['FIXED_HOLIDAY', 'VARIABLE_HOLIDAY', 'SCHOOL_BREAK']);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_estimated')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('school_calendar_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_events');
    }
};
