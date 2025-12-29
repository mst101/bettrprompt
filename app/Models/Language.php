<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelIdea\Helper\App\Models\_IH_Language_C;

class Language extends Model
{
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class, 'language_id', 'id');
    }

    public static function active(): array|_IH_Language_C
    {
        return static::where('active', true)->get();
    }
}
