<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // KWSB, Internal, Eksternal, Minbak, Sosma, Infokom, KWU
            $table->string('full_name');
            $table->string('color');
            $table->string('color_light');
            $table->string('color_soft');
            $table->string('icon');
            $table->text('description');
            $table->enum('visibility', ['publik', 'only_roles'])->default("publik");
            $table->text('vision');
            $table->text('mission');
            $table->integer('established_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
