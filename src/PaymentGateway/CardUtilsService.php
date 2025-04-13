<?php

declare(strict_types=1);

namespace App\PaymentGateway;

class CardUtilsService
{
    public function isCardExpired(int $month, int $year): bool
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        return $year < $currentYear || ($year === $currentYear && $month < $currentMonth);
    }
}