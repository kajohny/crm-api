<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manager extends Model
{
    public $timestamps = false;
    protected $fillable = ['name'];

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }
}