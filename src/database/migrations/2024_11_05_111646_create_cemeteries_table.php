<?php

use App\Domains\Cemetery\ValueObjects\CemeteryType;
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
        Schema::create('cemeteries', function (Blueprint $table) {
            $table->uuid('identifier')->primary();
            $table->uuid('customer');
            $table->string('name');
            $table->enum(
                'type',
                Collection::make(CemeteryType::cases())
                    ->map
                    ->name
                    ->all()
            );
            $table->timestamp('construction');
            $table->boolean('in_house');
            $table->timestamps();

            $table->foreign('customer', 'fk_cemeteries_01')
                ->references('identifier')
                ->on('customers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cemeteries');
    }
};
