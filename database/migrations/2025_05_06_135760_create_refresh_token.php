<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refresh_token', function (Blueprint $table) {
            $table->id('id_refresh_token');
            $table->string('email');
            $table->longText('token');
            $table->integer('number');
            $table->enum('status', ['aktif', 'logout'])->default('aktif');
            $table->timestamps();
            $table->unsignedBigInteger('id_auth');
            $table->foreign('id_auth')->references('id_auth')->on('auth')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_token');
    }
};
