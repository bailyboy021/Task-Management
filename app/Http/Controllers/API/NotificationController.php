<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Notification;
use Carbon\Carbon;
use Auth;

class NotificationController extends BaseController
{
    /**
     * @OA\Get(
     *      path="/notif",
     *      operationId="indexNotif",
     *      tags={"Notification"},
     *      summary="Get list of notification",
     *      description="Menampilkan data list notifikasi sesuai dengan email user",
     *      security={ {"sanctum": {} }},
     *      @OA\Response(
     *         response=200,
     *         description="successful operation"
     *      ),
     *      @OA\Response(
     *         response=400,
     *         description="Invalid status value"
     *      ),
     *  )
     */
    public function indexNotif(Request $request)
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

    /**
     * @OA\Post(
     *      path="/view-notif/{id}",
     *      operationId="showNotif",
     *      tags={"Notification"},
     *      summary="Get detail of a notif",
     *      description="Menampilkan detail notifikasi berdasarkan ID",
     *      security={ {"sanctum": {} }},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID dari notif yang ingin diambil",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *         response=200,
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="message", type="string", example="Boim has Assign to you : Lorem EDIT"),
     *                 @OA\Property(property="action", type="string", example="Asign"),
     *                 @OA\Property(property="link_url", type="string", example="view-ticket/1"),
     *                 @OA\Property(property="status_read", type="string", example="Not Read"),
     *                 @OA\Property(property="read_on", type="string", format="date", example="31-01-2025")
     *             ),
     *             @OA\Property(property="message", type="string", example="Notification retrieved successfully.")
     *         )
     *      ),
     *      @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Notification not found.")
     *         )
     *      )
     * )
     */
    public function showNotif($id)
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
