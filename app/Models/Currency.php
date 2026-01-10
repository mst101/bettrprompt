<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'symbol',
        'thousands_separator',
        'decimal_separator',
        'symbol_on_left',
        'space_between_amount_and_symbol',
        'rounding_coefficient',
        'decimal_digits',
    ];

    protected $casts = [
        'symbol_on_left' => 'boolean',
        'space_between_amount_and_symbol' => 'boolean',
        'rounding_coefficient' => 'integer',
        'decimal_digits' => 'integer',
    ];

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class, 'currency_id', 'id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class, 'currency_code', 'id');
    }
}
