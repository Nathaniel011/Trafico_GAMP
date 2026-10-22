<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCertificatesAddInspection extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('certificates', function(Blueprint $table){
            if(!Schema::hasColumn('certificates', 'inspection_date')){
                $table->date('inspection_date')->nullable();
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
            if(Schema::hasColumn('certificates', 'inspection_date')){
                $table->dropColumn('inspection_date');
            }
        });
    }
}
