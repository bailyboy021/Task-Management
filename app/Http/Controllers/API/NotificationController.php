<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Notification;
use Carbon\Carbon;
use Auth;

class NotificationController extends BaseController
{
    public function index(Request $request)
    {
        $notif = Notification::where('email_user', auth()->user()->email)->get()->map(function ($notif) {
            return [
                'id' => $notif->id,
                'message' => $notif->message,
                'action' => $notif->action,
                'link_url' => $notif->link_url,
                'status_read' => $notif->status_read==0?'Not Read':'Read',
                'read_on' => $notif->read_on==null?'':Carbon::parse($notif->read_on)->format('d-m-Y'),
            ];
        });

        return $this->sendResponse($notif, 'Notification retrieved successfully.');
    }

    public function show($id)
    {
        $notif = Notification::find($id);
  
        if (!$notif) {
            return $this->sendError('Notification not found.');
        }
        
        $res = [
            'id' => $notif->id,
            'message' => $notif->message,
            'action' => $notif->action,
            'link_url' => $notif->link_url,
            'status_read' => $notif->status_read=0?'Not Read':'Read',
            'read_on' => $notif->read_on=null?'':Carbon::parse($notif->read_on)->format('d-m-Y'),
        ];

        // baca notif
        $this->readNotif($notif->id);

        return $this->sendResponse($res, 'Notification read successfully.');
    }

    private function readNotif($id)
    {
        $notifikasi = Notification::find($id);

        $data = array(
            'status_read' => 1,
            'read_on' => Carbon::now()->format('Y-m-d'),
        );

        $notifikasi->update($data);

        return $this->sendResponse($notifikasi, 'Notification read successfully.');
    }
}
