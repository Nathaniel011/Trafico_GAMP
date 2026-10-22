<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('number');
            $table->string("address");
            $table->decimal('lat', 16, 14);
            $table->decimal('lng', 16, 14);
            $table->string("owner")->nullable();
            $table->string('document')->nullable();
            $table->string('phone')->nullable();
            $table->string('obs')->nullable();
            $table->timestamp('notice_date');
            $table->timestamp('presentation_date')->nullable();
            $table->boolean('retirement')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notices');
    }
}
