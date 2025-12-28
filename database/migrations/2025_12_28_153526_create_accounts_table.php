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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Kas Tunai, Bank BRI, dll
            $table->enum('type', ['cash', 'bank']); // Tunai atau Bank
            $table->string('account_number')->nullable(); // Nomor rekening (untuk bank)
            $table->decimal('balance', 15, 2)->default(0); // Saldo saat ini
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
