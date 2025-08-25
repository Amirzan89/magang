<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class VerifikasiUser extends Model
{
    use HasFactory;
    protected $table = "verifikasi_user";
    protected $primaryKey = "id_verifikasi_user";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'email', 'kode_otp', 'link_otp', 'deskripsi', 'terkirim', 'id_user'
    ];
    public function toUser()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}