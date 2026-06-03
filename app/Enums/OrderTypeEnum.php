<?php

namespace App\Enums;

enum OrderTypeEnum: string
{
    case LIMIT = 'LIMIT';
    case MARKET = 'MARKET';
}
