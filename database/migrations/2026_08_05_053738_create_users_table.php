<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nim')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('angkatan')->nullable();
            // kahim, wakahim, sekum1, sekum2, bendum1, bendum2, kadiv, sekdiv, bendiv, anggota

            // KWSB, Internal, Eksternal, Minbak, Sosma, Infokom, KWU
            $table->foreignId('division')->constrained('divisions')->cascadeOnDelete();
            $table->string('sub_divisi')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
