<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCertificatesAddTipo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('certificates', function(Blueprint $table){
            if(!Schema::hasColumn('certificates', 'modalidad')){
                $table->string('modalidad')->default("NUEVO");
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('certificates', function(Blueprint $table){
            if(Schema::hasColumn('certificates', 'modalidad')){
                $table->dropColumn('modalidad');
            }
        });
    }
}
