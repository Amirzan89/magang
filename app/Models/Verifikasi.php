<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verifikasi extends Model
{
    use HasFactory;
    protected $table = "verifikasi";
    protected $primaryKey = "id_verifikasi";
    public $incrementing = true;
    public $timestamps = true;
    protected $fillable = [
        'email','kode_otp','link','deskripsi','send','id_user'
    ];
}