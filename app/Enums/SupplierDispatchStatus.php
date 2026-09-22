<?php

namespace App\Enums;

enum SupplierDispatchStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
}
