<?php
namespace App\Http\Controllers\Pages;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Security\AESController;
use App\Http\Controllers\Services\EventController AS ServiceEventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class HomeController extends Controller
{
    public function showHome(Request $request){
        $eventController = app()->make(ServiceEventController::class);
        $upcoming_events = $eventController->dataCacheEvent(null, null, 6, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img'], true, null, true);
        if($upcoming_events['status'] == 'error'){
            $codeRes = $upcoming_events['statusCode'];
            unset($upcoming_events['statusCode']);
            return response()->json($upcoming_events, $codeRes);
        }
        $upcoming_events = $upcoming_events['data'];
        $past_events = $eventController->dataCacheEvent(null, null, 4, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img'], true, null, true);
        if($past_events['status'] == 'error'){
            $codeRes = $past_events['statusCode'];
            unset($past_events['statusCode']);
            return response()->json($past_events, $codeRes);
        }
        $past_events = $past_events['data'];
        $listNamePhoto = [
            'john' => '/img/reviews/john.jpeg',
            'alex' => '/img/reviews/alex.jpg',
            'asep' => '/img/reviews/asep.jpeg',
            'jono' => '/img/reviews/jono.jpeg',
            'owi'  => '/img/reviews/owi.jpg',
            'owo'  => '/img/reviews/stalin.jpeg',
        ];
        $listComment = [
            "Pelayanannya sangat memuaskan, harganya terjangkau, dan hasilnya luar biasa. Sangat direkomendasikan!",
            "Pengalaman yang sangat menyenangkan. Timnya sangat profesional dan ramah. Pasti akan kembali lagi!",
            "Kualitasnya tidak perlu diragukan lagi. Saya sangat senang dengan hasilnya. Terima kasih banyak!",
            "Ini adalah solusi terbaik yang pernah saya temukan. Menghemat banyak waktu dan tenaga. Jempol",
            "Sangat mudah digunakan dan hasilnya melebihi ekspektasi. Pelayanan pelanggan juga sangat responsif",
            "Produk ini benar-benar mengubah cara saya bekerja. Efisien dan sangat efektif. Sukses terus!",
            "Saya awalnya ragu, tapi ternyata pelayanannya benar-benar hebat. Layak untuk dicoba!",
            "Pekerjaan selesai dengan cepat dan sempurna. Timnya sangat sigap dan detail. Puas sekali",
            "Tidak ada keluhan sama sekali! Semuanya berjalan lancar dari awal sampai akhir. Luar biasa!",
            "Meskipun harganya sedikit lebih tinggi, kualitas yang diberikan sebanding. Investasi yang sangat baik"
        ];
        $reviewCount = mt_rand(3, 5);
        $reviews = [];
        $minStar = 2;
        $maxStar = 5;
        for ($i = 0; $i < $reviewCount; $i++){
            $keys = array_keys($listNamePhoto);
            $randomKey = $keys[array_rand($keys)];
            $reviews[] = [
                'id' => $i,
                'name'   => $randomKey,
                'photo'  => $listNamePhoto[$randomKey],
                'rating' => mt_rand($minStar * 2, $maxStar * 2) / 2,
                'date_review' => UtilityController::changeMonth(UtilityController::randomDateInRange('2025-10-01', '2025-12-31')),
                'comment' => $listComment[mt_rand(0, count($listComment) - 1)]
            ];
        }
        $dataShow = [
            'upcoming_events' => $upcoming_events,
            'past_events' => $past_events,
            'reviews' => $reviews,
        ];
        $enc = app()->make(AESController::class)->encryptResponse($dataShow, $request->input('key'), $request->input('iv'));
        return UtilityController::getView('', $enc, 'json_encrypt');
    }
    public function showAbout(Request $request){
        $listNamePhoto = [
            'john' => '/img/reviews/john.jpeg',
            'alex' => '/img/reviews/alex.jpg',
            'asep' => '/img/reviews/asep.jpeg',
            'jono' => '/img/reviews/jono.jpeg',
            'owi'  => '/img/reviews/owi.jpg',
            'owo'  => '/img/reviews/stalin.jpeg',
        ];
        $listComment = [
            "Pelayanannya sangat memuaskan, harganya terjangkau, dan hasilnya luar biasa. Sangat direkomendasikan!",
            "Pengalaman yang sangat menyenangkan. Timnya sangat profesional dan ramah. Pasti akan kembali lagi!",
            "Kualitasnya tidak perlu diragukan lagi. Saya sangat senang dengan hasilnya. Terima kasih banyak!",
            "Ini adalah solusi terbaik yang pernah saya temukan. Menghemat banyak waktu dan tenaga. Jempol",
            "Sangat mudah digunakan dan hasilnya melebihi ekspektasi. Pelayanan pelanggan juga sangat responsif",
            "Produk ini benar-benar mengubah cara saya bekerja. Efisien dan sangat efektif. Sukses terus!",
            "Saya awalnya ragu, tapi ternyata pelayanannya benar-benar hebat. Layak untuk dicoba!",
            "Pekerjaan selesai dengan cepat dan sempurna. Timnya sangat sigap dan detail. Puas sekali",
            "Tidak ada keluhan sama sekali! Semuanya berjalan lancar dari awal sampai akhir. Luar biasa!",
            "Meskipun harganya sedikit lebih tinggi, kualitas yang diberikan sebanding. Investasi yang sangat baik"
        ];
        $reviewCount = mt_rand(3, 5);
        $reviews = [];
        $minStar = 2;
        $maxStar = 5;
        for ($i = 0; $i < $reviewCount; $i++){
            $keys = array_keys($listNamePhoto);
            $randomKey = $keys[array_rand($keys)];
            $reviews[] = [
                'id' => $i,
                'name'   => $randomKey,
                'photo'  => $listNamePhoto[$randomKey],
                'rating' => mt_rand($minStar * 2, $maxStar * 2) / 2,
                'date_review' => UtilityController::changeMonth(UtilityController::randomDateInRange('2025-10-01', '2025-12-31')),
                'comment' => $listComment[mt_rand(0, count($listComment) - 1)]
            ];
        }
        $dataShow = [
            'contributors' => [
                'https://i.pinimg.com/736x/53/1b/05/531b0525d7c7737e3624d78348bc190c.jpg',
                'https://publikasi.polije.ac.id/public/site/images/adhyatma/logo-gabung-putih.png',
                'https://katamata.wordpress.com/wp-content/uploads/2017/02/its-sticker-color1.png?w=1024',
                'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ2xgMHbCEGLmEwHVdWCK7mCTmm4B6jJaGGNw&s',
                'https://keystoneacademic-res.cloudinary.com/image/upload/c_pad,w_640,h_304/dpr_auto/f_auto/q_auto/v1/element/94/94604_thumb.png'
            ],
            'reviews' => $reviews,
        ];
        $enc = app()->make(AESController::class)->encryptResponse($dataShow, $request->input('key'), $request->input('iv'));
        return UtilityController::getView('', $enc, 'json_encrypt');
    }
    public function showEvents(Request $request){
        $eventController = app()->make(ServiceEventController::class);
        $validator = Validator::make($request->query(), [
            'next_page' => 'nullable|string|max:100',
            'limit' => 'nullable|numeric|max:30',
        ], [
            'next_page.string' => 'Parameter next_page harus berupa teks.',
            'next_page.max'    => 'Parameter next_page tidak boleh lebih dari 100 karakter.',
            'limit.numeric'  => 'Parameter limit harus berupa angka.',
            'limit.max'      => 'Batas maksimal limit adalah 30 item per halaman.',
        ]);
        if ($validator->fails()){
            $firstError = collect($validator->errors()->all())->first();
            return response()->json(['status'  => 'error', 'message' => $firstError ?? 'Terjadi kesalahan validasi parameter.'], 422);
        }
        $allEvent = $eventController->dataCacheEvent(null, null, null, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img'], true, null, true, ['next_page' => $request->query('next_page'), 'limit' => $request->query('limit') ? $request->query('limit') : 5, 'column_id' => 'eventid', 'is_first_time' => $request->hasHeader('X-Pagination-From') && $request->header('X-Pagination-From') === 'first-time']);
        if($allEvent['status'] == 'error'){
            $codeRes = $allEvent['statusCode'];
            unset($allEvent['statusCode']);
            return response()->json($allEvent, $codeRes);
        }
        $enc = app()->make(AESController::class)->encryptResponse(['data' => $allEvent['data'], ...$allEvent['meta_data']], $request->input('key'), $request->input('iv'));
        return UtilityController::getView('', $enc, 'json_encrypt');
    }
    public function getEventCategory(Request $request){
        $categoryData = app()->make(ServiceEventController::class)->dataCacheEventGroup(['id', 'eventgroup', 'eventgroupname', 'imageicon', 'active'], ['id', 'event_group', 'event_group_name', 'image_icon', 'active'], null, false);
        if($categoryData['status'] == 'error'){
            $codeRes = $categoryData['statusCode'];
            unset($categoryData['statusCode']);
            return response()->json($categoryData, $codeRes);
        }
        $enc = app()->make(AESController::class)->encryptResponse($categoryData['data'], $request->input('key'), $request->input('iv'));
        return UtilityController::getView('', $enc, 'json_encrypt');
    }
    public function showEventDetail(Request $request, $id){
        $eventController = app()->make(ServiceEventController::class);
        $eventDetail = $eventController->dataCacheEvent(null, $id, null, ['id', 'eventid', 'eventname', 'eventdescription', 'eventdetail', 'startdate', 'enddate', 'is_free' , 'link_event', 'imageicon_1', 'imageicon_2', 'imageicon_3', 'imageicon_4', 'imageicon_5', 'imageicon_6', 'imageicon_7', 'imageicon_8', 'category'], ['id', 'event_id', 'event_name', 'event_description', 'event_detail', 'start_date', 'end_date', 'is_free', 'link_event', 'img', 'img', 'img', 'img', 'img', 'img', 'img', 'img', 'category'], true, null, false);
        if($eventDetail['status'] == 'error'){
            $codeRes = $eventDetail['statusCode'];
            unset($eventDetail['statusCode']);
            return response()->json($eventDetail, $codeRes);
        }
        $eventDetail = $eventDetail['data'];
        $allEvent = $eventController->dataCacheEvent(null, null, 3, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img'], true, null, true);
        if($allEvent['status'] == 'error'){
            $codeRes = $allEvent['statusCode'];
            unset($allEvent['statusCode']);
            return response()->json($allEvent, $codeRes);
        }
        $allEvent = $allEvent['data'];
        $dataShow = [
            'detail_event' => $eventDetail,
            'all_events' => $allEvent,
        ];
        $enc = app()->make(AESController::class)->encryptResponse($dataShow, $request->input('key'), $request->input('iv'));
        return UtilityController::getView('', $enc, 'json_encrypt');
    }
}