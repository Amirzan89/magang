<?php
namespace App\Http\Controllers\Services;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Security\AESController;
use App\Mail\EventBookingMail;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use Error;
class EventController extends Controller
{
    private static $jsonFileEvent;
    private static $jsonFileEventGroup;
    private static $jsonFileEventsCounter;
    private static $jsonFileEventBookingCounter;
    public function __construct(){
        self::$jsonFileEvent = storage_path('app/cache/events.json');
        self::$jsonFileEventGroup = storage_path('app/cache/event-groups.json');
        self::$jsonFileEventsCounter = storage_path('app/cache/events_counter.json');
        self::$jsonFileEventBookingCounter = storage_path('app/cache/event_booking_counter.json');
    }
    private static function handleCache($inp, $id = null, $limit = null, $col = null, $alias = null, $formatDate = false, $shuffle = false, $searchFilter = null, $pagination = null){
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
            if(empty($query) || is_null($query)) return [];
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
            $filtered = $inp;
            foreach($searchFilter['filters'] as $key => $value){
                if($value === null || $value === '' || $value === []) continue;
                $filtered = array_filter($filtered, function($item) use ($key, $value, $searchFilter) {
                    switch($key) {
                        case 'startdate':
                        case 'enddate':
                            $start = !empty($searchFilter['filters']['startdate']) ? strtotime($searchFilter['filters']['startdate']) : null;
                            $end = !empty($searchFilter['filters']['enddate']) ? strtotime($searchFilter['filters']['enddate']) : null;
                            $itemStart = strtotime($item['startdate']);
                            $itemEnd   = strtotime($item['enddate']);
                            if($start && $end){
                                return $itemStart >= $start && $itemEnd <= $end;
                            }else if($start){
                                return $itemStart >= $start;
                            }else if($end){
                                return $itemEnd <= $end;
                            }
                            return true;
                        case 'category':
                            $filterCategories = (array) $value;
                            $itemCategories = (array)($item['eventgroup'] ?? []);
                            return count(array_intersect($filterCategories, $itemCategories)) > 0;
                        case 'is_free':
                            if ($value === 'all') return true;
                            return ($value === 'free') ? $item['is_free'] : !$item['is_free'];
                        default:
                            return isset($item[$key]) && $item[$key] == $value;
                    }
                });
            }
            return $filtered;
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

        // limit
        if($limit !== null && is_int($limit) && $limit > 0){
            $inp = array_slice($inp, 0, $limit);
            if($limit === 1){
                $inp = empty($inp) ? [] : $inp[0];
            }
        }

        if($pagination && is_array($pagination)){
            $idPage = $pagination['next_page'] ?? null;
            $pgLimit = isset($pagination['limit']) && is_numeric($pagination['limit']) ? (int) $pagination['limit'] : null;
            usort($inp, function($a, $b) use ($pagination){
                $getParts = function($id){
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
            $totalData = count($eventIds);
            $cursorIndex = $idPage !== null ? array_search($idPage, $eventIds, true) : -1;
            if($pagination['is_first_time'] && $idPage !== null && $cursorIndex >= 0){
                $inp = array_slice($inp, 0, ($cursorIndex + 1) + $pgLimit);
            }else{
                $inp = array_slice($inp, $cursorIndex + 1, $pgLimit);
            }
            $nextCursor = $inp && count($inp) > 0 ? end($inp)[$pagination['column_id']] : null;
            $hasMore = false;
            if($nextCursor !== null){
                $lastIndex = array_search($nextCursor, $eventIds, true);
                $hasMore = $lastIndex !== false && ($lastIndex + 1) < count($eventIds);
            }
            $metaData = [ 'next_cursor' => $nextCursor, 'has_more' => $hasMore, 'total_items' => $totalData ];
        }

        // shuffle
        if($shuffle){
            shuffle($inp);
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
        $directory = storage_path('app/cache');
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
        return self::handleCache($result, null, null, $col, $alias, false, false, $searchFilter, $pagination);
    }
    public function dataCacheEvent($con = null, $metaData = null, $inpData = null, $searchFilter = null, $pagination = null){
        $defaults = [
            'id' => null,
            'limit' => null,
            'col' => null,
            'alias' => null,
            'formatDate' => false,
            'shuffle' => false
        ];
        $metaData = is_array($metaData) ? array_merge($defaults, $metaData) : $defaults;
        $directory = storage_path('app/cache');
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
            case 'sync_cache':
                $updateFileCache();
                return ['status' => 'success', 'message' => 'Sinkronisasi cache event berhasil'];
            case 'get_total':
                return ['status' => 'success', 'data' => count($jsonData)];
            case 'get_total_by_category':
                $categoryData = $this->dataCacheEventGroup(['eventgroup', 'eventgroupname'], ['event_group', 'event_group_name'], null);
                if($categoryData['status'] === 'error'){
                    return $categoryData;
                }
                $categoryData = $categoryData['data'];
                $result = collect($jsonData)->groupBy('eventgroup')->map(function ($items, $key) use ($categoryData){
                    $match = collect($categoryData)->firstWhere('event_group', $key);
                    if(!$match){
                        return null;
                    }
                    return [
                        'event_group_name' => $match['event_group_name'],
                        'total_event' => $items->count(),
                    ];
                })->filter()->values()->toArray();
                return ['status' => 'success', 'data' => $result];
            case 'get_latest_id':
                $last = collect($jsonData)->pluck('eventid')->map(fn($id) => (int) str_replace('EVT', '', $id))->max();
                return $last ?? 0;
            case 'tambah':
                file_put_contents(self::$jsonFileEvent, json_encode(array_merge($jsonData, [$inpData]), JSON_PRETTY_PRINT));
                return ['status' => 'success', 'message' => 'Cache event berhasil diperbarui'];
            case 'delete_event':
                if(!$metaData['id']){
                    return ['status' => 'error', 'message' => 'ID event tidak diberikan'];
                }
                $idsToDelete = is_array($metaData['id']) ? $metaData['id'] : [$metaData['id']];
                $filtered = array_filter($jsonData, fn($item) => !in_array($item['eventid'], $idsToDelete));
                file_put_contents(self::$jsonFileEvent, json_encode(array_values($filtered), JSON_PRETTY_PRINT));
                return ['status' => 'success', 'message' => 'Cache event berhasil dihapus'];
        }
        return self::handleCache($result, $metaData['id'], $metaData['limit'], $metaData['col'], $metaData['alias'], $metaData['formatDate'], $metaData['shuffle'], $searchFilter, $pagination);
    }
    public function searchEvent(Request $request, UtilityController $utilityController, AESController $aesController){
        $categoryData = $this->dataCacheEventGroup(['id', 'eventgroup', 'eventgroupname', 'imageicon', 'active'], ['id', 'event_group', 'event_group_name', 'image_icon', 'active'], null);
        if($categoryData['status'] === 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$categoryData['message']], 'json_encrypt', $categoryData['statusCode']);
        }
        $categories = collect($categoryData['data'])->pluck('event_group')->implode(',');
        $validator = Validator::make($request->query(), [
            'find' => 'nullable|string|max:100',
            'f_category.*' => "nullable|string|in:$categories",
            'f_sr_date' => 'nullable|date',
            'f_er_date' => 'nullable|date',
            'f_pay' => 'nullable|string|in:free,pay,all',
            'next_page' => 'nullable|string|max:100',
            'limit' => 'nullable|numeric|max:30',
        ], [
            'find.string' => 'Pencarian harus string',
            'find.max' => 'Pencarian maksimal 100 karakter',
            'f_pop.in' => 'Filter Populer Invalid',
            'f_pop.string' => 'Filter Populer harus string',
            'f_univ.in' => 'Filter Universitas Invalid',
            'f_univ.string' => 'Filter Universitas harus string',
            'f_category.*.in' => 'Filter Kategori Invalid',
            'f_sr_date.date' => 'Filter Rentang Tanggal Harus tanggal',
            'f_er_date.date' => 'Filter Rentang Tanggal Harus tanggal',
            'f_pay.string' => 'Filter Harga Harus string',
            'f_pay.in' => 'Filter Harga Invalid',
            'next_page.string' => 'Parameter next_page harus berupa teks.',
            'next_page.max'    => 'Parameter next_page tidak boleh lebih dari 100 karakter.',
            'limit.numeric'  => 'Parameter limit harus berupa angka.',
            'limit.max'      => 'Batas maksimal limit adalah 30 item per halaman.',
        ]);
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json_encrypt', 422);
        }
        $filters = [
            'category' => $request->query('f_category', []),
            // 'popular' => $request->query('f_pop'),
            // 'university' => $request->query('f_univ'),
            // 'eventgroup' => $request->query('f_category'),
            'startdate' => $request->query('f_sr_date') ?: null,
            'enddate' => $request->query('f_er_date') ?: null,
            // 'price' => $request->query('f_price'),
            'is_free' => $request->query('f_pay'),
        ];
        $searchData = $this->dataCacheEvent(null, [
            'id' => null,
            'limit' => null,
            'col' => ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'imageicon_1', 'category'],
            'alias' => ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'img', 'category'],
            'formatDate' => true,
            'shuffle' => false
        ], null, [
            'flow' => $request->query('flow', 'search-filter'),
            'search' => ['keywoard' => $request->query('find'), 'fields' => ['eventname']],
            'filters' => $filters
        ], [
            'next_page' => $request->query('next_page'),
            'limit' => $request->query('limit') ? $request->query('limit') : 5,
            'column_id' => 'eventid',
            'is_first_time' => $request->hasHeader('X-Pagination-From') && $request->header('X-Pagination-From') === 'first-time'
        ]);
        if($searchData['status'] === 'error'){
            $codeRes = $searchData['statusCode'];
            unset($searchData['statusCode']);
            return response()->json($searchData, $codeRes);
        }
        return $utilityController->getView($request, $aesController, '', ['data'=>$searchData['data'], ...$searchData['meta_data']], 'json_encrypt');
    }
    public function bookingEvent(Request $request, UtilityController $utilityController, AESController $aesController){
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'gender' => 'required|in:M,F',
            'mobileno' => 'required|string|max:20',
            'email' => 'required|email',
            'event_group' => 'required|string',
            'event_id' => 'required|string',
            'qty' => 'required|integer|min:1',
        ], [
            'nama.required' => 'Nama harus diisi',
            'nama.max' => 'Nama maksimal 100 karakter',
            'gender.required' => 'Jenis kelamin harus diisi',
            'gender.in' => 'Jenis kelamin harus M atau F',
            'mobileno.required' => 'Nomor telepon harus diisi',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'event_group.required' => 'Event group harus ada',
            'event_id.required' => 'Event ID harus ada',
            'qty.required' => 'Jumlah tiket harus diisi',
            'qty.min' => 'Jumlah tiket minimal 1',
        ]);
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json_encrypt', 422);
        }
        $directory = storage_path('app/cache');
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
        try{
            $keyPyxis = env('PYXIS_KEY1');
            $ivPyxis = env('PYXIS_IV');
            $reqDec = [
                "userid" => "demo@demo.com",
                "groupid" => "XCYTUA",
                "businessid" => "PJLBBS",
                "sql" => sprintf(
                    "INSERT INTO event_registration (keybusinessgroup, keyregistered, eventgroup, eventid, registrationstatus, registrationno, registrationdate, registrationname, email, mobileno, gender, qty, paymenttype, paymentid, paymentamount, paymentdate, notes) VALUES ('I5RLGI', '5EA9I2', '%s', '%s', 'O', '%s', '%s', '%s', '%s', '%s', '%s', %d, 'C', '122335465656', '50000', '%s', 'OK')",
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
                return $utilityController->getView($request, $aesController, '', ['message'=>$decServer['message']], 'json_encrypt', 500);
            }
            // Mail::to($request->input('email'))->send(new EventBookingMail([
                //     'email' => $request->input('email'),
                //     'name' => $request->input('nama'),
                //     'event_id' => $request->input('event_id')
                // ]));
            return $utilityController->getView($request, $aesController, '', ['message'=>'Booking Event telah berhasil'], 'json_encrypt');
        }catch(RequestException $e){
            file_put_contents($counterFile, json_encode(['counter' => str_pad(intval($jsonData['counter']), 7, '0', STR_PAD_LEFT)], JSON_PRETTY_PRINT));
            return $utilityController->getView($request, $aesController, '', ['message'=>'Gagal booking event silahkan kirim ulang'], 'json_encrypt', $e->response->status());
        }catch(Throwable $e){
            file_put_contents($counterFile, json_encode(['counter' => str_pad(intval($jsonData['counter']), 7, '0', STR_PAD_LEFT)], JSON_PRETTY_PRINT));
            return $utilityController->getView($request, $aesController, '', ['message'=>'Gagal booking event silahkan kirim ulang'], 'json_encrypt', 500);
        }catch(Error $e){
            file_put_contents($counterFile, json_encode(['counter' => str_pad(intval($jsonData['counter']), 7, '0', STR_PAD_LEFT)], JSON_PRETTY_PRINT));
            return $utilityController->getView($request, $aesController, '', ['message'=>'Gagal booking event silahkan kirim ulang'], 'json_encrypt', 500);
        }
    }
    public function tambahEvent(Request $request, ThirdPartyController $thirdPartyController, UtilityController $utilityController, AESController $aesController){
        $categoryData = $this->dataCacheEventGroup(['id', 'eventgroup', 'eventgroupname', 'imageicon', 'active'], ['id', 'event_group', 'event_group_name', 'image_icon', 'active'], null);
        if($categoryData['status'] === 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$categoryData['message']], 'json_encrypt', $categoryData['statusCode']);
        }
        $categories = collect($categoryData['data'])->pluck('event_group')->implode(',');
        $validator = Validator::make($request->all(), [
            'event_name' => 'required|string|max:50',
            'event_description' => 'required|string|max:300',
            'event_group' => "required|string|in:$categories",
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date', ///////bug aneh
            'quota' => 'required|numeric|min:1',
            'price' => 'required|numeric|min:0',
            'inclusion' => 'required|string|max:255',
            'foto' => 'required|array|max:9',
        ], [
            'event_name.required' => 'Nama event harus diisi',
            'event_name.string' => 'Nama event harus berupa teks',
            'event_name.max' => 'Nama event maksimal 50 karakter',
            'event_description.required' => 'Deskripsi event harus diisi',
            'event_description.string' => 'Deskripsi event harus berupa teks',
            'event_description.max' => 'Deskripsi event maksimal 300 karakter',
            'event_group.required' => 'Kategori harus dipilih',
            'event_group.string' => 'Kategori event harus berupa teks',
            'event_group.in' => 'Kategori Event Invalid',
            'start_date.required' => 'Tanggal mulai event harus diisi',
            'start_date.date' => 'Tanggal mulai event harus tanggal',
            'end_date.required' => 'Tanggal berakhir event harus diisi',
            'end_date.date' => 'Tanggal berakhir harus tanggal',
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai',
            'quota.required' => 'Tanggal mulai event harus diisi',
            'quota.numeric' => 'Kuota event harus berupa angka',
            'quota.min' => 'Kuota event minimal 1',
            'price.required' => 'Harga tiket harus diisi',
            'price.numeric' => 'Harga tiket harus berupa angka',
            'price.min' => 'Nama event minimal 0',
            'inclusion.required' => 'Inklusi harus diisi',
            'inclusion.string' => 'Inklusi harus berupa teks',
            'inclusion.max' => 'Inklusi maksimal 255 karakter',
            'foto.required' => 'Foto event harus diisi',
            'foto.array' => 'Foto event harus array image',
            'foto.max' => 'Foto event maksimal 9',
        ]);
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json_encrypt', 422);
        }
        $genEventId = sprintf("EVT%03d", $this->dataCacheEvent('get_latest_id') + 1);
        $imageColumns = [];
        $foto = $utilityController->base64File($request, ['foto']);
        for($i = 0; $i < 9; $i++){
            $item = $foto[$i];
            if($item instanceof \Illuminate\Http\UploadedFile){
                if(!in_array($item->extension(), ['jpeg', 'png', 'jpg'])){
                    return $utilityController->getView($request, $aesController, '', ['message'=>'Format Foto tidak valid. Gunakan format jpeg, png, jpg'], 'json_encrypt', 400);
                }
                $fotoName = $item->hashName();
                Storage::disk('events')->put($fotoName, file_get_contents($item));
                $imageColumns[] = $fotoName;
            }else if($item && isset($item['url'])){
                $imageColumns[] = $item['url'];
            }else{
                $imageColumns[] = '-';
            }
        }
        $sql = sprintf("INSERT INTO event_schedule (keybusinessgroup, keyregistered, eventgroup, eventid, eventname, eventdescription, startdate, enddate, quota, price, inclusion, imageicon_1, imageicon_2, imageicon_3, imageicon_4, imageicon_5, imageicon_6, imageicon_7, imageicon_8, imageicon_9) VALUES ('I5RLGI', '5EA9I2', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
        '%s','%s','%s','%s','%s','%s','%s','%s','%s')",
            $request->event_group,
            $genEventId,
            addslashes($request->input('event_name')),
            addslashes($request->input('event_description')),
            addslashes($request->input('start_date')),
            addslashes($request->input('end_date')),
            addslashes($request->input('quota')),
            addslashes($request->input('price')),
            addslashes($request->input('inclusion')),
            ...$imageColumns
        );
        $insertAPI = $thirdPartyController->pyxisAPI([
            "userid" => "demo@demo.com",
            "groupid" => "XCYTUA",
            "businessid" => "PJLBBS",
            "sql" => $sql,
            "order" => ""
        ], '/JNonQuery');
        // if($insertAPI['status'] == 'error'){
        //     return $utilityController->getView($request, $aesController, '', ['message' => $insertAPI['message']], 'json_encrypt', $insertAPI['statusCode']);
        // }
        $this->dataCacheEvent('tambah', null, [
            'keybusinessgroup' => 'I5RLGI',
            'keyregistered' => '5EA9I2',
            'eventgroup' => $request->event_group,
            'eventid' => $genEventId,
            'eventname' => $request->event_name,
            'eventdescription' => $request->event_description,
            'startdate' => $request->start_date,
            'enddate' => $request->end_date,
            'quota' => $request->quota,
            'price' => $request->price,
            'inclusion' => $request->inclusion,
            'imageicon_1' => $imageColumns[0],
            'imageicon_2' => $imageColumns[1],
            'imageicon_3' => $imageColumns[2],
            'imageicon_4' => $imageColumns[3],
            'imageicon_5' => $imageColumns[4],
            'imageicon_6' => $imageColumns[5],
            'imageicon_7' => $imageColumns[6],
            'imageicon_8' => $imageColumns[7],
            'imageicon_9' => $imageColumns[8],
        ]);
        return $utilityController->getView($request, $aesController, '', ['message'=>'Event berhasil ditambahkan'], 'json_encrypt');
    }
    public function deleteEvent(Request $request, ThirdPartyController $thirdPartyController, UtilityController $utilityController, AESController $aesController){
        $ids = $request->input('id_events');
        if(!is_array($ids)){
            $ids = [$ids];
        }
        $validator = Validator::make(['id_events' => $ids], [
            'id_events' => 'required|array|min:1',
            'id_events.*' => 'string|max:100',
        ], [
            'id_events.required' => 'Event harus diisi',
            'id_events.array' => 'Format data tidak valid',
            'id_events.*.string' => 'ID event harus berupa teks',
            'id_events.*.max' => 'Panjang ID maksimal 100 karakter',
        ]);
        if($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return $utilityController->getView($request, $aesController, '', ['message'=>$firstError ?? 'Terjadi kesalahan validasi parameter.'], 'json_encrypt', 422);
        }
        if(count($ids) > 1){
            $inClause = implode("','", $ids);
            $sql = "DELETE FROM event_schedule WHERE eventid IN ('$inClause')";
        }else{
            $sql = "DELETE FROM event_schedule WHERE eventid = '{$ids[0]}'";
        }
        $deleteAPI = $thirdPartyController->pyxisAPI([
            "userid" => "demo@demo.com",
            "groupid" => "XCYTUA",
            "businessid" => "PJLBBS",
            "sql" => $sql,
            "order" => ""
        ],'/JNonQuery');
        if($deleteAPI['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$deleteAPI['message']], 'json_encrypt', $deleteAPI['statusCode']);
        }
        $this->dataCacheEvent('delete');
        return $utilityController->getView($request, $aesController, '', ['message'=>'Event berhasil dihapus'], 'json_encrypt');
    }
}