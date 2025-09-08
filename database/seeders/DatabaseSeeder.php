<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    private static $tempFile;
    public function __construct(){
        self::$tempFile = database_path('seeders/temp/table.json');
    }
    public function run(): void
    {
        $dirStorage = storage_path('app/database');
        if(!file_exists($dirStorage)){
            mkdir($dirStorage, 0755, true);
        }
        $files = glob($dirStorage . '/*');
        foreach($files as $file){
            if(is_file($file)){
                unlink($file);
            }
        }
        $directory = database_path('seeders/temp');
        if(!file_exists($directory)){
            mkdir($directory, 0755, true);
        }
        if(file_exists(self::$tempFile)){
            unlink(self::$tempFile);
        }
        file_put_contents(self::$tempFile,json_encode([], JSON_PRETTY_PRINT));
        // $this->call(AdminSeeder::class);
        $this->call(UserSeeder::class);
        // $this->call(MetodePembayaranSeeder::class);
        unlink(self::$tempFile);
        if(is_dir($directory) && count(scandir($directory)) === 2){
            rmdir($directory);
        }
    }
}
