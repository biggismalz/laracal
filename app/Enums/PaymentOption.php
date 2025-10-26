<?php

namespace App\Enums;

enum PaymentOption: string
{
    case Full = 'full';
    case Deposit = 'deposit';
}
