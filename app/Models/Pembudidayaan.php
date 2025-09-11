<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Pembudidayaan extends Model
{
    use HasFactory;
    protected $table = "pembudidayaan";
    protected $primaryKey = "id_budidaya";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'tgl_awal_perendaman', 'tgl_akhir_perendaman', 'tgl_awal_semai', 'tgl_akhir_semai', 'tgl_masuk_vegetatif', 'tgl_masuk_generatif', 'tgl_awal_penyiraman', 'tgl_akhir_penyiraman', 'id_gh'
    ];
}