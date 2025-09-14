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
        $upcoming_events = app()->make(ServiceEventController::class)->dataCacheFile('get_limit', null, 6, ['id', 'eventid', 'eventname', 'is_free', 'imageicon_1'], ['id', 'event_id', 'event_name', 'is_free', 'img'], $request->except(['key', 'iv']), true);
        if($upcoming_events['status'] == 'error'){
            $codeRes = $upcoming_events['statusCode'];
            unset($upcoming_events['statusCode']);
            return response()->json($upcoming_events, $codeRes);
        }
        $past_events = app()->make(ServiceEventController::class)->dataCacheFile('get_limit', null, 4, ['id', 'eventid', 'eventname', 'is_free', 'imageicon_1'], ['id', 'event_id', 'event_name', 'is_free', 'img'], $request->except(['key', 'iv']), true);
        if($past_events['status'] == 'error'){
            $codeRes = $past_events['statusCode'];
            unset($past_events['statusCode']);
            return response()->json($past_events, $codeRes);
        }
        $listNamePhoto = [
            'john' => '/img/reviews/john.jpeg',
            'alex' => '/img/reviews/alex.jpg',
            'asep' => '/img/reviews/asep.jpeg',
            'jono' => '/img/reviews/jono.jpeg',
            'owi'  => '/img/reviews/owi.jpg',
            'owo'  => '/img/reviews/stalin.jpeg',
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
            ];
        }
        $dataShow = [
            'upcoming_events' => $upcoming_events['data'],
            'past_events'     => $past_events['data'],
            'reviews'         => $reviews,
        ];
        $enc = app()->make(AESController::class)->encryptResponse($dataShow, $request->input('key'), $request->input('iv'));
        return UtilityController::getView('', $enc, 'json');
    }
    public function showArtikel(Request $request, $rekomendasi = null){
        $artikel = array_map(function($item){
            $item['created_at'] = Carbon::parse($item['created_at'])->translatedFormat('l, d F Y');
            return $item;
        }, app()->make(ServiceArtikelController::class)->dataCacheFile(null, 'get_limit', null, 3) ?? []);
        // $artikel = array_merge(...array_fill(0, 5, $artikel)); // make copy
        shuffle($artikel);
        return UtilityController::getView('dashboard', [], ['redirect' => '/dashboard']);
        // return view('page.Artikel.daftar',['artikel'=> $artikel]);
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