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
    public function showDashboard(Request $request, EventController $eventController, UtilityController $utilityController, AESController $aesController){
        $listEvents = $eventController->dataCacheEvent(null, null, 5, ['id', 'eventid', 'eventname', 'startdate', 'nama_lokasi', 'link_lokasi'], ['id', 'event_id', 'event_name', 'start_date', 'nama_lokasi', 'link_lokasi'], true, null, true);
        if($listEvents['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$listEvents['message']], 'json_encrypt', $listEvents['statusCode']);
        }
        $listEvents = $listEvents['data'];
        $total_event = $eventController->dataCacheEvent('get_total');
        if($total_event['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$total_event['message']], 'json_encrypt', $total_event['statusCode']);
        }
        $total_event = $total_event['data'];
        $event_group = $eventController->dataCacheEvent('get_total_by_category');
        if($event_group['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$event_group['message']], 'json_encrypt', $event_group['statusCode']);
        }
        $event_group = $event_group['data'];
        $dataShow = [
            'list_events' => $listEvents,
            'total_event' => $total_event,
            'event_group' => $event_group,
        ];
        return $utilityController->getView($request, $aesController, '', ['data'=>$dataShow], 'json_encrypt');
    }
    public function showEventsList(Request $request, EventController $eventController, UtilityController $utilityController, AESController $aesController){
        $eventList = $eventController->dataCacheEvent(null, null, null, ['id', 'eventid', 'eventname', 'eventgroup', 'startdate'], ['id', 'event_id', 'event_name', 'event_group', 'start_date'], false, null, true);
        if($eventList['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['data'=>$eventList['message']], 'json_encrypt', $eventList['statusCode']);
        }
        return $utilityController->getView($request, $aesController, '', ['data'=>$eventList['data']], 'json_encrypt');
    }
    public function showEventBooked(Request $request, ThirdPartyController $thirdPartyController, UtilityController $utilityController, AESController $aesController){
        $listBooked = $thirdPartyController->pyxisAPI([
            "userid" => "demo@demo.com",
            "groupid" => "XCYTUA",
            "businessid" => "PJLBBS",
            "sql" => "SELECT id, keybusinessgroup, keyregistered, eventgroup, eventid, registrationstatus, registrationno, registrationdate, registrationname, email, mobileno, gender, qty, paymenttype, paymentid, paymentamount,  paymentdate, notes FROM event_registration",
            "order" => ""
        ],'/JQuery');
        // if($listBooked['status'] == 'error'){
        //     return $utilityController->getView($request, $aesController, '', ['message'=>$listBooked['message']], 'json_encrypt');
        // }
        return $utilityController->getView($request, $aesController, '', ['data'=>$listBooked], 'json_encrypt');
    }
    public function showProfile(Request $request, ServiceAdminController $serviceAdminController, UtilityController $utilityController, AESController $aesController){
        return $utilityController->getView($request, $aesController, '', ['data'=>self::getUserAuth($request, $serviceAdminController)], 'json_encrypt');
    }
}