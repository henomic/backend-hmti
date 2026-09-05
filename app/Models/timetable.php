<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class timetable extends Model
{
    protected $table = "timetable";

    protected $guarded = ["id"];



    public function timetables()
    {
        return $this->morphTo();
    }

    public function documents()
    {
        return $this->morphMany(documents::class, 'documentable');
    }
}
