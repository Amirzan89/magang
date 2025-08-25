<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aes_token', function (Blueprint $table) {
            $table->id('id_aes_token');
            $table->string('aes_key', 45);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aes_token');
    }
};