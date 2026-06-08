<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    protected $table = 'NTOS_thread';
    
    public function firstEntry()
    {
        return $this->hasOne(ThreadEntry::class, 'thread_id', 'id')
            ->where('type', 'M')
            ->oldestOfMany();
    }
}
