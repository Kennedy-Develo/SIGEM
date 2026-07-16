<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifestation_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('manifestation_id')
                ->constrained('manifestations')
                ->cascadeOnDelete();

            $table->foreignId('assignee_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_by_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('ended_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('assigned_at');
            $table->dateTime('ended_at')->nullable();

            $table->text('assignment_reason')->nullable();
            $table->text('ending_reason')->nullable();

            $table->timestamps();

            $table->index(
                ['manifestation_id', 'ended_at'],
                'manifestation_assignments_current_index'
            );

            $table->index(
                ['assignee_id', 'assigned_at'],
                'manifestation_assignments_assignee_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifestation_assignments');
    }
};
