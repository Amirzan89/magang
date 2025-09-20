<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Security\AESController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
class EventController extends Controller
{
    private static $jsonFile;
    public function __construct(){
        self::$jsonFile = storage_path('app/database/events.json');
    }
    private function fetchEvents(){
        $keyPyxis = env('PYXIS_KEY1');
        $ivPyxis = env('PYXIS_IV');
        $reqDec = [
            "userid" => "demo@demo.com",
            "groupid" => "XCYTUA",
            "businessid" => "PJLBBS",
            "sql" => "SELECT id, keybusinessgroup, keyregistered, eventgroup, eventid, eventname, eventdescription, startdate, enddate, quota, price, inclusion, imageicon_1, imageicon_2, imageicon_3, imageicon_4, imageicon_5, imageicon_6, imageicon_7, imageicon_8, imageicon_9 FROM event_schedule",
            "order" => ""
        ];
        $bodyData = strtoupper(bin2hex(openssl_encrypt(json_encode($reqDec), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis)));
        $bodyReq = [
            'apikey' => env('PYXIS_KEY2'),
            'uniqueid' => $ivPyxis,
            'timestamp' => now()->format('YmdHis'),
            'message' => $bodyData,
        ];
        $res =  Http::withHeaders(['Content-Type' => 'application/json'])->post(env('PYXIS_URL'). '/JQuery', $bodyReq)->body();
        $decServer = json_decode(openssl_decrypt(hex2bin(json_decode($res, true)['message']), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis), true);
        return $decServer['data'];
    }
    public function dataCacheFile($con = null, $idEvent = null, $limit = null, $col = null, $alias = null, $searchFilter = null, $shuffle = false){
        $directory = storage_path('app/database');
        if(!file_exists($directory)){
            mkdir($directory, 0755, true);
        }
        if(!file_exists(self::$jsonFile)){
            $eventData = $this->fetchEvents();
            foreach($eventData as &$item){
                unset($item['id_event']);
            }
            if(!file_put_contents(self::$jsonFile, json_encode($eventData, JSON_PRETTY_PRINT))){
                return ['status' => 'error', 'message' => 'Gagal menyimpan file sistem'];
            }
        }
        $jsonData = json_decode(file_get_contents(self::$jsonFile), true);
        $result = $jsonData;
        foreach($result as &$item){
            $item['is_free'] = ($item['price'] == 0 || $item['price'] === "0.0000");
        }
        switch($con){
            case 'get_id':
                foreach($jsonData as $item){
                    if(isset($item['id_event']) && $item['id_event'] == $idEvent){
                        $result = $item;
                        break;
                    }
                }
                return ['status' => 'success', 'data' => $result];
            case 'get_total':
                return ['status' => 'success', 'data' => count($jsonData)];
        }

        $searchF = function(array $result, array $searchFilter){
            if(empty($searchFilter['search'])) return $result;
            $caseSensitive = $searchFilter['case_sensitive'] ?? false;
            $query = $searchFilter['search'];
            $keywords = preg_split('/\s+/', $caseSensitive ? $query : strtolower($query));
            return array_filter($result, function ($item) use ($keywords, $caseSensitive){
                $searchableFields = ['eventname'];
                foreach($keywords as $keyword){
                    foreach($searchableFields as $field){
                        if(!isset($item[$field])) continue;
                        $haystack = $caseSensitive ? $item[$field] : strtolower($item[$field]);
                        if(($caseSensitive ? strpos($haystack, $keyword) : strpos($haystack, $keyword)) !== false){
                            return true;
                        }
                    }
                }
                return false;
            });
        };

        $filtersF = function(array $result, array $searchFilter){
            if(!array_filter($searchFilter['filters'] ?? [], fn($v) => $v !== null && $v !== '')){
                return $result;
            }
            return array_filter($result, function ($item) use ($searchFilter){
                foreach($searchFilter['filters'] as $key => $value){
                    if($value === null || $value === '' || $value === []){
                        continue;
                    }
                    switch($key){
                        case 'category':
                            if(!array_key_exists('category', $item) || empty($item['category'])){
                                return false;
                            }
                            $filterCategories = (array) $value;
                            $itemCategories = (array) $item['category'];
                            if(count(array_intersect($filterCategories, $itemCategories)) === 0){
                                return false;
                            }
                            break;

                        case 'startdate':
                            if(!(strtotime($item['startdate']) > strtotime($value))){
                                return false;
                            }
                            break;

                        case 'enddate':
                            if(!(strtotime($item['enddate']) < strtotime($value))){
                                return false;
                            }
                            break;

                        case 'is_free':
                            if(!(($value === 'free' && $item['is_free']) || ($value === 'pay' && !$item['is_free']))){
                                return false;
                            }
                            break;

                        default:
                            if($item[$key] != $value){
                                return false;
                            }
                    }
                }
                return true;
            });
        };

        if($searchFilter){
            if($searchFilter['flow'] == 'search-filter'){
                $result = $searchF($result, $searchFilter);
                $result = $filtersF($result, $searchFilter);
            }else if($searchFilter['flow'] == 'filter-search'){
                $result = $filtersF($result, $searchFilter);
                $result = $searchF($result, $searchFilter);
            }
        }

        // shuffle
        if($shuffle){
            shuffle($result);
        }

        // limit
        if($limit !== null && is_int($limit) && $limit > 0){
            $result = array_slice($result, 0, $limit);
        }

        // change column mapping
        if(is_array($col) && is_array($alias) && count($col) === count($alias)){
            $mapped = [];
            foreach($result as $entry){
                $temp = [];
                foreach($col as $i => $key){
                    if(array_key_exists($key, $entry)){
                        $temp[$alias[$i]] = $entry[$key];
                    }
                }
                // if(!array_key_exists('category', $temp)){
                //     $temp['category'] = [];
                // }
                $mapped[] = $temp;
            }
            $result = $mapped;
        }

        return ['status' => 'success', 'data' => $result];
    }

