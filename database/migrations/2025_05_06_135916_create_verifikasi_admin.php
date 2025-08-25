<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifikasi_admin', function (Blueprint $table) {
            $table->id('id_verifikasi_admin');
            $table->string('email', 45);
            $table->string('kode_otp', 6);
            $table->string('link_verifikasi');
            $table->enum('deskripsi',['password','email']);
            $table->unsignedSmallInteger('terkirim');
            $table->timestamps();
            $table->unsignedBigInteger('id_admin');
            $table->foreign('id_admin')->references('id_admin')->on('admin')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifikasi_admin');
    }
};