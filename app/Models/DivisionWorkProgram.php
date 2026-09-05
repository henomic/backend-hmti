<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DivisionWorkProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'name',
        'date',
        'pic',
        'status',
        'progress',
        'allowed',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function permissions(): MorphMany
    {
        return $this->morphMany(
            roles_permisions::class,
            'permissionable'
        );
    }
    public function documents()
    {
        return $this->morphMany(EventDocument::class, 'documentable');
    }
    public function timetables()
    {
        return $this->morphMany(timetable::class, 'timetables');
    }
}