    public function searchEvent(Request $request){
        $validator = Validator::make($request->query(), [
            'find' => 'nullable|string|max:100',
            'f_pop' => 'nullable|string|in:all,trending,booked',
            'f_univ' => 'nullable|string|in:all,none',
            'f_category.*' => 'nullable|string|in:all,none,tech,business,design,games,seni',
            'f_startdate' => 'nullable|date',
            'f_enddate' => 'nullable|date',
            'f_pay' => 'nullable|string|in:free,pay',
        ], [
            'find.string' => 'Pencarian harus string',
            'find.max' => 'Pencarian maksimal 100 karakter',
            'f_pop.in' => 'Filter Populer Invalid',
            'f_pop.string' => 'Filter Populer harus string',
            'f_univ.in' => 'Filter Universitas Invalid',
            'f_univ.string' => 'Filter Universitas harus string',
            'f_category.*.in' => 'Filter Kategori Invalid',
            'f_startdate.date' => 'Filter Rentang Tanggal Harus tanggal',
            'f_enddate.date' => 'Filter Rentang Tanggal Harus tanggal',
            'f_pay.string' => 'Filter Harga Harus string',
            'f_pay.in' => 'Filter Harga Invalid',
        ]);
        if ($validator->fails()){
            $errors = [];
            foreach($validator->errors()->toArray() as $field => $errorMessages){
                $errors[$field] = $errorMessages[0];
                break;
            }
            return response()->json(['status' => 'error', 'message' => implode(', ', $errors)], 422);
        }
        $filters = [
            'category' => $request->query('f_category', []),
            // 'popular' => $request->query('f_pop'),
            // 'university' => $request->query('f_univ'),
            // 'eventgroup' => $request->query('f_category'),
            'startdate' => $request->query('f_startdate') ?: null,
            'enddate' => $request->query('f_enddate') ?: null,
            // 'price' => $request->query('f_price'),
            'is_free' => $request->query('f_pay'),
        ];
        $searchKeyword = $request->query('find');
        $flow = $request->query('flow', 'search-filter');
        $data = $this->dataCacheFile(null, null, null, ['id', 'eventid', 'eventname', 'is_free', 'imageicon_1', 'category'], ['id', 'event_id', 'event_name', 'is_free', 'img', 'category'], ['flow' => $flow, 'search' => $searchKeyword, 'filters' => $filters], false);
        if($data['status'] === 'error'){
            return response()->json($data, 500);
        }
        $enc = app()->make(AESController::class)->encryptResponse($data['data'], $request->input('key'), $request->input('iv'));
        return response()->json(['status' => 'success', 'data' => $enc]);
    }
    public function registrationEvents(Request $request){
        $keyPyxis = env('PYXIS_KEY1');
        $ivPyxis = env('PYXIS_IV');
        $reqDec = [
            "userid" => "demo@demo.com",
            "groupid" => "XCYTUA",
            "businessid" => "PJLBBS",
            "sql" => "INSERT INTO event_registration (keybusinessgroup, keyregistered, eventgroup, eventid, registrationstatus, registrationno, registrationdate, registrationname, email, mobileno, gender, qty, paymenttype, paymentid, paymentamount, paymentdate, notes)VALUES ('I5RLGI', '5EA9I2', 'SMNR', 'EVT001', 'O', 'REG92139123 ', '2025-08-15', 'Jamal Sikamto', 'jamSIM86@myemail.com', '8136232323', 'M', '1', 'C', '122335465656', '50000', '2025-08-15 ', 'OK')",
        ];
        $bodyData = strtoupper(bin2hex(openssl_encrypt(json_encode($reqDec), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis)));
        $bodyReq = [
            'apikey' => env('PYXIS_KEY2'),
            'uniqueid' => $ivPyxis,
            'timestamp' => now()->format('YmdHis'),
            'message' => $bodyData,
        ];
        $res =  json_decode(Http::withHeaders(['Content-Type' => 'application/json'])->post(env('PYXIS_URL') . '/JNonQuery', $bodyReq)->body());
        $decServer = json_decode(openssl_decrypt(hex2bin(json_decode($res, true)['message']), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis), true);
        $enc = app()->make(AESController::class)->encryptResponse($decServer['data'], $request->input('key'), $request->input('iv'));
        return response()->json(['status' => 'success', 'data' => $enc]);
    }
}