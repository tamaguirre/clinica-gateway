<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormEntry extends Model
{
    protected $table = 'form_entry';
    public $timestamps = false;

    public function valueWithPriority()
    {
        return $this->hasOne(FormEntryValues::class, 'entry_id', 'id')
            ->whereNotNull('value_id');
    }
}
