<?php

namespace App\Interfaces;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

interface OrderInterface
{
    /**
     * Fetch products by slugs with a write lock for the current transaction.
     * Must be called inside DB::transaction().
     */
    public function findLockedBySlugs(array $slugs): Collection;

    /**
     * Persist a new order record.
     */
    public function create(array $data): Order;

    /**
     * Persist a single order item.
     */
    public function createItem(array $data): OrderItem;

    /**
     * Decrement a product's stock_qty by the given amount.
     */
    public function decrementStock(int $productId, int $qty): void;

    /**
     * Write the final order_number, invoice_number and invoice_date
     * once the auto-incremented ID is known.
     */
    public function finaliseNumbers(Order $order, string $orderNumber, string $invoiceNumber): void;

    /**
     * Find an order by its public-facing number and the customer email (for identity verification).
     * Eager-loads items.
     */
    public function findByNumberAndEmail(string $orderNumber, string $email): ?Order;

    /**
     * Update the status of an order.
     */
    public function updateStatus(Order $order, \App\Enums\OrderStatus $status): void;

    /**
     * Increment a product's stock_qty (used when an order is cancelled).
     */
    public function incrementStock(int $productId, int $qty): void;
}
