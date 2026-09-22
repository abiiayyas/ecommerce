<?php

namespace App\Enums;

enum OrderFulfillmentStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case AwaitingSupplier = 'awaiting_supplier';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
