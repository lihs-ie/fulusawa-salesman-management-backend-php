<?php

use App\Domains\Visit\ValueObjects\VisitResult;
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
        Schema::create('visits', function (Blueprint $table) {
            $table->uuid('identifier')->primary();
            $table->uuid('user');
            $table->dateTime('visited_at');
            $table->string('phone_area_code')->nullable();
            $table->string('phone_local_code')->nullable();
            $table->string('phone_subscriber_number')->nullable();
            $table->string('postal_code_first');
            $table->string('postal_code_second');
            $table->tinyInteger('prefecture');
            $table->string('city');
            $table->string('street');
            $table->string('building')->nullable();
            $table->text('note')->nullable();
            $table->boolean('has_graveyard')->default(false);
            $table->enum(
                'result',
                Collection::make(VisitResult::cases())
                    ->map
                    ->name
                    ->all()
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
