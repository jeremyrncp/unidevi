<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('monthlyzer', [$this, 'monthlyzerFilter']),
            new TwigFilter('htmlentities', [$this, 'htmlentitesFilter']),
        ];
    }

    public function htmlentitesFilter($string): string
    {
        return htmlentities($string);
    }

    public function monthlyzerFilter($string)
    {
        $months = array("", "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre");

        $explodeDate = explode('-', $string);

        return $months[round($explodeDate[1])] . " " . $explodeDate[0];

    }

    public function durationFilter($integer)
    {
        $hour = floor($integer / 60);

        if ($hour ===  0.0) {
            return $integer . " min";
        } else {
            return $hour . "h " . $integer . "min";
        }
    }
}