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
        $listEvents = $eventController->dataCacheEvent(null, [
            'id' => null,
            'limit' => 5,
            'col' => ['id', 'eventid', 'eventname', 'startdate', 'nama_lokasi', 'link_lokasi'],
            'alias' => ['id', 'event_id', 'event_name', 'start_date', 'nama_lokasi', 'link_lokasi'],
            'formatDate' => true,
            'shuffle' => true
        ]);
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
    public function showEventsList(Request $request, EventController $eventController, UtilityController $utilityController, AESController $aesController){
        $eventList = $eventController->dataCacheEvent(null, [
            'id' => null,
            'limit' => null,
            'col' => ['id', 'eventid', 'eventname', 'eventgroup', 'startdate'],
            'alias' => ['id', 'event_id', 'event_name', 'event_group', 'start_date'],
            'formatDate' => false,
            'shuffle' => true
        ]);
        if($eventList['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['data'=>$eventList['message']], 'json_encrypt', $eventList['statusCode']);
        }
        return $utilityController->getView($request, $aesController, '', ['data'=>$eventList['data']], 'json_encrypt');
    }
    public function showEventTambah(Request $request, EventController $eventController, UtilityController $utilityController, AESController $aesController){
        $categoryData = $eventController->dataCacheEventGroup(['eventgroup', 'eventgroupname'], ['value', 'name'], null, false);
        if($categoryData['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$categoryData['message']], 'json_encrypt', $categoryData['statusCode']);
        }
        return $utilityController->getView($request, $aesController, '', ['data'=>$categoryData['data']], 'json_encrypt');
    }
    public function showEventAdminDetail(Request $request, EventController $eventController, UtilityController $utilityController, AESController $aesController, $id){
        $categoryData = $eventController->dataCacheEventGroup(['eventgroup', 'eventgroupname'], ['value', 'name'], null, false);
        if($categoryData['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['message'=>$categoryData['message']], 'json_encrypt', $categoryData['statusCode']);
        }
        $eventDetail = $eventController->dataCacheEvent(null, [
            'id' => $id,
            'limit' => null,
            'col' => ['id', 'eventid', 'eventgroup', 'eventname', 'eventdescription', 'startdate', 'enddate', 'price', 'quota', 'inclusion', 'imageicon_1', 'imageicon_2', 'imageicon_3', 'imageicon_4', 'imageicon_5', 'imageicon_6', 'imageicon_7', 'imageicon_8', 'imageicon_9'],
            'alias' => ['id', 'event_id', 'event_group', 'event_name', 'event_description', 'start_date', 'end_date', 'price', 'quota', 'inclusion', 'imageicon_1', 'imageicon_2', 'imageicon_3', 'imageicon_4', 'imageicon_5', 'imageicon_6', 'imageicon_7', 'imageicon_8', 'imageicon_9'],
        ]);
        if($eventDetail['status'] == 'error'){
            return $utilityController->getView($request, $aesController, '', ['data'=>$eventDetail['message']], 'json_encrypt', $eventDetail['statusCode']);
        }
        $eventDetail = $eventDetail['data'];
        $foto = [];
        $imgKeys = ['imageicon_1', 'imageicon_2', 'imageicon_3', 'imageicon_4', 'imageicon_5', 'imageicon_6', 'imageicon_7', 'imageicon_8', 'imageicon_9'];
        foreach($imgKeys as $key){
            $val = $eventDetail[$key] ?? null;
            if(empty($val) || is_null($val)){
                $foto[] = null;
                unset($eventDetail[$key]);
                continue;
            }
            if(preg_match('/^https?:\/\//i', $val)){
                $foto[] = $val;
                unset($eventDetail[$key]);
                continue;
            }
            $filePath = public_path('img/events/' . $eventDetail[$key]);
            $foto[] = file_exists($filePath) && is_file($filePath) ? $eventDetail[$key] : null;
            unset($eventDetail[$key]);
        }
        $eventDetail['foto'] = $foto;
        $dataShow = [
            'category' => $categoryData['data'],
            'event' => $eventDetail,
        ];
        return $utilityController->getView($request, $aesController, '', ['data'=>$dataShow], 'json_encrypt');
    }
    public function showProfile(Request $request, ServiceAdminController $serviceAdminController, UtilityController $utilityController, AESController $aesController){
        return $utilityController->getView($request, $aesController, '', ['data'=>self::getUserAuth($request, $serviceAdminController)], 'json_encrypt');
    }
}