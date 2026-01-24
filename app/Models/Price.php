<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Price extends Model
{
    protected $fillable = [
        'currency_code',
        'tier',
        'interval',
        'stripe_price_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the currency for this price
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'id');
    }

    /**
     * Get formatted price display (e.g., "£12.00", "$15.99")
     */
    public string $formatted {
        get {
            $currency = $this->currency;
            if (! $currency) {
                return (string) $this->amount;
            }

            $symbol = $currency->symbol;
            $amount = number_format($this->amount, $currency->decimal_digits, $currency->decimal_separator, $currency->thousands_separator);

            if ($currency->symbol_on_left) {
                $glue = $currency->space_between_amount_and_symbol ? ' ' : '';

                return $symbol.$glue.$amount;
            } else {
                $glue = $currency->space_between_amount_and_symbol ? ' ' : '';

                return $amount.$glue.$symbol;
            }
        }
    }

    /**
     * Scope to find price by tier, interval, and currency
     */
    public function scopeForTier($query, string $tier, string $interval, string $currency_code)
    {
        return $query->where('tier', $tier)
            ->where('interval', $interval)
            ->where('currency_code', $currency_code);
    }
}
