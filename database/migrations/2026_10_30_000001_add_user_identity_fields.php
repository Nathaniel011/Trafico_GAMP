<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Existing accounts remain valid; these values are required only for new registrations.
            $table->string('ci', 80)->nullable()->unique();
            $table->string('email', 254)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['ci']);
            $table->dropUnique(['email']);
            $table->dropColumn(['ci', 'email']);
        });
    }
};
