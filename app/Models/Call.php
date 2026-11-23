<?php

namespace App\Models;

use App\Enums\CallResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['company_id', 'meeting_id', 'result'];

    protected function casts(): array
    {
        return [
            'result' => CallResult::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}