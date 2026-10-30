<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('casetas', function (Blueprint $table) {
            $table->id();
            $table->string('number', 80)->unique();
            $table->string('owner');
            $table->string('document', 80)->index();
            $table->string('phone', 80)->nullable();
            $table->string('address');
            $table->double('lat');
            $table->double('lng');
            $table->double('accuracy')->nullable();
            $table->string('location_source', 20);
            $table->timestamp('located_at')->nullable();
            $table->text('location_reason')->nullable();
            $table->boolean('declared_no_license')->default(false);
            $table->boolean('pending_retirement')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();
        });
        Schema::create('caseta_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caseta_id')->constrained()->restrictOnDelete();
            $table->string('number', 80);
            $table->unsignedSmallInteger('year');
            $table->date('issued_on');
            $table->date('expires_on');
            $table->string('source')->default('Registro municipal');
            $table->string('payment_status', 20)->default('UNVERIFIED');
            $table->timestamps();
            $table->unique(['caseta_id', 'number', 'year']);
        });
        Schema::create('control_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caseta_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('OPEN');
            $table->text('reason');
            $table->timestamp('closed_at')->nullable();
            $table->text('closing_reason')->nullable();
            $table->timestamps();
            $table->index(['caseta_id', 'status']);
        });
        Schema::create('caseta_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caseta_id')->constrained()->restrictOnDelete();
            $table->string('kind', 50);
            $table->text('description');
            $table->string('actor_type', 80)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('details')->nullable();
            $table->string('event_key')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('temporary_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('path');
            $table->string('extension', 10);
            $table->unsignedInteger('size');
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
        Schema::table('notices', function (Blueprint $table) {
            $table->foreignId('caseta_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('control_case_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('sequence')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->double('accuracy')->nullable();
            $table->string('location_source', 20)->nullable();
            $table->timestamp('located_at')->nullable();
            $table->text('location_reason')->nullable();
            $table->unique(['control_case_id', 'sequence']);
            $table->unique(['type', 'year', 'number']);
        });
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('caseta_id')->nullable()->constrained()->restrictOnDelete();
            $table->double('accuracy')->nullable();
            $table->string('location_source', 20)->nullable();
            $table->timestamp('located_at')->nullable();
            $table->text('location_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caseta_id');
            $table->dropColumn(['accuracy', 'location_source', 'located_at', 'location_reason']);
        });
        Schema::table('notices', function (Blueprint $table) {
            $table->dropUnique(['control_case_id', 'sequence']);
            $table->dropUnique(['type', 'year', 'number']);
            $table->dropConstrainedForeignId('caseta_id');
            $table->dropConstrainedForeignId('control_case_id');
            $table->dropColumn(['year', 'sequence', 'deadline_at', 'accuracy', 'location_source', 'located_at', 'location_reason']);
        });
        Schema::dropIfExists('temporary_photos');
        Schema::dropIfExists('caseta_events');
        Schema::dropIfExists('control_cases');
        Schema::dropIfExists('caseta_licenses');
        Schema::dropIfExists('casetas');
    }
};
