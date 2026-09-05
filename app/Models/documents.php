<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class documents extends Model
{
    protected $table = "documents";
    protected $guarded = ['id'];


    public function documents()
    {
        return $this->morphTo();
    }
}
