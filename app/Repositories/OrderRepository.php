<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Interfaces\OrderInterface;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

class OrderRepository implements OrderInterface
{
    public function findLockedBySlugs(array $slugs): Collection
    {
        return Product::whereIn('slug', $slugs)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->keyBy('slug');
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function createItem(array $data): OrderItem
    {
        return OrderItem::create($data);
    }

    public function decrementStock(int $productId, int $qty): void
    {
        Product::where('id', $productId)->decrement('stock_qty', $qty);
    }

    public function finaliseNumbers(Order $order, string $orderNumber, string $invoiceNumber): void
    {
        $order->update([
            'order_number'   => $orderNumber,
            'invoice_number' => $invoiceNumber,
            'invoice_date'   => now(),
        ]);
    }

    public function findByNumberAndEmail(string $orderNumber, string $email): ?Order
    {
        return Order::with('items')
            ->where('order_number', $orderNumber)
            ->where('email', $email)
            ->first();
    }

    public function updateStatus(Order $order, OrderStatus $status): void
    {
        $order->update(['status' => $status]);
    }

    public function incrementStock(int $productId, int $qty): void
    {
        Product::where('id', $productId)->increment('stock_qty', $qty);
    }
}
