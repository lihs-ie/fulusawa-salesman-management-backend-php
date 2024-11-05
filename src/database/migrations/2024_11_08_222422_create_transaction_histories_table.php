<?php

use App\Domains\TransactionHistory\ValueObjects\TransactionType;
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
        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->uuid('identifier')->primary();
            $table->uuid('customer');
            $table->uuid('user');
            $table->enum(
                'type',
                Collection::make(TransactionType::cases())
                    ->map
                    ->name
                    ->all()
            );
            $table->text('description')->nullable();
            $table->date('date');
            $table->timestamps();

            $table->foreign('customer', 'fk_transaction_histories_01')
                ->references('identifier')
                ->on('customers');

            $table->foreign('user', 'fk_transaction_histories_02')
                ->references('identifier')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_histories');
    }
};
