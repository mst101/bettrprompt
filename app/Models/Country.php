<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Country extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'continent_id',
        'currency_id',
        'language_id',
        'first_day_of_week',
        'uses_miles',
        'name',
    ];

    protected $casts = [
        'uses_miles' => 'boolean',
        'first_day_of_week' => 'string',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id', 'id');
    }

    public static function sortedByName()
    {
        return static::orderBy('name', 'asc')->get();
    }
}
