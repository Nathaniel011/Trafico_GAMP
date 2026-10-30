<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            // The historical precision (16,14) cannot represent longitudes >= 100.
            $table->decimal('lat', 18, 14)->change();
            $table->decimal('lng', 18, 14)->change();
        });
        Schema::table('casetas', fn (Blueprint $table) => $table->index(['lat', 'lng']));
    }
    public function down(): void
    {
        Schema::table('casetas', fn (Blueprint $table) => $table->dropIndex(['lat', 'lng']));
        // Keep the widened numeric columns to preserve captured coordinates.
    }
};
