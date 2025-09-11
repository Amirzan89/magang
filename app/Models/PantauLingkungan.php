<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PantauLingkungan extends Model
{
    use HasFactory;
    protected $table = "pantau_lingkungan";
    protected $primaryKey = "id_lingkungan";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'ph_lingkungan', 'ppm_lingkungan', 'suhu_lingkungan', 'kelembapan_lingkungan', 'id_gh'
    ];
}