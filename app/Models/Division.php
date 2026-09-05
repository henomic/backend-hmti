<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'full_name',
        'color',
        'color_light',
        'color_soft',
        'icon',
        'description',
        'visibility',
        'vision',
        'mission',
        'established_year',
    ];

    // ─── Relations ───────────────────────────────────────────────

    public function members()
    {
        return $this->hasMany(DivisionMember::class);
    }

    public function workPrograms()
    {
        return $this->hasMany(DivisionWorkProgram::class);
    }
    public function event()
    {
        return $this->hasMany(Event::class);
    }

    public function histories()
    {
        return $this->hasMany(DivisionHistory::class);
    }

    public function galleries()
    {
        return $this->hasMany(DivisionGallery::class);
    }

    // ─── Helper Methods ───────────────────────────────────────────


    public function permissions(): MorphMany
    {
        return $this->morphMany(
            roles_permisions::class,
            'permissionable'
        );
    }
    public function getCoordinator()
    {
        return $this->members()->with('user')->where('position', 'Koordinator')->first();
    }

    public function getViceCoordinator()
    {
        return $this->members()->with('user')->where('position', 'Wakil Koordinator')->first();
    }

    public function getStats(): array
    {
        $programs = $this->workPrograms;

        $total = $programs->count();

        return [
            'members' => $this->members->count(),
            'completed' => $programs->where('status', 'Selesai')->count(),
            'upcoming' => $programs->where('status', 'Mendatang')->count(),
            'successRate' => $total > 0
                ? round(($programs->where('status', 'Selesai')->count() / $total) * 100)
                : 0,
        ];
    }
}
