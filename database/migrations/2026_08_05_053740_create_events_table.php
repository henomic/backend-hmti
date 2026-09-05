<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('title');
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete(); // nama divisi string: KWSB, Internal, dll
            $table->string('pic');      // nama PIC (string)
            // $table->datetime('start_time');
            // $table->datetime('end_time');
            $table->enum('type_event', ['event', 'meeting']);
            $table->enum('allowed', ['publik', 'anggota', 'divisi']);

            $table->enum('status', ['Mendatang', 'Berlangsung', 'Selesai', 'Dibatalkan', 'Persiapan'])->default('Mendatang');
            $table->text('description')->nullable();
            $table->string('created_by'); // nama user pembuat
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
