<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user');
            $table->uuid('uuid');
            $table->string('nama_lengkap',50);
            $table->enum('jenis_kelamin',['laki-laki','perempuan']);
            $table->string('no_telpon',15);
            $table->string('email',45);
            $table->string('password');
            $table->string('foto',50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};