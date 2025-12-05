<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaudeModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'tier',
        'version',
        'input_cost_per_mtok',
        'output_cost_per_mtok',
        'release_date',
        'active',
        'positioning',
        'context_window_input',
        'context_window_output',
    ];

    protected $casts = [
        'release_date' => 'date',
        'active' => 'boolean',
        'input_cost_per_mtok' => 'decimal:4',
        'output_cost_per_mtok' => 'decimal:4',
    ];

    /**
     * Get only active models
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get models by tier
     */
    public function scopeTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    /**
     * Calculate cost for input tokens
     */
    public function calculateInputCost(int $inputTokens): float
    {
        return ($inputTokens / 1_000_000) * (float) $this->input_cost_per_mtok;
    }

    /**
     * Calculate cost for output tokens
     */
    public function calculateOutputCost(int $outputTokens): float
    {
        return ($outputTokens / 1_000_000) * (float) $this->output_cost_per_mtok;
    }

    /**
     * Calculate total cost for input and output tokens
     */
    public function calculateTotalCost(int $inputTokens, int $outputTokens): float
    {
        return $this->calculateInputCost($inputTokens) + $this->calculateOutputCost($outputTokens);
    }
}
