<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Panen extends Model
{
    use HasFactory;
    protected $table = "panen";
    protected $primaryKey = "id_panen";
    public $incrementing = true;
    protected $keyType = 'integer';
    public $timestamps = true;
    protected $fillable = [
        'jumlah_buah', 'berat_buah', 'ukuran_buah','rasa_buah', 'biaya_operasional', 'id_gh'
    ];
}