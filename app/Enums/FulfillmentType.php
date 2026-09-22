<?php

namespace App\Enums;

enum FulfillmentType: string
{
    case OwnedStock = 'owned_stock';
    case SupplierDropship = 'supplier_dropship';
}
