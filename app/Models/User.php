<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = "users";
    protected $primaryKey = "id_user";
    public $incrementing = true;
    public $timestamps = true;
    protected $fillable = [ 
        'nama_lengkap', 'no_telpon', 'jenis_kelamin', 'tanggal_lahir', 'tempat_lahir', 'email', 'password', 'role', 'foto', 'verifikasi'
    ];
    protected $hidden = [
        // 'password',
    ];
    public function getJWTIdentifier(){
        return $this->getKey();
    }
    public function getJWTCustomClaims(){
        return [];
    }
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}