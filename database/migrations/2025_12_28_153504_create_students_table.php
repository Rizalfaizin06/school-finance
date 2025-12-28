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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->unique(); // Nomor Induk Siswa
            $table->string('nisn')->nullable()->unique(); // Nomor Induk Siswa Nasional
            $table->string('name');
            $table->enum('gender', ['L', 'P']); // Laki-laki, Perempuan
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->date('enrollment_date');
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active');
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
