<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Framework extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'complexity',
        'components',
        'best_for',
        'not_for',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'components' => 'array',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function cognitiveRequirements(): BelongsToMany
    {
        return $this->belongsToMany(
            CognitiveRequirement::class,
            'framework_cognitive_requirements',
            'framework_code',
            'cognitive_requirement_code'
        )->withPivot('support_level')->withTimestamps();
    }

    public function taskCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            TaskCategory::class,
            'framework_task_categories',
            'framework_code',
            'task_category_code'
        )->withPivot('suitability')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByComplexity($query, string $complexity)
    {
        return $query->where('complexity', $complexity);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
