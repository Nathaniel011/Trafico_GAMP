<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCertificate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create('references', function (Blueprint $table) {
//            $table->id();
//            $table->string("types");
//            $table->time('start_hour');
//            $table->time('end_hour');
//
//            $table->timestamps();
//        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string("tipo");
            $table->string("solicitante");
            $table->string('ci');
            $table->string('celular')->nullable();
            $table->string('rubro');
            $table->string('direccion');

            $table->float('dim_ancho');
            $table->float('dim_largo');

            $table->string('color');
            $table->string('material');

            $table->enum('tipo_via', ['ACERA', 'CALZADA', 'JARDINERA', 'OTRO']);
            $table->float('dim_via');
            $table->float('dim_perc_ocupado')->nullable();

            $table->boolean('technical_proceed');
            $table->boolean('legal_proceed');

            $table->string('ref_product');
            $table->string('ref_turn');
            $table->time('ref_start');
            $table->time('ref_end');


//            $table->unsignedInteger('reference_id');
//            $table->foreign('reference_id')->references('id')->on('references');

            $table->double('lat');
            $table->double('lng');

            $table->text('conclusions');

            // SQLite is used only by the isolated local test installation.
            $uuidDefault = Schema::getConnection()->getDriverName() === 'sqlite'
                ? "(lower(hex(randomblob(4))) || '-' || lower(hex(randomblob(2))) || '-4' || substr(lower(hex(randomblob(2))), 2) || '-' || substr('89ab', abs(random() % 4) + 1, 1) || substr(lower(hex(randomblob(2))), 2) || '-' || lower(hex(randomblob(6))))"
                : 'uuid_generate_v4()';
            $table->uuid('code')->default(DB::raw($uuidDefault));

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
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('references');
    }
}
