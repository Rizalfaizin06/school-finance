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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique(); // Nomor kwitansi
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->integer('month')->nullable(); // Bulan pembayaran (1-12) untuk SPP
            $table->integer('year')->nullable(); // Tahun pembayaran untuk SPP
            $table->decimal('amount', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'check'])->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
