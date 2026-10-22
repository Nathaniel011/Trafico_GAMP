<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParkingMaps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carparking', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('zone');
            $table->integer('quantity')->nullable();
            $table->integer('free')->nullable();
            $table->double('lat');
            $table->double('lng');

            $table->timestamps();
        });
        Schema::create('motoparking', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('zone');
            $table->integer('quantity')->nullable();
            $table->integer('free')->nullable();
            $table->double('lat');
            $table->double('lng');

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
        Schema::dropIfExists('motoparking');
        Schema::dropIfExists('carparking');
    }
}
