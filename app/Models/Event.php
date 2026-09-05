<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Override;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'division_id',
        'pic',
        // 'start_time',
        // 'end_time',
        // 'location',
        'type_event',
        'allowed',
        'status',
        'description',
        'created_by',
    ];

    // protected $casts = [
    //     'start_time' => 'datetime',
    //     'end_time'   => 'datetime',
    // ];

    // ─── Boot: Auto-generate event_id ─────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->event_id)) {
                $latest = self::orderBy('id', 'desc')->first();
                $number = $latest ? (intval(substr($latest->event_id, 1)) + 1) : 1;
                $event->event_id = 'E' . str_pad($number, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────────

    public function rundowns()
    {
        return $this->hasMany(EventRundown::class)->orderBy('order');
    }

    public function documents()
    {
        return $this->morphMany(EventDocument::class, 'documentable');
    }
    public function timetables()
    {
        return $this->morphMany(timetable::class, 'timetables');
    }

    public function permissions(): MorphMany
    {
        return $this->morphMany(
            roles_permisions::class,
            'permissionable'
        );
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }
}
