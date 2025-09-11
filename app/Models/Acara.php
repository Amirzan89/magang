<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Acara extends Model
{
    use HasFactory;
    protected $table = "acara";
    protected $primaryKey = "id_acara";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = false;
    protected $fillable = [
        'nama_acara', 'deskripsi_acara', 'tgl_awal', 'tgl_akhir'
    ];
}