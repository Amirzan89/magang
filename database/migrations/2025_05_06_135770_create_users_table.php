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
            $table->uuid();
            $table->string('nama_user', 50);
            $table->enum('jenis_kelamin',['laki-laki','perempuan'])->nullable();
            $table->string('no_telpon', 15)->nullable();
            $table->string('alamat', 400)->nullable();
            $table->string('no_rekening', 20)->nullable();
            $table->string('foto', 50)->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('id_auth');
            $table->foreign('id_auth')->references('id_auth')->on('auth')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};