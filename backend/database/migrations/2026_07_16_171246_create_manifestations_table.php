<?php

use App\Enums\ManifestationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manifestations', function (Blueprint $table) {
            $table->id();

            $table->string('nup', 17)->unique();
            $table->string('source', 30);
            $table->string('type', 50);
            $table
                ->string('status', 30)
                ->default(ManifestationStatus::Registered->value);

            $table
                ->foreignId('subject_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('subsubject_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('sector_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('current_assignee_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table
                ->foreignId('created_by_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table
                ->foreignId('updated_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('summary')->nullable();
            $table->text('description')->nullable();

            $table->date('opened_at');
            $table->date('original_deadline_at')->nullable();
            $table->date('current_deadline_at')->nullable();

            $table->dateTime('extended_at')->nullable();
            $table->text('extension_reason')->nullable();

            $table
                ->dateTime('forwarded_to_external_agency_at')
                ->nullable();

            $table
                ->string('external_agency')
                ->nullable();

            $table
                ->dateTime('answered_by_ombudsman_at')
                ->nullable();

            $table->dateTime('completed_at')->nullable();
            $table->dateTime('archived_at')->nullable();

            $table->timestamps();

            $table->index('source');
            $table->index('type');
            $table->index('status');
            $table->index('sector_id');
            $table->index('current_assignee_id');
            $table->index('current_deadline_at');
            $table->index(['status', 'current_deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manifestations');
    }
};
