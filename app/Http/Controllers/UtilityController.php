<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use DateTime;
class UtilityController extends Controller
{
    public static function getView($name = null, $data = [], $cond = null){
        $comps = function($dom) use ($data, $cond){
            if($dom){
                if(is_array($cond) && is_array($cond['cond']) && in_array('view', $cond['cond'])){
                    $indexPath = public_path('dist/index.html');
                    if(!File::exists($indexPath)) {
                        return response()->json(['error' => 'Page not found'], 404);
                    }
                    $htmlContent = File::get($indexPath);
                    $htmlContent = str_replace('<body>', '<body>' . '<script>const csrfToken = "' . csrf_token() . '";</script>', $htmlContent);
                    $htmlContent = str_replace('</head>', '<script>window.__INITIAL_COSTUM_STATE__ = ' . json_encode($data) . '</script></head>', $htmlContent);
                    return response($htmlContent)->cookie('XSRF-TOKEN', csrf_token(), 0, '/', null, false, true);
                }
            }else{
                if(is_array($cond) && array_key_exists('redirect', $cond)){
                    setCookie('__INITIAL_COSTUM_STATE__', base64_encode(json_encode($data)), 0, '/', null, false, false);
                    return redirect(env('FRONTEND_URL', 'http://localhost:3000') . $cond['redirect']);
                }
            }
            if(is_array($cond) && array_key_exists('json_cookie', $cond)){
                setCookie('__INITIAL_COSTUM_STATE__', base64_encode($cond['json_cookie']), 0, '/', null, false, false);
                return response()->json(['status' => 'success', 'data' => $data]);
            }else if(is_string($cond) && $cond == 'only_cookie'){
                setCookie('__INITIAL_COSTUM_STATE__', base64_encode(json_encode($data)), 0, '/', null, false, false);
                return response()->json(['status' => 'success']);
            }else if(is_string($cond) && $cond == 'json'){
                return response()->json(['status' => 'success', 'data' => $data]);
            }
            return response()->json(['status' => 'error', 'message' => 'invalid request'], 400);
        };
        $env = env('APP_VIEW', 'blade');
        if($env == 'blade'){
            return view($name);
        }else if($env == 'inertia'){
            return inertia($name);
        }else if($env == 'nuxt'){
            return $comps(env('APP_DOMAIN', 'same') == 'same');
        }
    }
    public static function changeMonth($inpDate){
        $inpDate = json_decode($inpDate, true);
        $monthTranslations = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];
        // Check if it's an associative array (single data)
        if (array_keys($inpDate) !== range(0, count($inpDate) - 1)) {
            foreach (['tanggal', 'tanggal_awal', 'tanggal_akhir'] as $dateField) {
                if (isset($inpDate[$dateField]) && $inpDate[$dateField] !== null) {
                    $date = new DateTime($inpDate[$dateField]);
                    $monthNumber = $date->format('m');
                    $indonesianMonth = $monthTranslations[$monthNumber];
                    $formattedDate = $date->format('d') . ' ' . $indonesianMonth . ' ' . $date->format('Y');
                    $inpDate[$dateField] = $formattedDate;
                }
            }
        } else {
            $processedData = [];
            foreach ($inpDate as $inpDateRow) {
                $processedRow = $inpDateRow;
                foreach (['tanggal', 'tanggal_awal', 'tanggal_akhir'] as $dateField) {
                    if (isset($processedRow[$dateField]) && $processedRow[$dateField] !== null) {
                        $date = new DateTime($processedRow[$dateField]);
                        $monthNumber = $date->format('m');
                        $indonesianMonth = $monthTranslations[$monthNumber];
                        $formattedDate = $date->format('d') . ' ' . $indonesianMonth . ' ' . $date->format('Y');
                        $processedRow[$dateField] = $formattedDate;
                    }
                }
                $processedData[] = $processedRow;
            }
            $inpDate = $processedData;
        }
        return $inpDate;
    }
}