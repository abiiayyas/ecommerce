<?php

namespace App\Enums;

enum StockReservationStatus: string
{
    case Active = 'active';
    case Committed = 'committed';
    case Released = 'released';
}
