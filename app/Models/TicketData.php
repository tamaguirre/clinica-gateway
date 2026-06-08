<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketData extends Model
{
    protected $table = 'ost_ticket__cdata';
    protected $primaryKey = 'ticket_id';
    public $timestamps = false;
    protected $guarded = [];
}
