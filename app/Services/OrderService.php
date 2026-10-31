<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Interfaces\OrderInterface;
use App\Mail\OrderConfirmed;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function __construct(protected OrderInterface $order) {}

    /**
     * Cancel a placed order and restore stock.
     *
     * @throws \RuntimeException if the order is not in a cancellable state
     */
    public function cancelOrder(Order $order): void
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Processing], true)) {
            throw new \RuntimeException('Bu sipariş artık iptal edilemez.');
        }

        DB::transaction(function () use ($order) {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                $this->order->incrementStock($item->product_id, $item->qty);
            }

            $this->order->updateStatus($order, OrderStatus::Cancelled);
        });
    }

    /**
     * Place an order atomically.
     *
     * @param  array  $customer   Keys: first_name, last_name, email, phone, address, city, district, zip
     * @param  array  $cartItems  Each item: ['slug' => string, 'qty' => int]
     * @param  array  $payment    Keys: coupon_code (nullable), card_brand (nullable), card_last4 (nullable)
     * @throws \RuntimeException  When stock is insufficient or a product is unavailable
     */
    public function placeOrder(array $customer, array $cartItems, array $payment): Order
    {
        $order = DB::transaction(function () use ($customer, $cartItems, $payment) {
            $slugs    = array_column($cartItems, 'slug');
            $products = $this->order->findLockedBySlugs($slugs);

            // ── Stock validation ──────────────────────────────────────────────
            foreach ($cartItems as $item) {
                $product = $products->get($item['slug']);

                if (!$product) {
                    throw new \RuntimeException('Bir ürün artık mevcut değil. Lütfen sepetinizi kontrol edin.');
                }

                if ($product->stock_qty < $item['qty']) {
                    throw new \RuntimeException(
                        '"' . $product->name . '" ürününde yeterli stok bulunmuyor. Mevcut stok: ' . $product->stock_qty
                    );
                }
            }

            // ── Subtotal ──────────────────────────────────────────────────────
            $subtotal = 0.0;
            foreach ($cartItems as $item) {
                $subtotal += (float) $products->get($item['slug'])->price * $item['qty'];
            }

            // ── Coupon ────────────────────────────────────────────────────────
            $discountAmount = 0.0;
            $couponCode     = null;

            if (!empty($payment['coupon_code'])) {
                $coupon = CouponService::find($payment['coupon_code']);
                if ($coupon) {
                    $discountAmount = CouponService::calcDiscount($coupon, $subtotal, $cartItems, $products);
                    $couponCode     = $coupon['code'];
                }
            }

            $amountPaid = max(0.0, $subtotal - $discountAmount);

            // ── Create order (temp number until ID is known) ──────────────────
            $order = $this->order->create([
                'order_number'    => 'TEMP-' . Str::uuid(),
                'first_name'      => $customer['first_name'],
                'last_name'       => $customer['last_name'],
                'email'           => $customer['email'],
                'phone'           => $customer['phone'],
                'address'         => $customer['address'],
                'city'            => $customer['city'],
                'district'        => $customer['district'],
                'zip'             => $customer['zip'] ?? null,
                'total'           => $subtotal,
                'coupon_code'     => $couponCode,
                'discount_amount' => $discountAmount,
                'amount_paid'     => $amountPaid,
                'payment_method'  => 'credit_card',
                'card_brand'      => $payment['card_brand'] ?? null,
                'card_last4'      => $payment['card_last4'] ?? null,
                'status'          => OrderStatus::Paid,
            ]);

            // ── Order items + stock decrement ─────────────────────────────────
            foreach ($cartItems as $item) {
                $product   = $products->get($item['slug']);
                $lineTotal = (float) $product->price * $item['qty'];

                $this->order->createItem([
                    'order_id'      => $order->id,
                    'product_id'    => $product->id,
                    'product_name'  => $product->name,
                    'product_sku'   => $product->sku,
                    'product_image' => $product->image,
                    'price'         => $product->price,
                    'qty'           => $item['qty'],
                    'line_total'    => $lineTotal,
                ]);

                $this->order->decrementStock($product->id, $item['qty']);
            }

            // ── Finalise order/invoice numbers using the auto-increment ID ────
            $padded = str_pad($order->id, 6, '0', STR_PAD_LEFT);
            $date   = now()->format('Ymd');

            $this->order->finaliseNumbers(
                $order,
                'YM-'  . $date . '-' . $padded,
                'FAT-' . $date . '-' . $padded,
            );

            return $order->fresh(['items']);
        });

        Mail::to($order->email)->queue(new OrderConfirmed($order));

        return $order;
    }
}
