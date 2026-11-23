<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function latestCall(): HasOne
    {
        return $this->hasOne(Call::class)->latestOfMany();
    }
}