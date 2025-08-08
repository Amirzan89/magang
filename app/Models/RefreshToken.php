<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notification\Notifiable;
class RefreshToken extends Model implements JWTSubject
{
    use HasFactory;
    protected $table = "refresh_token";
    protected $primaryKey = "id_refresh_token";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'email','token', 'number', 'status', 'id_auth'
    ];
    public function toAuth()
    {
        return $this->belongsTo(Auth::class, 'id_auth');
    }
    public function getJWTIdentifier(){
        return $this->getKey();
    }
    public function getJWTCustomClaims(){
        return [];
    }
    protected $hidden = [
        // 'password',
    ];

    protected $casts = [
        // 'email_verified_at' => 'datetime',
    ];
}
