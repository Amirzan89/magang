<?php
namespace App\Http\Controllers\Pages;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AdminController as ServiceAdminController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Security\AESController;
use App\Http\Controllers\Services\EventController;
use App\Http\Controllers\Services\ThirdPartyController;
use Illuminate\Http\Request;
class AdminController extends Controller
{
    public static function getUserAuth(Request $request, ServiceAdminController $serviceAdminController){
        $userAuth = $request->input('user_auth');
        unset($userAuth['id_user']);
        $fotoStore = $serviceAdminController::getFotoProfile($request);
        if($fotoStore['status'] == 'error'){
            $userAuth['foto'] = null;
        }else{
            $userAuth['foto'] = $fotoStore['data'];
        }
        return $userAuth;
    }
    public function showDashboard(Request $request, EventController $eventController, AESController $aesController){
        $listEvents = $eventController->dataCacheEvent(null, null, 5, ['id', 'eventid', 'eventname', 'startdate', 'nama_lokasi', 'link_lokasi'], ['id', 'event_id', 'event_name', 'start_date', 'nama_lokasi', 'link_lokasi'], true, null, true);
        if($listEvents['status'] == 'error'){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>$listEvents['message']],$request->input('key'), $request->input('iv'))], $listEvents['statusCode']);
        }
        $listEvents = $listEvents['data'];
        $total_event = $eventController->dataCacheEvent('get_total');
        if($total_event['status'] == 'error'){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>$total_event['message']],$request->input('key'), $request->input('iv'))], $total_event['statusCode']);
        }
        $total_event = $total_event['data'];
        $event_group = $eventController->dataCacheEvent('get_total_by_category');
        if($event_group['status'] == 'error'){
            return response()->json(['status'=>'error','message'=>$aesController->encryptResponse(['message'=>$event_group['message']],$request->input('key'), $request->input('iv'))], $event_group['statusCode']);
        }
        $event_group = $event_group['data'];
        $dataShow = [
            'list_events' => $listEvents,
            'total_event' => $total_event,
            'event_group' => $event_group,
        ];
        return response()->json(['status'=>'success','message'=>$aesController->encryptResponse(['data'=>$dataShow],$request->input('key'), $request->input('iv'))]);
    }
    public function showEventBooked(Request $request, AESController $aesController){
        $listBooked = app()->make(ThirdPartyController::class)->pyxisAPI([
            "userid" => "demo@demo.com",
            "groupid" => "XCYTUA",
            "businessid" => "PJLBBS",
            "sql" => "SELECT id, keybusinessgroup, keyregistered, eventgroup, eventid, registrationstatus, registrationno, registrationdate, registrationname, email, mobileno, gender, qty, paymenttype, paymentid, paymentamount,  paymentdate, notes FROM event_registration",
            "order" => ""
        ],'/JQuery');
        return response()->json(['status'=>'success','message'=>$aesController->encryptResponse(['data'=>$listBooked],$request->input('key'), $request->input('iv'))]);
    }
    public function showProfile(Request $request, ServiceAdminController $serviceAdminController, AESController $aesController){
        return response()->json(['status'=>'success','message'=>$aesController->encryptResponse(['data'=>self::getUserAuth($request, $serviceAdminController)],$request->input('key'), $request->input('iv'))]);
    }
}