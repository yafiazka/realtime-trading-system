<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case OPEN = 'OPEN';
    case PARTIALLY_FILLED = 'PARTIALLY_FILLED';
    case FILLED = 'FILLED';
    case CANCELED = 'CANCELED';
}
