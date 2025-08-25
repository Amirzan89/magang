<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class VerifikasiAdmin extends Model
{
    use HasFactory;
    protected $table = "verifikasi_admin";
    protected $primaryKey = "id_verifikasi_admin";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'email', 'kode_otp', 'link_otp', 'deskripsi', 'terkirim', 'id_admin'
    ];
    public function toAdmin()
    {
        return $this->belongsTo(Admin::class, 'id_admin');
    }
}