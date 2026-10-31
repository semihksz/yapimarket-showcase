<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Paid       = 'paid';
    case Processing = 'processing';
    case Shipped    = 'shipped';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';
    case Refunded   = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Paid       => 'Ödendi',
            self::Processing => 'Hazırlanıyor',
            self::Shipped    => 'Kargoya Verildi',
            self::Delivered  => 'Teslim Edildi',
            self::Cancelled  => 'İptal Edildi',
            self::Refunded   => 'İade Edildi',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Paid       => 'bg-success',
            self::Processing => 'bg-warning text-dark',
            self::Shipped    => 'bg-info text-dark',
            self::Delivered  => 'bg-primary',
            self::Cancelled  => 'bg-danger',
            self::Refunded   => 'bg-secondary',
        };
    }
}
