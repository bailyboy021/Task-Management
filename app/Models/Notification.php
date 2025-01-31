<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    use SoftDeletes;

    protected $table = "notifications";
    protected $primarykey = "id";

    protected $guarded = [
        'id'
    ];
}
