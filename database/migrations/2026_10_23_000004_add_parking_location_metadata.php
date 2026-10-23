<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        foreach (['carparking', 'motoparking'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->double('accuracy')->nullable();
                $table->string('location_source', 20)->nullable();
                $table->timestamp('located_at')->nullable();
                $table->text('location_reason')->nullable();
            });
        }
    }
    public function down(): void
    {
        foreach (['carparking', 'motoparking'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['accuracy', 'location_source', 'located_at', 'location_reason']));
        }
    }
};
