<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text('description')->nullable();
            $table->date('creation_date')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('group_contacts', function (Blueprint $table) {
            $table->id();
            $table->string("number");
            $table->string('type'); // whatsapp or phone

            $table->unsignedBigInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups');

            $table->timestamps();
        });

        Schema::create('transport_lines', function(Blueprint $table){
            $table->id();
            $table->string('code');
            $table->string('color');
            $table->boolean('enabled')->default(true);
            $table->string('point_description_1')->nullable();
            $table->string('point_description_2')->nullable();

            $table->unsignedBigInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups');

            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string("type");
            $table->boolean("enabled")->default(true);
            $table->text("notes")->nullable();
            $table->text("description")->nullable();

            $table->unsignedBigInteger('transport_line_id');
            $table->foreign('transport_line_id')->references('id')->on('transport_lines');

            $table->timestamps();
        });

        Schema::create('path_points', function (Blueprint $table) {
            $table->id();
            $table->integer('order');
            $table->double('lat');
            $table->double('lng');

            $table->unsignedBigInteger('route_id');
            $table->foreign('route_id')->references('id')->on('routes');

            $table->timestamps();
        });

        Schema::create('inspectors', function(Blueprint $table){
            $table->id();
            $table->string("name");
            $table->string("username");
            $table->string("password");
            $table->boolean("enabled")->default(false);
            $table->timestamps();
        });

        Schema::create('complaints', function(Blueprint $table){
            $table->id();
            $table->string('vehicle_plaque');
            $table->string('route_code');
            $table->string('group_name')->nullable();
            $table->text('notes')->nullable();
            $table->text('state');//PENDIENTE, ACEPTADO, RECHAZADO
            $table->string('photo_path')->nullable();

            $table->unsignedBigInteger('inspector_id')->nullable();
            $table->foreign('inspector_id')->references('id')->on('inspectors');

            $table->timestamps();
        });

        Schema::create('categories', function(Blueprint $table){
            $table->id();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->string('logo_path');

            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories');

            $table->timestamps();
        });

        Schema::create('places', function(Blueprint $table){
            $table->id();
            $table->string('name');
            $table->text('body')->nullable();
            $table->text('near_lines')->nullable();
            $table->string('photo_path');
            $table->integer('order')->default(0);

            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories');

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
        Schema::dropIfExists('places');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('inspectors');
        Schema::dropIfExists('path_points');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('transport_lines');
        Schema::dropIfExists('group_contacts');
        Schema::dropIfExists('groups');
    }
}
