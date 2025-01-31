<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class TicketLog extends Model
{
    use SoftDeletes;

    protected $table = "ticket_logs";
    protected $primarykey = "id";

    protected $guarded = [
        'id'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
