<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->uuid('identifier')->primary();
            $table->uuid('user');
            $table->date('date');
            $table->jsonb('schedules');
            $table->jsonb('visits');
            $table->boolean('is_submitted');
            $table->timestamps();

            $table->foreign('user', 'fk_daily_reports_01')
                ->references('identifier')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
