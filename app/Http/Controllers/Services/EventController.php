<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Security\AESController;
use App\Mail\EventBookingMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
class EventController extends Controller
{
    private static $jsonFileEvent;
    private static $jsonFileEventGroup;
    private static $jsonFileEventBookingCounter;
    public function __construct(){
        self::$jsonFileEvent = storage_path('app/database/events.json');
        self::$jsonFileEventGroup = storage_path('app/database/event-groups.json');
        self::$jsonFileEventBookingCounter = storage_path('app/database/event_booking_counter.json');
    }
    private static function handleCache($inp, $id = null, $limit = null, $col = null, $alias = null, $formatDate = false, $searchFilter = null, $shuffle = false, $pagination = null){
        if(!is_null($id) && !empty($id) && $id){
            $found = false;
            foreach($inp as &$item){
                if($item['eventid'] === $id){
                    $found = true;
                    $inp = $item;
                    break;
                }
            }
            if(!$found){
                return ['status' => 'error', 'message' => 'Event Not Found', 'statusCode' => 404];
            }
        }

        $metaData = null;

        $searchF = function(array $inp, array $searchFilter){
            if(empty($searchFilter['search']) || is_null($searchFilter['search'])) return $inp;
            $query = $searchFilter['search']['keywoard'];
            $searchableFields = array_key_exists('fields', $searchFilter['search']) ? $searchFilter['search']['fields'] : ['eventname'];
            $caseSensitive = $searchFilter['search']['case_sensitive'] ?? false;
            $keywords = preg_split('/\s+/', $caseSensitive ? $query : strtolower($query));
            return array_filter($inp, function ($item) use ($keywords, $searchableFields, $caseSensitive){
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

        $filtersF = function(array $inp, array $searchFilter){
            if(!array_filter($searchFilter['filters'] ?? [], fn($v) => $v !== null && $v !== '')){
                return $inp;
            }
            return array_filter($inp, function ($item) use ($searchFilter){
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
                            if(!array_key_exists('eventgroup', $item) || empty($item['eventgroup'])){
                                return false;
                            }
                            $filterCategories = (array) $value;
                            $itemCategories = (array) $item['eventgroup'];
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
                $inp = $searchF($inp, $searchFilter);
            }else if($searchFilter['flow'] == 'filter'){
                $inp = $filtersF($inp, $searchFilter);
            }else if($searchFilter['flow'] == 'search-filter'){
                $inp = $searchF($inp, $searchFilter);
                $inp = $filtersF($inp, $searchFilter);
            }else if($searchFilter['flow'] == 'filter-search'){
                $inp = $filtersF($inp, $searchFilter);
                $inp = $searchF($inp, $searchFilter);
            }
        }

        if($pagination && is_array($pagination)){
            $idPage = $pagination['next_page'] ?? null;
            $pgLimit  = isset($pagination['limit']) && is_numeric($pagination['limit']) ? (int) $pagination['limit'] : null;
            usort($inp, function ($a, $b) use ($pagination){
                $getParts = function ($id){
                    $id = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $id));
                    preg_match('/([A-Za-z]+)(\d+)/', $id, $match);
                    $prefix = $match[1] ?? '';
                    $number = isset($match[2]) ? (int)$match[2] : 0;
                    return [$prefix, $number];
                };
                [$prefixA, $numA] = $getParts($a[$pagination['column_id']]);
                [$prefixB, $numB] = $getParts($b[$pagination['column_id']]);
                return $prefixA === $prefixB ? $numA <=> $numB : strcmp($prefixA, $prefixB);
            });
            $eventIds = array_column($inp, $pagination['column_id']);
            $cursorIndex = $idPage !== null ? array_search($idPage, $eventIds, true) : -1;
            $inp = array_slice($inp, $cursorIndex + 1, $pgLimit);
            $nextCursor = $inp && count($inp) > 0 ? end($inp)[$pagination['column_id']] : null;
            $hasMore = false;
            if ($nextCursor !== null) {
                $lastIndex = array_search($nextCursor, $eventIds, true);
                $hasMore = $lastIndex !== false && ($lastIndex + 1) < count($eventIds);
            }
            $metaData = [ 'next_cursor' => $nextCursor, 'has_more' => $hasMore ];
        }

        // shuffle
        if($shuffle){
            shuffle($inp);
        }

        // limit
        if($limit !== null && is_int($limit) && $limit > 0){
            $inp = array_slice($inp, 0, $limit);
            if($limit === 1){
                $inp = empty($inp) ? [] : $inp[0];
            }
        }

        //format date
        if($formatDate){
            $inp = app()->make(UtilityController::class)->changeMonth($inp);
        }

        // change column mapping
        if(is_array($col) && is_array($alias) && count($col) === count($alias)){
            $mapItem = function($entry) use ($col, $alias) {
                $entryArr = (array) $entry;
                $temp = [];
                foreach($col as $i => $key){
                    if(array_key_exists($key, $entryArr)){
                        if(array_key_exists($alias[$i], $temp)){
                            if(!is_array($temp[$alias[$i]])){
                                $temp[$alias[$i]] = [$temp[$alias[$i]]];
                            }
                            $temp[$alias[$i]][] = $entryArr[$key];
                        }else{
                            $temp[$alias[$i]] = $entryArr[$key];
                        }
                    }
                }
                return $temp;
            };
            if(is_array($inp) && array_keys($inp) === range(0, count($inp) - 1)){
                $inp = array_map($mapItem, $inp);
            }else{
                $inp = $mapItem($inp);
            }
        }   
        return ['status' => 'success', 'data' => $inp, 'meta_data' => $metaData];
    }
    public function dataCacheEventGroup($col = null, $alias = null, $searchFilter = null, $pagination = null){
        $directory = storage_path('app/database');
        if(!file_exists($directory)){
            mkdir($directory, 0755, true);
        }
        $updateFileCache = function(){
            $eventGroupData = app()->make(ThirdPartyController::class)->pyxisAPI([
                "userid" => "demo@demo.com",
                "groupid" => "XCYTUA",
                "businessid" => "PJLBBS",
                "sql" => "SELECT id, eventgroup, eventgroupname, imageicon, active FROM event_group",
                "order" => ""
            ],'/JQuery');
            foreach($eventGroupData as &$item){
                unset($item['id_event']);
            }
            if(!file_put_contents(self::$jsonFileEventGroup, json_encode($eventGroupData, JSON_PRETTY_PRINT))){
                return ['status' => 'error', 'message' => 'Gagal menyimpan file sistem'];
            }
            return $eventGroupData;
        };
        $jsonData = [];
        if(!file_exists(self::$jsonFileEventGroup)){
            $jsonData = $updateFileCache();
        }else{
            $jsonData = json_decode(file_get_contents(self::$jsonFileEventGroup), true);
        }
        if(empty($jsonData) || is_null($jsonData)){
            $jsonData = $updateFileCache();
        }
        $result = $jsonData;
        return self::handleCache($result, null, null, $col, $alias, false, $searchFilter, false, $pagination);
    }
    public function dataCacheEvent($con = null, $id = null, $limit = null, $col = null, $alias = null, $formatDate = false, $searchFilter = null, $shuffle = false, $pagination = null){
        $directory = storage_path('app/database');
        if(!file_exists($directory)){
            mkdir($directory, 0755, true);
        }
        $updateFileCache = function(){
            $eventData = app()->make(ThirdPartyController::class)->pyxisAPI([
                "userid" => "demo@demo.com",
                "groupid" => "XCYTUA",
                "businessid" => "PJLBBS",
                "sql" => "SELECT id, keybusinessgroup, keyregistered, eventgroup, eventid, eventname, eventdescription, startdate, enddate, quota, price, inclusion, imageicon_1, imageicon_2, imageicon_3, imageicon_4, imageicon_5, imageicon_6, imageicon_7, imageicon_8, imageicon_9 FROM event_schedule",
                "order" => ""
            ],'/JQuery');
            foreach($eventData as &$item){
                unset($item['id_event']);
            }
            if(!file_put_contents(self::$jsonFileEvent, json_encode($eventData, JSON_PRETTY_PRINT))){
                return ['status' => 'error', 'message' => 'Gagal menyimpan file sistem'];
            }
            return $eventData;
        };
        $jsonData = [];
        if(!file_exists(self::$jsonFileEvent)){
            $jsonData = $updateFileCache();
        }else{
            $jsonData = json_decode(file_get_contents(self::$jsonFileEvent), true);
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
            case 'get_total_by_category':
                $categoryData = $this->dataCacheEventGroup(['eventgroup', 'eventgroupname'], ['event_group', 'event_group_name'], null);
                if($categoryData['status'] === 'error'){
                    return $categoryData;
                }
                $categoryData = $categoryData['data'];
                $result = collect($jsonData)->groupBy('eventgroup')->map(fn($items, $key) => [
                    'event_group_name' => collect($categoryData)->firstWhere('event_group', $key)['event_group_name'] ?? $key,
                    'total_event' => $items->count(),
                ])->values()->toArray();
                return ['status' => 'success', 'data' => $result];
        }
        return self::handleCache($result, $id, $limit, $col, $alias, $formatDate, $searchFilter, $shuffle, $pagination);
    }

    public function searchEvent(Request $request){
        $categoryData = $this->dataCacheEventGroup(['id', 'eventgroup', 'eventgroupname', 'imageicon', 'active'], ['id', 'event_group', 'event_group_name', 'image_icon', 'active'], null);
        if($categoryData['status'] === 'error'){
            $codeRes = $categoryData['statusCode'];
            unset($categoryData['statusCode']);
            return response()->json($categoryData, $codeRes);
        }
        $categories = collect($categoryData['data'])->pluck('event_group')->implode(',');
        $validator = Validator::make($request->query(), [
            'find' => 'nullable|string|max:100',
            "f_category.*' => 'nullable|string|in:$categories",
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
            $firstError = collect($validator->errors()->all())->first();
            return response()->json(['status'  => 'error', 'message' => $firstError ?? 'Terjadi kesalahan validasi parameter.'], 422);
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
        $searchData = $this->dataCacheEvent(null, null, null, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1', 'category'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img', 'category'], true, ['flow' => $request->query('flow', 'search-filter'), 'search' => ['keywoard' => $request->query('find'), 'fields' => ['eventname']], 'filters' => $filters], false);
        if($searchData['status'] === 'error'){
            $codeRes = $searchData['statusCode'];
            unset($searchData['statusCode']);
            return response()->json($searchData, $codeRes);
        }
        $enc = app()->make(AESController::class)->encryptResponse($searchData['data'], $request->input('key'), $request->input('iv'));
        return response()->json(['status' => 'success', 'message' => $enc]);
    }
    public function bookingEvent(Request $request){
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'email' => 'required|email',
            'mobileno' => 'required|string|max:20',
            'gender' => 'required|in:M,F',
            'qty' => 'required|integer|min:1',
            'event_group' => 'required|string',
            'event_id' => 'required|string',
        ], [
            'nama.required' => 'Nama wajib diisi',
            'nama.max' => 'Nama maksimal 100 karakter',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'mobileno.required' => 'Nomor telepon wajib diisi',
            'gender.required' => 'Jenis kelamin wajib diisi',
            'gender.in' => 'Jenis kelamin harus M atau F',
            'qty.required' => 'Jumlah tiket wajib diisi',
            'qty.min' => 'Jumlah tiket minimal 1',
            'event_group.required' => 'Event group wajib ada',
            'event_id.required' => 'Event ID wajib ada',
        ]);
        if ($validator->fails()) {
            $firstError = collect($validator->errors()->all())->first();
            return response()->json(['status'  => 'error', 'message' => $firstError ?? 'Terjadi kesalahan validasi parameter.'], 422);
        }
        $directory = storage_path('app/database');
        if(!file_exists($directory)){
            mkdir($directory, 0755, true);
        }
        $counterFile = self::$jsonFileEventBookingCounter;
        $counter = 1;
        $registNo = 'REG' . str_pad($counter, 7, '0', STR_PAD_LEFT);
        if(file_exists($counterFile)){
            $jsonData = json_decode(file_get_contents($counterFile), true);
            $counter = isset($jsonData['counter']) ? intval($jsonData['counter']) + 1 : 1;
        }
        file_put_contents($counterFile, json_encode(['counter' => str_pad($counter, 7, '0', STR_PAD_LEFT)], JSON_PRETTY_PRINT));
        $keyPyxis = env('PYXIS_KEY1');
        $ivPyxis = env('PYXIS_IV');
        $reqDec = [
            "userid" => "demo@demo.com",
            "groupid" => "XCYTUA",
            "businessid" => "PJLBBS",
            "sql" => sprintf(
                "INSERT INTO event_registration 
                (keybusinessgroup, keyregistered, eventgroup, eventid, registrationstatus, registrationno, registrationdate, registrationname, email, mobileno, gender, qty, paymenttype, paymentid, paymentamount, paymentdate, notes)
                VALUES ('I5RLGI', '5EA9I2', '%s', '%s', 'O', '%s', '%s', '%s', '%s', '%s', '%s', %d, 'C', '122335465656', '50000', '%s', 'OK')",
                addslashes($request->input('event_group')),
                addslashes($request->input('event_id')),
                $registNo,
                now()->toDateString(),
                addslashes($request->input('nama')),
                addslashes($request->input('email')),
                addslashes($request->input('mobileno')),
                addslashes($request->input('gender')),
                (int) $request->input('qty'),
                now()->toDateString()
            )
        ];
        $bodyData = strtoupper(bin2hex(openssl_encrypt(json_encode($reqDec), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis)));
        $bodyReq = [
            'apikey' => env('PYXIS_KEY2'),
            'uniqueid' => $ivPyxis,
            'timestamp' => now()->format('YmdHis'),
            'message' => $bodyData,
        ];
        $res = Http::withHeaders(['Content-Type' => 'application/json'])->post(env('PYXIS_URL') . '/JNonQuery', $bodyReq);
        $responseJson = json_decode($res->body(), true);
        $decServer = json_decode(openssl_decrypt(hex2bin($responseJson['message']), 'AES-256-CBC', $keyPyxis, OPENSSL_RAW_DATA, $ivPyxis), true);
        if(isset($decServer['status']) && $decServer['status'] === 'error'){
            return response()->json(['status' => 'error', 'message' => $decServer['message']], 500);
        }
        Mail::to($request->input('email'))->send(new EventBookingMail([
            'email' => $request->input('email'),
            'name' => $request->input('nama'),
            'event_id' => $request->input('event_id')
        ]));
        $enc = app()->make(AESController::class)->encryptResponse(['message' => 'Email sudah dikirimkan'], $request->input('key'), $request->input('iv'));
        return response()->json(['status' => 'success', 'message' => $enc]);
    }
}