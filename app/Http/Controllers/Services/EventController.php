<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Security\AESController;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Laravel\Passport\Token;
use Carbon\Carbon;
use Error;
class EventController extends Controller
{
    private static $jsonFile;
    public function __construct(){
        self::$jsonFile = storage_path('app/database/events.json');
    }
    private function fetchEvents($reqDec){
        $keyPyxis = env('PYXIS_KEY1');
        $ivPyxis = env('PYXIS_IV');
        $bodyData = strtoupper(bin2hex(openssl_encrypt(json_encode($reqDec), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis)));
        $bodyReq = [
            'apikey' => env('PYXIS_KEY2'),
            'uniqueid' => $ivPyxis,
            'timestamp' => now()->format('YmdHis'),
            'message' => $bodyData,
        ];
        $res =  Http::withHeaders(['Content-Type' => 'application/json'])->post('http://sereg.alcorsys.com:8989/JQuery', $bodyReq)->body();
        $decServer = json_decode(openssl_decrypt(hex2bin(json_decode($res, true)['message']), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis), true);
        return $decServer['data'];
    }
    public function dataCacheFile($con, $idEvent = null, $limit = null, $colAlias = null, $reqData){
        $directory = storage_path('app/database');
        if(!file_exists($directory)){
            mkdir($directory, 0755, true);
        }
        $fileExist = file_exists(self::$jsonFile);
        //check if file exist
        if(!$fileExist){
            //if file is delete will make new json file
            $eventData = $this->fetchEvents($reqData);
            foreach($eventData as &$item){
                unset($item['id_event']);
            }
            if(!file_put_contents(self::$jsonFile,json_encode($eventData, JSON_PRETTY_PRINT))){
                return ['status'=>'error','message'=>'Gagal menyimpan file sistem'];
            }
        }
        if($con == 'get_id'){
            $jsonData = json_decode(file_get_contents(self::$jsonFile), true);
            $result = null;
            foreach($jsonData as $key => $item){
                if(isset($item['id_event']) && $item['id_event'] == $idEvent){
                    $result = $jsonData[$key];
                }
            }
            return ['status'=>'success','data'=>$result];
        }else if($con == 'get_total'){
            $jsonData = json_decode(file_get_contents(self::$jsonFile), true);
            $result = 0;
            $result = count($jsonData);
            return ['status'=>'success','data'=>$result];
        }else if($con == 'get_limit'){
            $jsonData = json_decode(file_get_contents(self::$jsonFile), true);
            if(!empty($data) && !is_null($data)){
                $result = null;
                if(count($data) > 1){
                    return ['status'=>'error', 'message'=>'error array key more than 1'];
                }
                foreach($jsonData as $key => $item){
                    $keys = array_keys($data)[0];
                    if(isset($item[$keys]) && $item[$keys] == $data[$keys]){
                        $result[] = $jsonData[$key];
                    }
                }
                if($result === null){
                    return ['status'=>'success','data'=>$result];
                }
                $jsonData = [];
                $jsonData = $result;
            }
            if(is_array($jsonData)){
                if($limit !== null && is_int($limit) && $limit > 0){
                    $jsonData = array_slice($jsonData, 0, $limit);
                }
                if(!is_null($colAlias) && is_array($colAlias['col'])){
                    foreach($jsonData as &$entry){
                        $entry = array_intersect_key($entry, array_flip($colAlias['col']));
                        $entry = is_array($colAlias['alias']) && (count($colAlias['col']) === count($colAlias['alias'])) ? array_combine($colAlias['alias'], array_values($entry)) : $entry;
                    }
                }
                return ['status'=>'success','data'=>$jsonData];
            }
            return null;
        // }else if($con == 'get_riwayat'){
        //     $jsonData = json_decode(file_get_contents(self::$jsonFile), true);
        //     usort($jsonData, function($a, $b){
        //         return strtotime($b['created_at']) - strtotime($a['created_at']);
        //     });
        //     if(!empty($data) && !is_null($data)){
        //         $result = null;
        //         if(count($data) > 1){
        //             return 'error array key more than 1';
        //         }
        //         foreach($jsonData as $key => $item){
        //             $keys = array_keys($data)[0];
        //             if(isset($item[$keys]) && $item[$keys] == $data[$keys]){
        //                 $result[] = $jsonData[$key];
        //             }
        //         }
        //         if($result === null){
        //             return ['status'=>'success','data'=>$result];
        //         }
        //         $jsonData = [];
        //         $jsonData = $result;
        //     }
        //     if(is_array($jsonData)){
        //         if($limit !== null && is_int($limit) && $limit > 0){
        //             $jsonData = array_slice($jsonData, 0, $limit);
        //         }
        //         if(is_array($col)){
        //             foreach($jsonData as &$entry){
        //                 $entry = array_intersect_key($entry, array_flip($col));
        //                 $entry = is_array($alias) &&(count($col) === count($alias)) ? array_combine($alias, array_values($entry)) : $entry;
        //             }
        //         }
        //         foreach($jsonData as &$item){
        //             $item['desc'] = 'ks';
        //         }
        //         return $jsonData;
        //     }
        //     return [];
        }
    }
}