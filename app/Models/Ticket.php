<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'ticket';
    protected $primaryKey = 'ticket_id';
    protected $guarded = [];

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated';

    public function thread()
    {
        return $this
            ->hasOne(Thread::class, 'object_id', 'ticket_id')
            ->where('object_type', 'T');
    }

    public function ticketData()
    {
        return $this->hasOne(TicketData::class, 'ticket_id', 'ticket_id');
    }

    public function formEntry()
    {
        return $this->hasOne(FormEntry::class, 'object_id', 'ticket_id')
            ->where('object_type', 'T');
    }
}
