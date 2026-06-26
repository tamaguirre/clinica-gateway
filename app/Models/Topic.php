<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    use HasFactory;

    protected $table = 'help_topic';
    protected $primaryKey = 'topic_id';

    const ACTIVE = 2;
}
