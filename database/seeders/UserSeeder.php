<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;
class UserSeeder extends Seeder
{
    private static $tempFile;
    public function __construct(){
        self::$tempFile = database_path('seeders/temp/table.json');
    }
    public function run(): void
    {
        $ids = [];
        for($i = 1; $i <= 10; $i++){
            $ids[] = User::insertGetId([
                'uuid' =>  Str::uuid(),
                'nama_lengkap'=>'User'.$i,
                'jenis_kelamin'=>['laki-laki', 'perempuan'][rand(0, 1)],
                'no_telpon'=>'0852'.mt_rand(20000000,99999999),
                'email'=>"AdminTesting".$i."@gmail.com",
                'password'=>Hash::make('Admin@1234567890'),
                'foto'=>'/2.jpg',
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now()
            ]);
        }
        $ids[] = User::insertGetId([
            'uuid' =>  Str::uuid(),
            'nama_lengkap'=>'User'.$i,
            'jenis_kelamin'=>['laki-laki', 'perempuan'][rand(0, 1)],
            'no_telpon'=>'0852'.mt_rand(20000000,99999999),
            'email'=>"amirzanfikri5@gmail.com",
            'password'=>Hash::make('User@1234567890'),
            'foto'=>'/2.jpg',
            'created_at'=>Carbon::now(),
            'updated_at'=>Carbon::now()
        ]);
        $ids[] = User::insertGetId([
            'uuid' =>  Str::uuid(),
            'nama_lengkap'=>'User'.$i,
            'jenis_kelamin'=>['laki-laki', 'perempuan'][rand(0, 1)],
            'no_telpon'=>'0852'.mt_rand(20000000,99999999),
            'email'=>"amvue.amirzan@gmail.com",
            'password'=>Hash::make('User@1234567890'),
            'foto'=>'/2.jpg',
            'created_at'=>Carbon::now(),
            'updated_at'=>Carbon::now()
        ]);
        $ids[] = User::insertGetId([
            'uuid' =>  Str::uuid(),
            'nama_lengkap'=>'User'.$i,
            'jenis_kelamin'=>['laki-laki', 'perempuan'][rand(0, 1)],
            'no_telpon'=>'0852'.mt_rand(20000000,99999999),
            'email'=>"amlaravel.amirzan@gmail.com",
            'password'=>Hash::make('User@1234567890'),
            'foto'=>'/2.jpg',
            'created_at'=>Carbon::now(),
            'updated_at'=>Carbon::now()
        ]);
        $ids[] = User::insertGetId([
            'uuid' =>  Str::uuid(),
            'nama_lengkap'=>'User'.$i,
            'jenis_kelamin'=>['laki-laki', 'perempuan'][rand(0, 1)],
            'no_telpon'=>'0852'.mt_rand(20000000,99999999),
            'email'=>"amscriptamm@gmail.com",
            'password'=>Hash::make('User@1234567890'),
            'foto'=>'/2.jpg',
            'created_at'=>Carbon::now(),
            'updated_at'=>Carbon::now()
        ]);
        $directory = storage_path('app/database');
        if(!file_exists($directory)){
            mkdir($directory, 0755, true);
        }
        Storage::disk('admin')->put('/2.jpg', Crypt::encrypt(file_get_contents(database_path('seeders/image/2.jpg'))));
        $jsonData = json_decode(file_get_contents(self::$tempFile), true);
        if(!isset($jsonData['users'])){
            $jsonData['users'] = [];
        }
        $jsonData['users'] = array_merge($jsonData['users'], $ids);
        file_put_contents(self::$tempFile,json_encode($jsonData, JSON_PRETTY_PRINT));
    }
}