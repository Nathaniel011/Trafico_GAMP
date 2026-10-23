<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificate_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->json('snapshot');
            $table->string('actor_type', 80)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason');
            $table->timestamp('created_at');
            $table->unique(['certificate_id', 'revision']);
        });
        Schema::create('license_payment_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caseta_license_id')->constrained()->restrictOnDelete();
            $table->string('status', 20);
            $table->string('source');
            $table->string('reference', 120);
            $table->timestamp('checked_at');
            $table->text('observations')->nullable();
            $table->string('actor_type', 80)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->char('fingerprint', 64)->unique();
            $table->timestamp('created_at');
            $table->index(['caseta_license_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_payment_checks');
        Schema::dropIfExists('certificate_revisions');
    }
};
