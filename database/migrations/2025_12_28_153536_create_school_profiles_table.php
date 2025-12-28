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
        Schema::create('school_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npsn')->nullable(); // Nomor Pokok Sekolah Nasional
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('headmaster')->nullable(); // Nama Kepala Sekolah
            $table->string('treasurer')->nullable(); // Nama Bendahara
            $table->string('logo')->nullable(); // Path logo sekolah
            $table->text('letterhead')->nullable(); // HTML kop surat
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_profiles');
    }
};
