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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('identifier')->primary();
            $table->string('last_name');
            $table->string('first_name')->nullable();
            $table->string('phone_area_code');
            $table->string('phone_local_code');
            $table->string('phone_subscriber_number');
            $table->string('postal_code_first');
            $table->string('postal_code_second');
            $table->tinyInteger('prefecture');
            $table->string('city');
            $table->string('street');
            $table->string('building')->nullable();
            $table->jsonb('cemeteries');
            $table->jsonb('transaction_histories');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
