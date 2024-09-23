<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id(); // id Primaria
            $table->string('nameCompany', 255); // Nombre de la compañía
            $table->string('typeCompany'); // Tipo de compañía
            $table->string('addressCompany'); // Dirección de la compañía
            $table->string('cityCompany'); // Ciudad de la compañía
            $table->string('countryCompany'); // País de la compañía
            $table->timestamps(); // Campos created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
