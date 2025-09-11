<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class GreenHouse extends Model
{
    use HasFactory;
    protected $table = "green_house";
    protected $primaryKey = "id_gh";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'nama_gh', 'fokus_gh', 'metode_gh', 'alamat_gh','luas_gh', 'populasi', 'foto_gh', 'id_user'
    ];
}