<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventDocument extends Model
{

    protected $table="documents";
    use HasFactory;

    protected $guarded = ["event_id"];

    public function documentable()
    {
    return $this->morphTo();
    }
}
