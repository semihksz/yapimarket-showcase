<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Collection;

class CouponService
{
    /**
     * Returns the raw coupon regardless of expiry. Null if code doesn't exist or is inactive.
     */
    public static function raw(string $code): ?array
    {
        $coupon = Coupon::where('code', $code)->where('is_active', true)->first();

        if (!$coupon) {
            return null;
        }

        return [
            'code'       => $coupon->code,
            'type'       => $coupon->type,
            'value'      => (float) $coupon->value,
            'label'      => $coupon->label,
            'expires_at' => $coupon->expires_at?->toDateString(),
            'products'   => $coupon->products,
        ];
    }

    /**
     * Find a coupon by code. Returns null if not found, inactive, or expired.
     */
    public static function find(string $code): ?array
    {
        $raw = self::raw($code);

        if (!$raw) {
            return null;
        }

        if ($raw['expires_at'] !== null && now()->toDateString() > $raw['expires_at']) {
            return null;
        }

        return $raw;
    }

    /**
     * Calculate discount amount.
     * $products is a Collection keyed by slug with price and qty resolved.
     */
    public static function calcDiscount(array $coupon, float $subtotal, array $cartItems, Collection $products): float
    {
        $base = $subtotal;

        if (!empty($coupon['products'])) {
            $base = 0.0;
            foreach ($cartItems as $item) {
                if (in_array($item['slug'], $coupon['products'], true)) {
                    $product = $products->get($item['slug']);
                    if ($product) {
                        $base += (float) $product->price * $item['qty'];
                    }
                }
            }
        }

        if ($base <= 0) {
            return 0.0;
        }

        if ($coupon['type'] === 'percent') {
            return round($base * $coupon['value']) / 100;
        }

        if ($coupon['type'] === 'fixed') {
            return min((float) $coupon['value'], $base);
        }

        return 0.0;
    }
}
