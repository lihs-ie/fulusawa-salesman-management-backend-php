<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->uuid('identifier')->primary();
            $table->string('last_name');
            $table->string('first_name')->nullable();
            $table->jsonb('phone_number');
            $table->jsonb('address');
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
