<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormEntryValues extends Model
{
    protected $table = 'ost_form_entry_values';
    protected $guarded = [];
    protected $primaryKey = 'entry_id';
    public $timestamps = false;
}
