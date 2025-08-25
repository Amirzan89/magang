<?php
namespace Database\Seeders;
use App\Models\Auth;
use App\Models\Admin;
use Illuminate\Database\Seeder;
Use Illuminate\Support\Facades\Hash;
Use Illuminate\Support\Str;
class AdminSeeder extends Seeder
{
    private static $tempFile;
    public function __construct(){
        self::$tempFile = database_path('seeders/temp/table.json');
    }
    public function run(): void
    {
        $ids = [];
        $idAuth = Auth::insertGetId([
            'email' => "SuperAdmin@gmail.com",
            'password'=> Hash::make('Admin@1234567890'),
            'role' => 'super_admin',
        ]);
        $ids[] = Admin::insertGetId([
            'uuid' =>  Str::uuid(),
            'nama_admin' => 'Super Admin',
            'id_auth' => $idAuth,
        ]);
        $roles = ['admin_chat', 'admin_pemesanan'];
        foreach($roles as $role){
            $nameRole = ucwords(str_replace('admin_', '', $role));
            for($i = 1; $i <= 10; $i++){
                $idAuth = Auth::insertGetId([
                    'email' => "AdminTesting".$nameRole.$i."@gmail.com",
                    'password' => Hash::make('Admin@1234567890'),
                    'role' => $role,
                ]);
                $ids[] = Admin::insertGetId([
                    'uuid' =>  Str::uuid(),
                    'nama_admin' => 'Admin ' . $nameRole . '' . $i,
                    'id_auth' => $idAuth,
                ]);
            }
        }
        $jsonData = json_decode(file_get_contents(self::$tempFile), true);
        if(!isset($jsonData['admin'])){
            $jsonData['admin'] = [];
        }
        $jsonData['admin'] = array_merge($jsonData['admin'], $ids);
        file_put_contents(self::$tempFile,json_encode($jsonData, JSON_PRETTY_PRINT));
    }
}