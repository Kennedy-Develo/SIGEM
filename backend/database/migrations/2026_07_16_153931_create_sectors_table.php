<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('acronym', 20)->unique();
            $table->string('name');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectors');
    }
};
