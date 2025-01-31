<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Ticket extends Model
{
    use SoftDeletes;

    protected $table = "tickets";
    protected $primarykey = "id";

    protected $guarded = [
        'id'
    ];
}
