<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class roles_permisions extends Model
{
    protected $table = 'role_permissions';
    protected $guarded = ["id"];

    public function roles(): BelongsTo
    {
        return $this->belongsTo(roles::class);
    }


    public function permissionable()
    {
        return $this->morphTo();
    }
}
