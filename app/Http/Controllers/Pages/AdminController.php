<?php
namespace App\Http\Controllers\Pages;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Security\AESController;
use App\Http\Controllers\Services\EventController AS ServiceEventController;
use App\Http\Controllers\Services\ThirdPartyController;
use Illuminate\Http\Request;
class AdminController extends Controller
{
    private static function getUserAuth(Request $request){
        $userAuth = $request->input('user_auth');
        unset($userAuth['id_user']);
        if(!empty($userAuth['foto'])){
            $userAuth['foto'] = 'exist';
        }
        return $userAuth;
    }
    public function showDashboard(Request $request){
        $eventController = app()->make(ServiceEventController::class);
        $listEvents = $eventController->dataCacheEvent(null, null, 5, ['id', 'eventid', 'eventname', 'startdate', 'nama_lokasi', 'link_lokasi'], ['id', 'event_id', 'event_name', 'start_date', 'nama_lokasi', 'link_lokasi'], true, null, true);
        if($listEvents['status'] == 'error'){
            $codeRes = $listEvents['statusCode'];
            unset($listEvents['statusCode']);
            return response()->json($listEvents, $codeRes);
        }
        $listEvents = $listEvents['data'];
        $total_event = $eventController->dataCacheEvent('get_total');
        if($total_event['status'] == 'error'){
            $codeRes = $total_event['statusCode'];
            unset($total_event['statusCode']);
            return response()->json($total_event, $codeRes);
        }
        $total_event = $total_event['data'];
        $event_group = $eventController->dataCacheEvent('get_total_by_category');
        if($event_group['status'] == 'error'){
            $codeRes = $event_group['statusCode'];
            unset($event_group['statusCode']);
            return response()->json($event_group, $codeRes);
        }
        $event_group = $event_group['data'];
        $dataShow = [
            'user_auth' => self::getUserAuth($request),
            'list_events' => $listEvents,
            'total_event' => $total_event,
            'event_group' => $event_group,
        ];
        return UtilityController::getView('', app()->make(AESController::class)->encryptResponse($dataShow, $request->input('key'), $request->input('iv')), 'json_encrypt');
    }
    public function showEventBooked(Request $request, AESController $aesController){
        $listBooked = app()->make(ThirdPartyController::class)->pyxisAPI([
            "userid" => "demo@demo.com",
            "groupid" => "XCYTUA",
            "businessid" => "PJLBBS",
            "sql" => "SELECT id, keybusinessgroup, keyregistered, eventgroup, eventid, registrationstatus, registrationno, registrationdate, registrationname, email, mobileno, gender, qty, paymenttype, paymentid, paymentamount,  paymentdate, notes FROM event_registration",
            "order" => ""
        ],'/JQuery');
        $dataShow = [
            'user_auth' => self::getUserAuth($request),
            'list_booked' => $listBooked,
        ];
        return UtilityController::getView('', $aesController->encryptResponse($dataShow, $request->input('key'), $request->input('iv')), 'json_encrypt');
    }
    public function showProfile(Request $request, AESController $aesController){
        return UtilityController::getView('', $aesController->encryptResponse(self::getUserAuth($request), $request->input('key'), $request->input('iv')), 'json_encrypt');
    }
}