<?php

namespace App\Service;

class UtilsService
{
    public function calculateSumWithPrice(array $items)
    {
        $sum = 0;

        foreach($items as $item) {
            $sum += $item->price;
        }

        return $sum;
    }

    public function getPeriodesDates(array $items)
    {
        $periods = [];

        foreach ($items as $item) {
            $date = $item->getCreatedAt()->format("Y-m");

            if (!in_array($date, $periods)) {
                $periods[] = $date;
            }
        }

        return $periods;
    }

}
