<?php

namespace App\Enums;

enum LedgerType: string
{
    case DEPOSIT = 'DEPOSIT';
    case LOCK = 'LOCK';
    case UNLOCK = 'UNLOCK';
    case CREDIT = 'CREDIT';
    case DEBIT = 'DEBIT';
}
