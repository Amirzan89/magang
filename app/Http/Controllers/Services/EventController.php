<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
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
    public function dataCacheFile($con = null, $limit = null, $col = null, $alias = null, $formatDate = false, $searchFilter = null, $shuffle = false){
        $directory = storage_path('app/database');
        if(!file_exists($directory)){
            mkdir($directory, 0755, true);
        }
        $updateFileCache = function(){
            $eventData = $this->fetchEvents();
            foreach($eventData as &$item){
                unset($item['id_event']);
            }
            if(!file_put_contents(self::$jsonFile, json_encode($eventData, JSON_PRETTY_PRINT))){
                return ['status' => 'error', 'message' => 'Gagal menyimpan file sistem'];
            }
            return $eventData;
        };
        $jsonData = [];
        if(!file_exists(self::$jsonFile)){
            $jsonData = $updateFileCache();
        }else{
            $jsonData = json_decode(file_get_contents(self::$jsonFile), true);
        }
        if(empty($jsonData) || is_null($jsonData)){
            $jsonData = $updateFileCache();
        }
        $result = $jsonData;
        foreach($result as &$item){
            $item['is_free'] = $item['price'] == 0 || $item['price'] === "0.0000";
        }
        switch($con){
            case 'get_total':
                return ['status' => 'success', 'data' => count($jsonData)];
        }

        $searchF = function(array $result, array $searchFilter){
            if(empty($searchFilter['search']) || is_null($searchFilter['search'])) return $result;
            $query = $searchFilter['search']['keywoard'];
            $searchableFields = array_key_exists('fields', $searchFilter['search']) ? $searchFilter['search']['fields'] : ['eventname'];
            $caseSensitive = $searchFilter['search']['case_sensitive'] ?? false;
            $keywords = preg_split('/\s+/', $caseSensitive ? $query : strtolower($query));
            return array_filter($result, function ($item) use ($keywords, $searchableFields, $caseSensitive){
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
                if(!empty($searchFilter['filters']['startdate']) && !empty($searchFilter['filters']['enddate'])){
                    $eventStart  = strtotime($item['startdate']);
                    $eventEnd    = strtotime($item['enddate']);
                    $filterStart = strtotime($searchFilter['filters']['startdate']);
                    $filterEnd   = strtotime($searchFilter['filters']['enddate']);
                    return $eventStart <= $filterEnd && $eventEnd >= $filterStart;
                }else{
                    if(!empty($searchFilter['filters']['startdate'])){
                        if(strtotime($item['startdate']) >= strtotime($searchFilter['filters']['startdate'])){
                            return true;
                        }
                    }
                    if(!empty($searchFilter['filters']['enddate'])){
                        if(strtotime($item['enddate']) <= strtotime($searchFilter['filters']['enddate'])){
                            return true;
                        }
                    }
                }
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

                        case 'is_free':
                            if($value === 'all'){
                                return true;
                            }
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
            if(array_key_exists('filters', $searchFilter) && array_key_exists('startdate', $searchFilter['filters']) && array_key_exists('enddate', $searchFilter['filters']) && !empty($searchFilter['filters']['startdate']) && !empty($searchFilter['filters']['enddate'])){
                $start = strtotime($searchFilter['filters']['startdate']);
                $end = strtotime($searchFilter['filters']['enddate']);
                if($start > $end){
                    return ['status' => 'error', 'message' => 'Invalid: range date filter'];
                }
            }
            if($searchFilter['flow'] == 'search'){
                $result = $searchF($result, $searchFilter);
            }else if($searchFilter['flow'] == 'filter'){
                $result = $filtersF($result, $searchFilter);
            }else if($searchFilter['flow'] == 'search-filter'){
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
            if($limit === 1){
                $result = empty($result) ? [] : $result[0];
            }
        }

        //format date
        if($formatDate){
            $result = app()->make(UtilityController::class)->changeMonth($result);
        }

        // change column mapping
        if(is_array($col) && is_array($alias) && count($col) === count($alias)){
            $mapItem = function($entry) use ($col, $alias) {
                $entryArr = (array) $entry;
                $temp = [];
                foreach($col as $i => $key){
                    if(array_key_exists($key, $entryArr)){
                        $temp[$alias[$i]] = $entryArr[$key];
                    }
                }
                return $temp;
            };
            if(is_array($result) && array_keys($result) === range(0, count($result) - 1)){
                $result = array_map($mapItem, $result);
            }else{
                $result = $mapItem($result);
            }
        }

        return ['status' => 'success', 'data' => $result];
    }

    public function searchEvent(Request $request){
        $validator = Validator::make($request->query(), [
            'find' => 'nullable|string|max:100',
            'f_pop' => 'nullable|string|in:all,trending,booked',
            'f_univ' => 'nullable|string|in:all,none',
            'f_category.*' => 'nullable|string|in:all,none,tech,business,design,games,seni,olahraga',
            'f_startdate' => 'nullable|date',
            'f_enddate' => 'nullable|date',
            'f_pay' => 'nullable|string|in:free,pay,all',
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
        $data = $this->dataCacheFile(null, null, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1', 'category'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img', 'category'], true, ['flow' => $request->query('flow', 'search-filter'), 'search' => ['keywoard' => $request->query('find'), 'fields' => ['eventname']], 'filters' => $filters], false);
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