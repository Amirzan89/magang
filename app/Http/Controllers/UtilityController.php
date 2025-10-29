<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Security\AESController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use DateTime;
class UtilityController extends Controller
{
    public static function getView(Request $request, AESController $aesController, $name = null, $data = [], $cond = null, $statusCode = 200){
        $comps = function($domain) use ($request, $aesController, $data, $cond, $statusCode){
            // return response()->json(base64_encode(json_encode($data)));
            if(is_array($cond) && array_key_exists('redirect', $cond)){
                if(in_array('isGoogleRedirect', $cond['cond']) || !$domain){
                    setCookie('__INITIAL_COSTUM_STATE__', base64_encode(json_encode($data)), 0, '/', null, false, false);
                    return redirect(env('FRONTEND_URL', 'http://localhost:3000') . $cond['redirect']);
                }
                if(in_array('isForgotPasswordRedirect', $cond['cond']) || !$domain){
                    setCookie('__INITIAL_COSTUM_STATE__', base64_encode(json_encode($data)), 0, '/', null, false, false);
                    return redirect(env('FRONTEND_URL', 'http://localhost:3000') . $cond['redirect']);
                }
            }
            if($domain && is_array($cond) && isset($cond['cond']) && in_array('view', $cond['cond'])){
                $indexPath = public_path('index.html');
                if(!File::exists($indexPath)){
                    return response()->json(['error' => 'Page not found'], 404);
                }
                $htmlContent = File::get($indexPath);
                $htmlContent = str_replace('<body>', '<body><script>const csrfToken = "' . csrf_token() . '";</script>', $htmlContent);
                $htmlContent = str_replace('</head>', '<script>window.__INITIAL_COSTUM_STATE__ = ' . json_encode($data) . '</script></head>', $htmlContent);
                return response($htmlContent)->cookie('XSRF-TOKEN', csrf_token(), 0, '/', null, false, true);
            }
            if(is_array($cond) && array_key_exists('json_cookie', $cond)){
                setCookie('__INITIAL_COSTUM_STATE__', base64_encode($cond['json_cookie']), 0, '/', null, false, false);
                return response()->json(['status' => $statusCode ? 'success' : 'error', 'data' => $data], $statusCode);
            }else if(is_string($cond) && $cond == 'only_cookie'){
                setCookie('__INITIAL_COSTUM_STATE__', base64_encode(json_encode($data)), 0, '/', null, false, false);
                return response()->json(['status' => $statusCode ? 'success' : 'error'], $statusCode);
            }else if(is_string($cond) && $cond == 'json'){
                return response()->json(['status' => $statusCode == 200 ? 'success' : 'error', 'data' => $data], $statusCode);
            }else if(is_string($cond) && $cond == 'json_encrypt'){
                return response()->json(['status' => $statusCode == 200 ? 'success' : 'error', 'message' => $aesController->encryptResponse($data, $request->input('key'), $request->input('iv'))], $statusCode);
            }
            return response()->json(['status' => 'error', 'message' => 'invalid request'], 400);
        };
        $env = env('APP_VIEW', 'blade');
        if($env == 'blade'){
            return view($name);
        }else if($env == 'inertia'){
            return inertia($name);
        }else if($env == 'vue'){
            return $comps(env('APP_DOMAIN', 'same') == 'same');
        }
    }
    public static function randomDateInRange($startDate, $endDate, $format = 'Y-m-d'){
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);
        $randomTimestamp = rand($start->timestamp, $end->timestamp);
        return Carbon::createFromTimestamp($randomTimestamp)->format($format);
    }
    public static function changeMonth($inpDate){
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
        $formatDate = function ($dateStr) use ($monthTranslations){
            if(empty($dateStr) || strtotime($dateStr) === false){
                return $dateStr;
            }
            $date = new DateTime($dateStr);
            $monthNumber = $date->format('m');
            $indonesianMonth = $monthTranslations[$monthNumber];
            return $date->format('d') . ' ' . $indonesianMonth . ' ' . $date->format('Y');
        };
        if(is_string($inpDate)){
            return $formatDate($inpDate);
        }
        $isMulti = function(array $arr): bool {
            if($arr === []) return false;
            return count(array_filter($arr, 'is_array')) === count($arr);
        };
        if(!$isMulti($inpDate)){
            foreach($inpDate as $key => $value){
                if($value !== null){
                    $inpDate[$key] = $formatDate($value);
                }
            }
        }else{
            $processedData = [];
            foreach($inpDate as $row){
                $processedRow = $row;
                foreach($processedRow as $key => $value){
                    if($value !== null){
                        $processedRow[$key] = $formatDate($value);
                    }
                }
                $processedData[] = $processedRow;
            }
            $inpDate = $processedData;
        }
        return $inpDate;
    }
    public static function compactUuid(string $uuid): string {
        return str_replace('-', '', $uuid);
    }
    public static function uuidNormalize($uuidCompact){
        if(strlen($uuidCompact) === 32){
            $result = substr($uuidCompact, 0, 8) . '-' .
                substr($uuidCompact, 8, 4) . '-' .
                substr($uuidCompact, 12, 4) . '-' .
                substr($uuidCompact, 16, 4) . '-' .
                substr($uuidCompact, 20);
            if(!Uuid::isValid($result)){
                return ['status' => 'error', 'message' => 'Invalid uuid'];
            }
            return ['status' => 'success', 'data' => $result];
        }
        return ['status' => 'error', 'message' => 'Invalid uuid'];
    }
    private static function getExtensionFromMime($mime){
        static $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];
        return $map[$mime] ?? 'bin';
    }
    public function base64File(Request $request): UploadedFile|array|null {
        $input = $request->all();
        $uploadedFiles = [];
        $makeUploadedFile = function (string $key, array $fileItem): UploadedFile {
            $meta = $fileItem['meta'];
            $base64 = $fileItem['data'];
            $decoded = base64_decode($base64, true);
            if($decoded === false){
                throw new \RuntimeException("Invalid base64 for {$key}");
            }
            $ext = self::getExtensionFromMime($meta['type'] ?? 'application/octet-stream');
            $tmpPath = sys_get_temp_dir() . '/' . uniqid($key . '_', true) . '.' . $ext;
            file_put_contents($tmpPath, $decoded);
            return new UploadedFile($tmpPath, $meta['name'] ?? basename($tmpPath), $meta['type'] ?? 'application/octet-stream', $meta['size'] ?? null, true);
        };
        foreach($input as $key => $value){
            if(is_array($value) && isset($value['data'], $value['meta'])){
                $uploadedFiles[$key] = $makeUploadedFile($key, $value);
            }else if(is_array($value) && isset($value[0]) && is_array($value[0]) && isset($value[0]['data'], $value[0]['meta'])){
                foreach($value as $i => $fileItem){
                    $uploadedFiles[$key][$i] = $makeUploadedFile($key . "_{$i}", $fileItem);
                }
            }
        }
        if(empty($uploadedFiles)){
            return null;
        }
        if(count($uploadedFiles) === 1){
            return reset($uploadedFiles);
        }
        return $uploadedFiles;
    }
}