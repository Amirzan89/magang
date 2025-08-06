<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refresh_token', function (Blueprint $table) {
            $table->id('id_refresh_token');
            $table->string('email');
            $table->longText('token');
            $table->integer('number');
            $table->timestamps();
            $table->unsignedBigInteger('id_auth');
            $table->foreign('id_auth')->references('id_auth')->on('auth')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_token');
    }
};
