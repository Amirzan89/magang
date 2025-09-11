<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class HamaPenyakit extends Model
{
    use HasFactory;
    protected $table = "hama_penyakit";
    protected $primaryKey = "id_hama_penyakit";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'warna_daun', 'warna_batang', 'serangan_hama','cara_penanganan', 'jml_pestisida', 'foto_gh', 'id_gh'
    ];
}