<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PantauTanaman extends Model
{
    use HasFactory;
    protected $table = "pantau_tanaman";
    protected $primaryKey = "id_tanaman";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'tinggi_tanaman', 'jml_daun_tanaman', 'berat_buah_tanaman', 'id_gh'
    ];
}