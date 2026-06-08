<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    protected $table = 'NTOS_user__cdata';

    // relations

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
