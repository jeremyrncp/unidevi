<?php

namespace App\Controller;

use App\Entity\Devis;
use App\Entity\User;
use App\Repository\DevisRepository;
use App\Repository\InvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(DevisRepository $devisRepository, InvoiceRepository $invoiceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $devis = $devisRepository->FindBy(["owner" => $user]);

        $sumDevisSended = 0;
        $sumDevisCreated = count($devis);
        $totalsTTCDevisSended = 0;

        /** @var Devis $quote */
        foreach ($devis as $quote) {
            if ($quote->getStatus() === "Finalisé") {
                $sumDevisSended ++;
                $totalsTTCDevisSended += $quote->getTotalsTTC();
            }
        }

        $invoices = $invoiceRepository->FindBy(["owner" => $user]);


        $sumInvoicesSended = 0;
        $sumInvoicesCreated = count($invoices);
        $totalsTTCInvoicesSended = 0;

        /** @var Devis $quote */
        foreach ($invoices as $invoice) {
            if ($invoice->getStatus() === "Finalisé") {
                $sumInvoicesSended ++;
                $totalsTTCInvoicesSended += $invoice->getTotalsTTC();
            }
        }


        return $this->render('index/index.html.twig', [
            'user' => $user,
            "sumDevisSended" => $sumDevisSended,
            "sumDevisCreated" => $sumDevisCreated,
            "totalsTTCDevisSended" => $totalsTTCDevisSended,
            'sumInvoicesSended' => $sumInvoicesSended,
            "sumInvoicesCreated" => $sumInvoicesCreated,
            "totalsTTCInvoicesSended" => $totalsTTCInvoicesSended
        ]);
    }
}
