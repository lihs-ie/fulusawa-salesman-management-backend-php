<?php

use App\Domains\Schedule\ValueObjects\ScheduleContent;
use App\Domains\Schedule\ValueObjects\ScheduleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('identifier')->primary();
            $table->jsonb('participants');
            $table->uuid('creator');
            $table->uuid('updater');
            $table->uuid('customer')->nullable();
            $table->string('title', ScheduleContent::MAX_TITLE_LENGTH);
            $table->longText('description')->nullable();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->enum(
                'status',
                Collection::make(ScheduleStatus::cases())
                    ->map
                    ->name
                    ->all()
            );
            $table->jsonb('repeat')->nullable();
            $table->timestamps();

            $table->foreign('creator', 'fk_schedules_01')
                ->references('identifier')
                ->on('users');

            $table->foreign('updater', 'fk_schedules_02')
                ->references('identifier')
                ->on('users');

            $table->foreign('customer', 'fk_schedules_03')
                ->references('identifier')
                ->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
