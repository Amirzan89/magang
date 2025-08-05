<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth', function (Blueprint $table) {
            $table->id('id_auth');
            $table->string('email', 45);
            $table->string('password');
            $table->enum('role',['super_admin', 'admin_chat', 'admin_pemesanan', 'user']);
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth');
    }
};