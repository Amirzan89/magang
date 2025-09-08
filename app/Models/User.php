<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class User extends Model
{
    use HasFactory;
    protected $table = "users";
    protected $primaryKey = "id_user";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'uuid',
        'nama_lengkap',
        'email',
        'password',
        // 'jenis_kelamin',
        // 'no_telpon',
        // 'alamat',
        // 'no_rekening',
        // 'email_verified_at',
        // 'created_at',
        // 'updated_at',
    ];
    public function fromVerifikasi()
    {
        return $this->hasMany(VerifikasiUser::class, 'id_verifikasi_user');
    }
    public function fromPesanan()
    {
        return $this->hasMany(Pesanan::class, 'id_pesanan');
    }
    public function toAuth()
    {
        return $this->belongsTo(Auth::class, 'id_auth');
    }
}
