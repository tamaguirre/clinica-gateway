<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    use HasFactory;

    protected $table        = 'user__cdata';
    protected $primaryKey   = 'user_id';
    public    $incrementing = false;

    // relations

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
