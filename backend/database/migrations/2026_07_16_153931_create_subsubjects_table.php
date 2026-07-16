<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subsubjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('name');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['subject_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsubjects');
    }
};
