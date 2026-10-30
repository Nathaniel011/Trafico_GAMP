<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('casetas', fn (Blueprint $table) => $table->string('license_verification', 20)->default('UNKNOWN')); }
    public function down(): void { Schema::table('casetas', fn (Blueprint $table) => $table->dropColumn('license_verification')); }
};
