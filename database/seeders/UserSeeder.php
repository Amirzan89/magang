<?php
namespace Database\Seeders;
use App\Models\Auth;
use App\Models\User;
use Illuminate\Database\Seeder;
Use Illuminate\Support\Facades\Hash;
Use Illuminate\Support\Str;
class UserSeeder extends Seeder
{
    private static $tempFile;
    public function __construct(){
        self::$tempFile = database_path('seeders/temp/table.json');
    }
    public function run(): void
    {
        for($i = 1; $i <= 50; $i++){
            $idAuth = Auth::insertGetId([
                'email' => "UserTesting" . $i . "@gmail.com",
                'password' => Hash::make('User@1234567890'),
                'role'=> 'user',
            ]);
            $ids[] = User::insertGetId([
                'uuid' => Str::uuid(),
                'nama_user' => 'User ' . $i,
                'jenis_kelamin' => ['laki-laki', 'perempuan'][rand(0,1)],
                'no_telpon' => '085'.mt_rand(000000000,999999999),
                'alamat' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Consequatur nesciunt provident explicabo a repudiandae delectus, numquam totam accusantium perspiciatis quam adipisci iure autem incidunt, illum accusamus facilis expedita dignissimos deserunt.',
                'email_verified_at' => null,
                'id_auth' => $idAuth,
            ]);
        }
        $jsonData = json_decode(file_get_contents(self::$tempFile), true);
        if(!isset($jsonData['user'])){
            $jsonData['user'] = [];
        }
        $jsonData['user'] = array_merge($jsonData['user'], $ids);
        file_put_contents(self::$tempFile,json_encode($jsonData, JSON_PRETTY_PRINT));
    }
}