<?php

use App\Domains\Visit\ValueObjects\VisitResult;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table): void {
            $table->uuid('identifier')->primary();
            $table->uuid('user');
            $table->dateTime('visited_at');
            $table->jsonb('phone_number')->nullable();
            $table->jsonb('address');
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

            $table->foreign('user', 'fk_visits_01')
                ->references('identifier')
                ->on('users')
            ;
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
