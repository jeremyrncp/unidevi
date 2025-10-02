<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\DevisRepository;
use App\Repository\InvoiceRepository;

class NumerotationService
{
    public function __construct(
        private readonly DevisRepository $devisRepository,
        private readonly InvoiceRepository $invoiceRepository
    ) {
    }

    public function getNumberDevis(User $user)
    {
        $devis =  $this->devisRepository->findBy(["owner" => $user]);

        $count = count($devis);

        if ($user->getNumberDevis() !== null) {
            return $user->getNumberDevis() + $count + 1;
        }

        return $count + 1;
    }

    public function getNumberFactures(User $user)
    {
        $invoices =  $this->invoiceRepository->findBy(["owner" => $user]);

        $count = count($invoices);

        if ($user->getNumberFactures() !== null) {
            return $user->getNumberFactures() + $count + 1;
        }

        return $count + 1;
    }
}
