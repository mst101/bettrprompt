<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CognitiveRequirement extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'description',
        'aligned_traits',
        'opposed_traits',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'aligned_traits' => 'array',
        'opposed_traits' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function frameworks(): BelongsToMany
    {
        return $this->belongsToMany(
            Framework::class,
            'framework_cognitive_requirements',
            'cognitive_requirement_code',
            'framework_code'
        )->withPivot('support_level')->withTimestamps();
    }

    public function taskCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            TaskCategory::class,
            'task_category_cognitive_requirements',
            'cognitive_requirement_code',
            'task_category_code'
        )->withPivot('requirement_level')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
