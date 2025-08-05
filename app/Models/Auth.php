<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class Auth extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $table = "auth";
    protected $primaryKey = "id_auth";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = false;
    protected $fillable = [
        'email', 'password', 'role'
    ];
    protected $hidden = [
        'password', 'remember_token',
    ];
    public function fromAdmin()
    {
        return $this->hasMany(Admin::class, 'id_admin');
    }
    public function fromUser()
    {
        return $this->hasMany(User::class, 'id_user');
    }
}