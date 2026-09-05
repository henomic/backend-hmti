<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class roles extends Model
{
    protected $table = 'roles';
    protected $guarded = ["id"];

    public function permision(): HasMany
    {
        return $this->hasMany(roles_permisions::class, 'role_id', 'id');
    }
}
