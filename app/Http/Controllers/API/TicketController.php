<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Ticket;
use App\Models\Notification;
use App\Models\User;
use App\Models\TicketLog;
use Carbon\Carbon;
use Auth;

class TicketController extends BaseController
{
    /**
     * @OA\Get(
     *      path="/ticket",
     *      operationId="index",
     *      tags={"Ticket"},
     *      summary="Get list of ticket",
     *      description="Menampilkan data list tiket sesuai dengan email user",
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
    public function index(Request $request)
    {
        $ticket = Ticket::where('created_by', auth()->user()->email)->get()->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'judul' => $ticket->judul,
                'deskripsi' => $ticket->deskripsi,
                'due_date' => Carbon::parse($ticket->due_date)->format('d-m-Y'),
                'status' => $this->getStatusText($ticket->status),
                'created_by' => $ticket->created_by,
            ];
        });

        return $this->sendResponse($ticket, 'Ticket retrieved successfully.');
    }

    public function show($id)
    {
        $ticket = Ticket::find($id);
  
        if (!$ticket) {
            return $this->sendError('Ticket not found.');
        }
        
        $res = [
            'id' => $ticket->id,
            'judul' => $ticket->judul,
            'deskripsi' => $ticket->deskripsi,
            'due_date' => Carbon::parse($ticket->due_date)->format('d-m-Y'),
            'status' => $this->getStatusText($ticket->status),
            'created_by' => $ticket->created_by,
        ];

        return $this->sendResponse($res, 'Ticket retrieved successfully.');
    }

    private function getStatusText($status)
    {
        return match ($status) {
            0 => 'pending',
            1 => 'in progress',
            2 => 'completed',
            default => 'unknown',
        };
    }

    public function addTicket(Request $request)
    {
        $request->merge([
            'created_by' => auth()->user()->email,
            'due_date' => $request->due_date ?? Carbon::now()->addDays(3)->toDateString(),
        ]);

        $validator = Validator::make($request->all(), [
            "judul" => "required|string",
            "deskripsi" => "required|string|min:5",
            "asign_to" => "nullable|string|exists:users,email",
            "due_date" => "nullable|date",
            "created_by" => "required|string|exists:users,email",
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $ticket = Ticket::create($validator->validated());

        $nama = auth()->user()->name;

        // buat log
        $this->logActivity($ticket->id, 'add_ticket', "$nama create new ticket : $ticket->judul");

        return $this->sendResponse($ticket, 'New Ticket created successfully.');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => "required|int|exists:tickets,id",
            "judul" => "required|string",
            "deskripsi" => "required|string|min:5",
            "asign_to" => "nullable|string|exists:users,email",
            "due_date" => "nullable|date",
            "created_by" => "required|string|exists:users,email",
        ]);

        $ticket = Ticket::find($request->id);

        if ($ticket->created_by !== auth()->user()->email) {
            return $this->sendError('Unauthorized.', ['error' => 'You are not allowed to edit this ticket.'], 403);
        }

        $data = array(
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'asign_to' => $request->asign_to,
            'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        );

        $ticket->update($data);

        $nama = auth()->user()->name;

        // buat log
        $this->logActivity($ticket->id, 'update_ticket', "$nama Update ticket : $ticket->judul");
   
        return $this->sendResponse($ticket, 'Ticket updated successfully.');
    }

    public function destroy($id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return $this->sendError('Ticket not found.');
        }

        if ($ticket->created_by !== auth()->user()->email) {
            return $this->sendError('Unauthorized.', ['error' => 'You are not allowed to delete this ticket.'], 403);
        }

        $ticket->delete();

        $nama = auth()->user()->name;

        // buat log
        $this->logActivity($ticket->id, 'delete_ticket', "$nama Delete ticket : $ticket->judul");

        return $this->sendResponse($ticket, 'Ticket deleted successfully.');
    }

    public function asignTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => "required|int|exists:tickets,id",
            "asign_to" => "required|string|exists:users,email",
            "due_date" => "nullable|date",
        ]);

        $ticket = Ticket::find($request->id);

        if ($ticket->created_by !== auth()->user()->email) {
            return $this->sendError('Unauthorized.', ['error' => 'You are not allowed to edit this ticket.'], 403);
        }

        $data = array(
            'asign_to' => $request->asign_to,
            'due_date' => Carbon::parse($request->due_date)->format('Y-m-d'),
        );

        $ticket->update($data);

        // kirim notif
        $this->addNotif($ticket->id);

        $oldUser = auth()->user()->name;
        $newUser = User::where('email', $request->asign_to)->first();

        // buat log
        $this->logActivity($ticket->id, 'asign_ticket', "$oldUser Asign Ticket to $newUser->name");
   
        return $this->sendResponse($ticket, 'Ticket asigned successfully.');
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id" => "required|int|exists:tickets,id",
            "status" => "required|int|in:0,1,2",
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $ticket = Ticket::find($request->id);

        if (!$ticket) {
            return $this->sendError('Ticket not found.');
        }

        // Cek apakah user adalah pembuat atau penerima tugas
        if ($ticket->created_by !== auth()->user()->email && $ticket->asign_to !== auth()->user()->email) {
            return $this->sendError('Unauthorized.', ['error' => 'You are not allowed to update this ticket.'], 403);
        }

        // Validasi transisi status yang diperbolehkan
        $validTransitions = [
            0 => [1],  // Pending -> In Progress
            1 => [2],  // In Progress -> Completed
        ];

        if (!isset($validTransitions[$ticket->status]) || !in_array($request->status, $validTransitions[$ticket->status])) {
            return $this->sendError('Invalid status transition.', ['error' => 'Status update not allowed.'], 400);
        }

        $oldStatus = $this->getStatusText($ticket->status);
        $newStatus = $this->getStatusText($request->status);

        $ticket->update([
            'status' => $request->status,
            'updated_by' => auth()->user()->email
        ]);

        // buat log
        $this->logActivity($ticket->id, 'status_updated', "Status changed from $oldStatus to $newStatus");

        return $this->sendResponse($ticket, 'Ticket status updated successfully.');
    }


    private function addNotif($id)
    {
        $ticket = Ticket::find($id);
        $data_notif = array(
            'message' => Auth::user()->name." has Assign to you : ".$ticket->judul,
            'action' => "Asign",
            'link_url' => 'view-ticket/'.$ticket->id,
            'email_user' => $ticket->asign_to,
            'status_read' => 0,
            'created_by' => Auth::user()->email
        );

        $notifikasi = Notification::create($data_notif);
        return $this->sendResponse($notifikasi, 'New Notification created successfully.');
    }

    private function logActivity($ticket_id, $action, $description)
    {
        TicketLog::create([
            'ticket_id' => $ticket_id,
            'action' => $action,
            'description' => $description,
            'performed_by' => auth()->user()->email,
        ]);
    }
    
}
