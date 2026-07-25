<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
