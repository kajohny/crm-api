<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'bin', 'phone'];

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }
}