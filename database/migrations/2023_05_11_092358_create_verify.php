<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi', function(Blueprint $table){
            $table->id('id_verifikasi');
            $table->string('email');
            $table->string('code');
            $table->string('link');
            $table->enum('description',['changePass']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verify');
    }
};