<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('history_users', function (Blueprint $table) {
            $table->id(); // ID primaria
            $table->unsignedBigInteger('id_user'); // ID del usuario relacionado
            $table->unsignedInteger('id_company'); // ID de la compañía
            $table->string('nameDocument', 255); // Nombre del documento
            $table->string('routeDocument', 255); // Ruta del documento
            $table->text('description')->nullable(); // Descripción de la acción
            $table->timestamps(); // Campos created_at y updated_at

            // Relación con la tabla users
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_users');
    }
};


