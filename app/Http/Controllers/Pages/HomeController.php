<?php
namespace App\Http\Controllers\Pages;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Security\AESController;
use App\Http\Controllers\Services\EventController AS ServiceEventController;
use Illuminate\Http\Request;
use Carbon\Carbon;
class HomeController extends Controller
{
    public function showHome(Request $request){
        $eventController = app()->make(ServiceEventController::class);
        $upcoming_events = $eventController->dataCacheFile(null, null, 6, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img'], true, null, true);
        if($upcoming_events['status'] == 'error'){
            $codeRes = $upcoming_events['statusCode'];
            unset($upcoming_events['statusCode']);
            return response()->json($upcoming_events, $codeRes);
        }
        $upcoming_events = $upcoming_events['data'];
        $past_events = $eventController->dataCacheFile(null, null, 4, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img'], true, null, true);
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
        return UtilityController::getView('', $enc, 'json');
    }
    public function showEvents(Request $request){
        $eventController = app()->make(ServiceEventController::class);
        $allEvent = $eventController->dataCacheFile(null, null, null, ['id', 'eventid', 'eventname', 'startdate', 'is_free', 'nama_lokasi', 'link_lokasi', 'imageicon_1'], ['id', 'event_id', 'event_name', 'start_date', 'is_free', 'nama_lokasi', 'link_lokasi', 'img'], true, null, true);
        if($allEvent['status'] == 'error'){
            $codeRes = $allEvent['statusCode'];
            unset($allEvent['statusCode']);
            return response()->json($allEvent, $codeRes);
        }
        $enc = app()->make(AESController::class)->encryptResponse($allEvent['data'], $request->input('key'), $request->input('iv'));
        return UtilityController::getView('', $enc, 'json');
    }
    public function showDetailArtikel(Request $request, $path){
        $path = str_replace('-', ' ', $path);
        $artikel = array_map(function($item){
            $item['created_at'] = Carbon::parse($item['created_at'])->translatedFormat('l, d F Y');
            return $item;
        }, app()->make(ServiceArtikelController::class)->dataCacheFile(null, 'get_limit', 3, 3) ?? []);
        // $artikel = array_merge(...array_fill(0, 5, $artikel)); // make copy
        shuffle($artikel);
        $detailArtikel = app()->make(ServiceArtikelController::class)->dataCacheFile(['judul' => $path], 'get_limit', 1, ['judul', 'deskripsi', 'foto', 'link_video','created_at']);
        if(is_null($detailArtikel)){
            return response()->json(['status' => 'error', 'message' => 'Artikel tidak ditemukan'], 404);
        }
        $detailArtikel = $detailArtikel[0];
        $detailArtikel['deskripsi'] = '<p>' . str_replace("\n", '</p><p>', $detailArtikel['deskripsi']) . '</p>';
        $detailArtikel['created_at'] = Carbon::parse($detailArtikel['created_at'])->translatedFormat('l, d F Y');
        $dataShow = [
            'artikel' => $artikel,
            'detailArtikel' => $detailArtikel,
        ];
        return view('page.Artikel.detail',$dataShow);
    }
}