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
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // SPP, Kegiatan, Seragam, dll
            $table->enum('category', ['spp', 'non_spp', 'bos']); // Kategori pembayaran
            $table->decimal('amount', 15, 2)->default(0); // Nominal default
            $table->enum('frequency', ['monthly', 'once', 'yearly'])->default('monthly');
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
        Schema::dropIfExists('fee_types');
    }
};
