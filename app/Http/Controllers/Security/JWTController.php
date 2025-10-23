<?php
namespace App\Http\Controllers\Security;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UtilityController;
use App\Models\Admin;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use UnexpectedValueException;
use DomainException;
use InvalidArgumentException;
use Carbon\Carbon;
class JWTController extends Controller
{
    private static $tempFile;
    public function __construct(){
        self::$tempFile = storage_path('seeders/temp/table .json');
    }
    //check token in database is exist 
    public function checkExistRefreshToken($token){
        if(empty($token) || is_null($token)){
            return ['status'=>'error','message'=>'token empty'];
        }
        return RefreshToken::select("id_user")->whereRaw("BINARY token = ?",[$token])->limit(1)->exists();
    }
    //save token refresh to database
    public function createJWTWebsite($refreshToken, $utilityController, $idUser){
        try{
            $uuid = User::select('uuid')->where('id_user', $idUser)->first();
            if(is_null($uuid)){
                return ['status'=>'error','messsage'=>'User not found','code'=>400];
            }
            $uuid = $uuid['uuid'];
            $number = RefreshToken::where('id_user', $idUser)->count();
            $exp = time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED'));
            $expRefresh = time() + intval(env('JWT_REFRESH_TOKEN_EXPIRED'));
            $secretKey = env('JWT_SECRET');
            $secretRefreshKey = env('JWT_SECRET_REFRESH_TOKEN');
            $compact_uuid = $utilityController::compactUuid($uuid);
            if($number >= 3){
                if(!RefreshToken::where('id_user', $idUser)->where('number', 1)->delete()){
                    return ['status'=>'error','message'=>'error delete old refresh token', 'code'=>500];
                }
                for($i = 1; $i <= 3; $i++){
                    RefreshToken::where('id_user', $idUser)->where('number', $i)->update(['number' => $i - 1]);
                }
                $payloadRefresh = ['user' => $compact_uuid, 'exp' => $expRefresh, 'number' => 3];
                $Rtoken = JWT::encode($payloadRefresh, $secretRefreshKey, 'HS512');
                $payload = ['user' => $compact_uuid, 'exp' => $exp, 'number' => 3];
                $token = JWT::encode($payload, $secretKey,'HS512');
                $refreshToken->token = $Rtoken;
                $refreshToken->number = 3;
                $refreshToken->id_user = $idUser;
                $refreshToken->created_at = Carbon::now();
                $refreshToken->updated_at = Carbon::now();
                if(!$refreshToken->save()){
                    return ['status'=>'error','message'=>'error saving token','code'=>500];
                }
                return ['status'=>'success','data'=> [
                    'token' => $token,
                    'refresh' => $Rtoken
                ], 'number'=>3];
            }else{
                $number = $number > 0 ? $number + 1 : 1;
                $refreshToken->id_user = $idUser;
                $payloadRefresh = ['user' => $compact_uuid, 'exp' => $expRefresh, 'number' => $number];
                $Rtoken = JWT::encode($payloadRefresh, $secretRefreshKey, 'HS512');
                $refreshToken->token = $Rtoken;
                $payload = ['user' => $compact_uuid, 'exp' => $exp, 'number' => $number];
                $token = JWT::encode($payload, $secretKey,'HS512');
                $refreshToken->number = $number;
                $refreshToken->created_at = Carbon::now();
                $refreshToken->updated_at = Carbon::now();
                if(!$refreshToken->save()){
                    return ['status'=>'error','message'=>'error saving token','code'=>500];
                }
                return ['status'=>'success', 'data'=> [
                    'token' => $token,
                    'refresh' => $Rtoken
                ], 'number' => $number ];
            }
        }catch(UnexpectedValueException  $e){
            return ['status'=>'error','message'=>$e->getMessage()];
        }
    }
    public function decode($request, $utilityController, $token, $opt){
        try{
            $decoded = json_decode(json_encode(JWT::decode($token, new Key(env($opt), 'HS512'))), true);
            if(!isset($decoded['user']) && !isset($decoded['exp']) && !isset($decoded['number'])){
                return ['status'=>'error','message'=>'Invalid JWT','code'=>500];
            }
            $uuid = $utilityController->uuidNormalize($decoded['user']);
            if($uuid['status'] == 'error'){
                return $uuid;
            }
            $decoded['user'] = $uuid['data'];
            return ['status'=>'success','data'=>$decoded];
        }catch(ExpiredException $e){
            return ['status'=>'error','message'=>$e->getMessage()];
        } catch (SignatureInvalidException $e) {
            return ['status'=>'error','message'=>$e->getMessage()];
        } catch (BeforeValidException $e) {
            return ['status'=>'error','message'=>$e->getMessage()];
        }catch(UnexpectedValueException $e){
            return ['status'=>'error','message'=>$e->getMessage()];
        } catch (InvalidArgumentException $e) {
            return ['status'=>'error','message'=>$e->getMessage()];
        } catch (DomainException $e) {
            return ['status'=>'error','message'=>$e->getMessage()];
        } catch (\Exception $e) {
            return ['status'=>'error','message'=>$e->getMessage()];
        }
    }
    // public function decodeGoogleLogin($credential){
    //     try {
    //         $decodedToken = JWT::decode($credential, JWK::parseKeySet(Http::get('https://www.googleapis.com/oauth2/v3/certs')->json()));
    //         if ($decodedToken->aud !== env('GOOGLE_CLIENT_ID')) {
    //             return ['status' => 'error', 'message' => 'Invalid audience'];
    //         }
    //         if ($decodedToken->exp < time()) {
    //             return ['status' => 'error', 'message' => 'Token has expired'];
    //         }
    //         return ['status' => 'success', 'data' => $decodedToken];
    //     } catch (\Exception $e) {
    //         return ['status' => 'error', 'message' => $e->getMessage()];
    //     }
    // }
    public function updateTokenWebsite($inp){
        try{
            return ['status'=>'success','data'=> JWT::encode(['user'=>$inp['user'], 'exp'=>time() + intval(env('JWT_ACCESS_TOKEN_EXPIRED')), 'number'=>$inp['number']], env('JWT_SECRET'),'HS512')];
        }catch(UnexpectedValueException $e){
            return ['status'=>'error','message'=>$e->getMessage()];
        }
    }
    //delete refresh token website 
    public function deleteRefreshToken($idUser, $number = null){
        $query = RefreshToken::where('uuid', $idUser);
        if($number !== null) $query->where('number', $number);
        $query->delete();
    }

    public function rotateKEYJWT(){
        $currentMonth = now()->format('w-m-Y');
        if (Cache::get('jwt_key_month') !== $currentMonth) {
            File::
            Cache::put('aes_month', $currentMonth);
        }
        return ['status'=>'success','message'=>'ddd'];
    }
}
?>