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

    /**
     * @OA\Post(
     *      path="/view-ticket/{id}",
     *      operationId="show",
     *      tags={"Ticket"},
     *      summary="Get detail of a ticket",
     *      description="Menampilkan detail tiket berdasarkan ID",
     *      security={ {"sanctum": {} }},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID dari tiket yang ingin diambil",
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
     *                 @OA\Property(property="judul", type="string", example="Bug Report"),
     *                 @OA\Property(property="deskripsi", type="string", example="Ada bug di halaman login"),
     *                 @OA\Property(property="due_date", type="string", format="date", example="01-02-2025"),
     *                 @OA\Property(property="status", type="string", example="Open"),
     *                 @OA\Property(property="created_by", type="string", example="user@example.com")
     *             ),
     *             @OA\Property(property="message", type="string", example="Ticket retrieved successfully.")
     *         )
     *      ),
     *      @OA\Response(
     *         response=404,
     *         description="Ticket not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ticket not found.")
     *         )
     *      )
     * )
     */
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

    /**
     * @OA\Post(
     *      path="/add-ticket",
     *      operationId="addTicket",
     *      tags={"Ticket"},
     *      summary="Create a new ticket",
     *      description="Menambahkan tiket baru ke dalam sistem",
     *      security={ {"sanctum": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"judul", "deskripsi"},
     *              @OA\Property(property="judul", type="string", example="Bug Report"),
     *              @OA\Property(property="deskripsi", type="string", example="Ada bug di halaman login"),
     *              @OA\Property(property="asign_to", type="string", example="user@example.com"),
     *              @OA\Property(property="due_date", type="string", format="date", example="2025-02-04")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="New Ticket created successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="judul", type="string", example="Bug Report"),
     *                  @OA\Property(property="deskripsi", type="string", example="Ada bug di halaman login"),
     *                  @OA\Property(property="asign_to", type="string", example="user@example.com"),
     *                  @OA\Property(property="due_date", type="string", format="date", example="2025-02-04"),
     *                  @OA\Property(property="created_by", type="string", example="admin@example.com")
     *              ),
     *              @OA\Property(property="message", type="string", example="New Ticket created successfully.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Validation Error",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validation Error."),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="judul", type="array",
     *                      @OA\Items(type="string", example="The judul field is required.")
     *                  ),
     *                  @OA\Property(property="deskripsi", type="array",
     *                      @OA\Items(type="string", example="The deskripsi field must be at least 5 characters.")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
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

    /**
     * @OA\Post(
     *      path="/edit-ticket",
     *      operationId="update",
     *      tags={"Ticket"},
     *      summary="Update an existing ticket",
     *      description="Memperbarui data tiket berdasarkan ID tiket",
     *      security={ {"sanctum": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"id", "judul", "deskripsi", "created_by"},
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="judul", type="string", example="Bug Report Updated"),
     *              @OA\Property(property="deskripsi", type="string", example="Deskripsi tiket telah diperbarui."),
     *              @OA\Property(property="asign_to", type="string", example="user@example.com"),
     *              @OA\Property(property="due_date", type="string", format="date", example="2025-02-05"),
     *              @OA\Property(property="created_by", type="string", example="admin@example.com")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Ticket updated successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="judul", type="string", example="Bug Report Updated"),
     *                  @OA\Property(property="deskripsi", type="string", example="Deskripsi tiket telah diperbarui."),
     *                  @OA\Property(property="asign_to", type="string", example="user@example.com"),
     *                  @OA\Property(property="due_date", type="string", format="date", example="2025-02-05"),
     *                  @OA\Property(property="created_by", type="string", example="admin@example.com")
     *              ),
     *              @OA\Property(property="message", type="string", example="Ticket updated successfully.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Validation Error",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validation Error."),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="judul", type="array",
     *                      @OA\Items(type="string", example="The judul field is required.")
     *                  ),
     *                  @OA\Property(property="deskripsi", type="array",
     *                      @OA\Items(type="string", example="The deskripsi field must be at least 5 characters.")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unauthorized."),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="error", type="string", example="You are not allowed to edit this ticket.")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Ticket not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Ticket not found.")
     *          )
     *      )
     * )
     */
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

    /**
     * @OA\Delete(
     *      path="/delete-ticket/{id}",
     *      operationId="destroy",
     *      tags={"Ticket"},
     *      summary="Delete a ticket",
     *      description="Menghapus tiket berdasarkan ID tiket",
     *      security={ {"sanctum": {} }},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID dari tiket yang ingin dihapus",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Ticket deleted successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="judul", type="string", example="Bug Report"),
     *                  @OA\Property(property="deskripsi", type="string", example="Ada bug di halaman login"),
     *                  @OA\Property(property="asign_to", type="string", example="user@example.com"),
     *                  @OA\Property(property="due_date", type="string", format="date", example="2025-02-05"),
     *                  @OA\Property(property="created_by", type="string", example="admin@example.com")
     *              ),
     *              @OA\Property(property="message", type="string", example="Ticket deleted successfully.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unauthorized."),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="error", type="string", example="You are not allowed to delete this ticket.")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Ticket not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Ticket not found.")
     *          )
     *      )
     * )
     */
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

    /**
     * @OA\Post(
     *      path="/asign-ticket",
     *      operationId="asignTicket",
     *      tags={"Asign Ticket"},
     *      summary="Assign a ticket to a user",
     *      description="Mengalihkan tugas tiket ke pengguna lain dengan menentukan email penerima",
     *      security={ {"sanctum": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"id", "asign_to"},
     *              @OA\Property(property="id", type="integer", example=1, description="ID dari tiket yang ingin dialokasikan"),
     *              @OA\Property(property="asign_to", type="string", example="user@example.com", description="Email dari pengguna yang ditugaskan"),
     *              @OA\Property(property="due_date", type="string", format="date", example="2025-02-10", description="(Opsional) Tanggal jatuh tempo tiket")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Ticket assigned successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="asign_to", type="string", example="user@example.com"),
     *                  @OA\Property(property="due_date", type="string", format="date", example="2025-02-10")
     *              ),
     *              @OA\Property(property="message", type="string", example="Ticket assigned successfully.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unauthorized."),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="error", type="string", example="You are not allowed to edit this ticket.")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Ticket not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Ticket not found.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation Error",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validation Error."),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="asign_to", type="array",
     *                      @OA\Items(type="string", example="The selected email does not exist.")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
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

    /**
     * @OA\Post(
     *      path="/update-ticket-status",
     *      operationId="updateStatus",
     *      tags={"Management Status"},
     *      summary="Update ticket status",
     *      description="Memperbarui status tiket berdasarkan aturan transisi status yang diperbolehkan",
     *      security={ {"sanctum": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"id", "status"},
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="status", type="integer", example=1, description="0 = Pending, 1 = In Progress, 2 = Completed")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Ticket status updated successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer", example=1),
     *                  @OA\Property(property="status", type="integer", example=1, description="0 = Pending, 1 = In Progress, 2 = Completed"),
     *                  @OA\Property(property="updated_by", type="string", example="user@example.com")
     *              ),
     *              @OA\Property(property="message", type="string", example="Ticket status updated successfully.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid status transition or validation error",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Validation Error."),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="status", type="array",
     *                      @OA\Items(type="string", example="Status update not allowed.")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unauthorized."),
     *              @OA\Property(property="errors", type="object",
     *                  @OA\Property(property="error", type="string", example="You are not allowed to update this ticket.")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Ticket not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Ticket not found.")
     *          )
     *      )
     * )
     */
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
