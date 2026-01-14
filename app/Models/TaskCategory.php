<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskCategory extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'description',
        'triggers',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'triggers' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get all questions in this task category.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'task_category_code', 'code');
    }

    public function cognitiveRequirements(): BelongsToMany
    {
        return $this->belongsToMany(
            CognitiveRequirement::class,
            'task_category_cognitive_requirements',
            'task_category_code',
            'cognitive_requirement_code'
        )->withPivot('requirement_level')->withTimestamps();
    }

    public function frameworks(): BelongsToMany
    {
        return $this->belongsToMany(
            Framework::class,
            'framework_task_categories',
            'task_category_code',
            'framework_code'
        )->withPivot('suitability')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
