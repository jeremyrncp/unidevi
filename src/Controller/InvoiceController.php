<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleInvoice;
use App\Entity\Customer;
use App\Entity\Devis;
use App\Entity\Invoice;
use App\Entity\Upsell;
use App\Entity\UpsellInvoice;
use App\Entity\User;
use App\Form\ChangeStyleDevisType;
use App\Form\ChangeStyleInvoiceType;
use App\Form\CustomerDevisType;
use App\Form\SelectionCustomerType;
use App\Repository\CustomerRepository;
use App\Repository\DevisRepository;
use App\Repository\InvoiceRepository;
use App\Service\NumerotationService;
use App\Service\OpenAIAssistant;
use App\Service\PromptService;
use App\Service\SubscriptionService;
use App\Service\UtilsService;
use App\VO\SelectionCustomerVO;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final class InvoiceController extends AbstractController
{

    public function __construct(private readonly PromptService $promptService)
    {
    }
    #[Route('/invoice', name: 'app_invoice_list')]
    public function index(InvoiceRepository $invoiceRepository, Request $request, PaginatorInterface $paginator, UtilsService $utilsService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->query->get("search") !== null) {
            $invoices = $invoiceRepository->findByNameAndOwner($request->query->get("search"), $user);
        } else {
            $invoices = $invoiceRepository->findBy(["owner" => $user], ["id" => "DESC"]);
        }

        $pagination = $paginator->paginate(
            $invoices,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('invoice/list.html.twig', [
            'devis' => $pagination,
            'user' => $user,
            "periods" => $utilsService->getPeriodesDates($invoices)
        ]);
    }

    #[Route('/invoice/step0', name: 'app_invoice')]
    public function step0(Request $request, EntityManagerInterface $entityManager, CustomerRepository $customerRepository, NumerotationService $numerotationService, SubscriptionService $subscriptionService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($subscriptionService->isDemo($user)) {
            return $this->redirectToRoute("app_parameters_subscription");
        }

        if ($request->request->get("font") !== null) {
            $style           = $request->request->get("style");
            $font            = $request->request->get("font");
            $colorAccent     = $request->request->get("colorAccent");
            $rememberDefault = $request->request->get("rememberDefault");
            $customer = $request->request->get("customer");

            if ($rememberDefault !== null) {
                $user->setStyleType($style)->setStylePolice($font)->setStyleColor($colorAccent);

                $entityManager->flush();
            }

            $devis = new Invoice();
            $devis->setOwner($user);
            $devis->setLogo($user->getStyleLogo());
            $devis->setCreatedAt(new \DateTime());
            $devis->setStyle($style);
            $devis->setColor($colorAccent);
            $devis->setFont($font);
            $devis->setNumber($numerotationService->getNumberFactures($user));

            if ($user->getCompanyName() !== null) {
                $devis->setNameCompany($user->getCompanyName());
            }

            if ($user->getPhoneNumber() !== null) {
                $devis->setPhoneNumberCompany($user->getPhoneNumber());
            }

            if ($user->getEmail() !== null) {
                $devis->setEmailCompany($user->getEmail());
            }

            if ($user->getSiret() !== null) {
                $devis->setSiretCompany($user->getSiret());
            }

            if ($user->getAdresse() !== null) {
                $devis->setAddress($user->getAdresse());
            }

            if ($user->getPostalCode() !== null) {
                $devis->setPostalCodeCompany($user->getPostalCode());
            }

            if ($user->getCountry() !== null) {
                $devis->setCountryCompany($user->getCountry());
            }

            if ($user->getValidityDevis() !== null) {
                $devis->setValidityDevis($user->getValidityDevis());
            }

            if (is_int($customer)) {
                /** @var Customer $customerEntity */
                $customerEntity = $customerRepository->find($customer);

                if ($customerEntity->getOwner() === $user) {
                    $devis->setCustomer($customerEntity);
                    $devis->setNameCustomer($customerEntity->getName())
                          ->setAddressCustomer($customerEntity->getAddress())
                          ->setPostalCodeCustomer($customerEntity->getPostalCode())
                          ->setCityCustomer($customerEntity->getCity())
                          ->setSiretCustomer($customerEntity->getSiret());
                }
            }


            $entityManager->persist($devis);
            $entityManager->flush();

            if (is_int($customer)) {
                return $this->redirectToRoute("app_invoice_step2", ["id" => $devis->getId()]);
            }

            return $this->redirectToRoute("app_invoice_step1", ["id" => $devis->getId()]);
        }

        return $this->render('invoice/index.html.twig', [
            'user' => $user,
            'customer' => $request->query->get('customer'),
        ]);
    }

    #[Route('/invoice/step1/{id}', name: 'app_invoice_step1')]
    public function step1(#[MapEntity(mapping: ['id' => 'id'])] Invoice $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        $selectionCustomerVO = new SelectionCustomerVO();
        $selectionCustomerForm = $this->createForm(SelectionCustomerType::class, $selectionCustomerVO);
        $selectionCustomerForm->handleRequest($request);

        $customer = new Customer();
        $customerForm = $this->createForm(CustomerDevisType::class, $customer);
        $customerForm->handleRequest($request);

        if ($customerForm->isSubmitted() && $customerForm->isValid()) {
            $entityManager->persist($customer);

            $devis->setCustomer($customer);
            $customer->setOwner($user);
            $this->hydrateDevisWithCustomer($customer, $user, $devis);

            $entityManager->flush();

            return $this->redirectToRoute("app_invoice_step2", ["id" => $devis->getId()]);
        } else if ($selectionCustomerForm->isSubmitted() && $selectionCustomerForm->isValid()) {
            $customer = $selectionCustomerVO->customer;
            $customer->setOwner($user);
            $devis->setCustomer($customer);
            $this->hydrateDevisWithCustomer($customer, $user, $devis);
            $entityManager->flush();

            return $this->redirectToRoute("app_invoice_step2", ["id" => $devis->getId()]);
        }

        return $this->render('invoice/step1.html.twig', [
            'user' => $user,
            "selectionCustomerForm" => $selectionCustomerForm->createView(),
            "customerForm" => $customerForm->createView(),
        ]);
    }

    #[Route('/invoice/step2/{id}', name: 'app_invoice_step2')]
    public function step2(Invoice $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        return $this->render('invoice/step2.html.twig', [
            'user' => $user,
            'devis' => $devis,
        ]);
    }

    #[Route('/invoice/step3-manuel/{id}', name: 'app_invoice_step3_manuel')]
    public function step3Manuel(Invoice $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        if ($request->request->get("submitType") === "modeManuel") {
            $devis->setName($request->request->get("titleDevis"));
            $devis->setTvaRate($request->request->get("tvaRate"));
            $devis->setSubtotal($request->request->get("subtotal"));
            $devis->setTotalMain($request->request->get("totalMain"));


            $data = $this->mapServiceAndUpsell($request);

            foreach ($data as $uniquid => $item) {
                if ($item["type"] === "service") {
                    $article = new ArticleInvoice();
                    $article->setName($item["title"])->setDescription(
                        $item["description"]
                    );

                    if ($item["price"] !== "") {
                        $article->setPrice($item["price"] * 100);
                    }

                    $devis->addArticle($article);
                } else {
                    if ($item["type"] === "upsell") {
                        $upsell = new UpsellInvoice();

                        $upsell->setName($item["title"])
                            ->setDescription(
                                $item["description"]
                            );

                        if ($item["price"] !== "") {
                            $upsell->setPrice($item["price"] * 100);
                        }

                        $devis->addUpsell($upsell);
                    }
                }
            }

            $entityManager->flush();
            $this->addFlash("message", "Services et upsells bien ajoutés");

            return $this->redirectToRoute("app_invoice_step4", ["id" => $devis->getId()]);
        }

        return $this->render('invoice/step3_manuel.html.twig', [
            'user' => $user,
        ]);
    }


    #[Route('/invoice/step4/{id}', name: 'app_invoice_step4')]
    public function step4(Invoice $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        return $this->render('invoice/step4.html.twig', array_merge([
            'user' => $user,
            'devis' => $devis,
        ], $this->getsumAndOtherElements($devis)));
    }

    #[Route('/invoice/duplication/{id}', name: 'app_invoice_duplication')]
    public function duplication(Invoice $devis, Request $request, EntityManagerInterface $entityManager, NumerotationService $numerotationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        $cloneDevis = clone $devis;
        $cloneDevis->setNumber($numerotationService->getNumberFactures($user));
        $cloneDevis->setId(null);


        foreach ($cloneDevis->getArticles() as $article) {
            $cloneDevis->removeArticle($article);
        }

        foreach ($cloneDevis->getUpsells() as $upsell) {
            $cloneDevis->removeUpsell($upsell);
        }

        /** @var Article $article */
        foreach ($devis->getArticles() as $article) {
            $cloneArticle = clone $article;
            $cloneArticle->setId(null);
            $cloneDevis->addArticle($cloneArticle);
        }

        /** @var Upsell $upsell */
        foreach ($devis->getUpsells() as $upsell) {
            $cloneUpsell= clone $upsell;
            $cloneUpsell->setId(null);
            $cloneDevis->addUpsell($cloneUpsell);
        }

        $entityManager->persist($cloneDevis);
        $entityManager->flush();

        $this->addFlash("message", "Facture dupliquée");

        return $this->render('invoice/step4.html.twig', array_merge([
            'user' => $user,
            'devis' => $cloneDevis,
        ], $this->getsumAndOtherElements($cloneDevis)));
    }

    #[Route('/invoice/archiver/{id}', name: 'app_invoice_archiver')]
    public function archiver(Invoice $devis, Request $request, EntityManagerInterface $entityManager, NumerotationService $numerotationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        $devis->setArchivedAt(new \DateTime());
        $entityManager->flush();

        $this->addFlash("message", "Facture archivée");

        return $this->render('invoice/step4.html.twig', array_merge([
            'user' => $user,
            'devis' => $devis,
        ], $this->getsumAndOtherElements($devis)));
    }

    #[Route('/invoice/change-style/{id}', name: 'app_invoice_change_style')]
    public function changeStyle(Invoice $devis, Request $request, EntityManagerInterface $entityManager, NumerotationService $numerotationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        $form = $this->createForm(ChangeStyleInvoiceType::class, $devis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash("message", "Style bien changé");

            return $this->render('invoice/step4.html.twig', array_merge([
                'user' => $user,
                'devis' => $devis,
            ], $this->getsumAndOtherElements($devis)));
        }

        return $this->render('invoice/change_style.html.twig', [
            "form" => $form->createView(),
            "user" => $user
        ]);
    }


    #[Route('/invoice/view-send/{id}', name: 'app_invoice_view_send')]
    public function viewSend(Invoice $devis, Request $request, EntityManagerInterface $entityManager, Environment $twig): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }


        $logo = null;

        if ($devis->getLogo() !== null) {
            $type = pathinfo(__DIR__ . "/../../public/uploads/" . $devis->getLogo(), PATHINFO_EXTENSION);
            $data = file_get_contents(__DIR__ . "/../../public/uploads/" . $devis->getLogo());
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            $logo = $base64;
        }

        $red = null;
        $green = null;
        $blue = null;

        [$red, $green, $blue] = sscanf($devis->getColor(), "#%02x%02x%02x");


        $template = $twig->render("invoice/maquette_style_" . $devis->getStyle() . ".html.twig", array_merge([
            'user' => $user,
            'devis' => $devis,
            "logo" => $logo,
            "red" => $red,
            "green" => $green,
            "blue" => $blue
        ], $this->getsumAndOtherElements($devis)));

        $namefile = uniqid() . ".pdf";

        $dompdf = new Dompdf();
        $dompdf->loadHtml($template);
        $dompdf->setPaper('A4');
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents(__DIR__ . "/../../public/uploads/" . $namefile, $output);


        return $this->render('invoice/view_and_send.html.twig', [
            "user" => $user,
            "devis" => $devis,
            "pdf" => $namefile
        ]);
    }

    #[Route('/invoice/send/{id}', name: 'app_invoice_send')]
    public function send(Invoice $devis, Request $request, EntityManagerInterface $entityManager, Environment $twig, MailerInterface $mailer): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        $namefile = $request->query->get("file");

        $email = (new Email())
            ->from($_ENV["SENDER_EMAIL"])
            ->to($devis->getCustomer()->getEmail())
            ->subject("Votre deivs " . $user->getCompanyName())
            ->replyTo($user->getEmailContact())
            ->addPart(new DataPart(new File(__DIR__ . "/../../public/uploads/" . $namefile)));

        $mailer->send($email);

        $devis->setSendedAt(new \DateTime());
        $entityManager->flush();

        $this->addFlash("message", "Facture envoyée à " . $devis->getCustomer()->getEmail());

        return $this->render('invoice/view_and_send.html.twig', [
            "user" => $user,
            "devis" => $devis,
            "pdf" => $namefile
        ]);
    }

    #[Route('/invoice/step3-ia/{id}', name: 'app_invoice_step3_ia')]
    public function step3IA(Invoice $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        if ($request->request->get("submitType") === "modeManuel") {
            $devis->setName($request->request->get("titleDevis"));
            $devis->setTvaRate($request->request->get("tvaRate"));
            $devis->setSubtotal($request->request->get("subtotal"));
            $devis->setTotalMain($request->request->get("totalMain"));


            $data = $this->mapServiceAndUpsell($request);

            foreach ($data as $uniquid => $item) {
                if ($item["type"] === "service") {
                    $article = new ArticleInvoice();
                    $article->setName($item["title"])->setDescription(
                        $item["description"]
                    );

                    if ($item["price"] !== "") {
                        $article->setPrice($item["price"] * 100);
                    }

                    $devis->addArticle($article);
                } else {
                    if ($item["type"] === "upsell") {
                        $upsell = new UpsellInvoice();

                        $upsell->setName($item["title"])
                            ->setDescription(
                                $item["description"]
                            );

                        if ($item["price"] !== "") {
                            $upsell->setPrice($item["price"] * 100);
                        }

                        $devis->addUpsell($upsell);
                    }
                }
            }

            $entityManager->flush();
            $this->addFlash("message", "Services et upsells bien ajoutés");

            return $this->redirectToRoute("app_invoice_step4", ["id" => $devis->getId()]);
        }

        return $this->render('invoice/step3_ia.html.twig', [
            'user' => $user,
            'devis' => $devis,
        ]);
    }


    #[Route('/invoice/ia-generate/{id}', name: 'app_invoice_ia_generate')]
    public function iAGenerate(Invoice $devis, Request $request, EntityManagerInterface $entityManager, OpenAIAssistant $openAIAssistant, UtilsService $utilsService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire de la facture");
        }

        $data = json_decode($request->getContent());
        $description = $data->description;
        $duree = $data->duree;
        $unite = $data->unite;

        /** Extract services with open ai **/
        $assistantIdServiceID  = $openAIAssistant->getAssistantId("assistént services", $this->promptService->getPromptServices());
        $thread = $openAIAssistant->createThread();
        $openAIAssistant->sendMessage($thread, $openAIAssistant->createMessage($description, $duree, $unite));
        $runId = $openAIAssistant->runAssistant($thread, $assistantIdServiceID);
        $openAIAssistant->waitForRun($thread, $runId);
        $responseService = $openAIAssistant->getLatestAssistantResponse($thread);
        $servicesVOs = $openAIAssistant->extractServices($responseService);

        /** Extract upsells **/
        $assistantIdUpsellID  = $openAIAssistant->getAssistantId("Assistant upsells", $this->promptService->getPromptUpsells());
        $thread = $openAIAssistant->createThread();
        $openAIAssistant->sendMessage($thread, $openAIAssistant->createMessage($description, $duree, $unite));
        $runId = $openAIAssistant->runAssistant($thread, $assistantIdUpsellID);
        $openAIAssistant->waitForRun($thread, $runId);
        $responseUpsells = $openAIAssistant->getLatestAssistantResponse($thread);
        $upsellsVOs = $openAIAssistant->extractUpsells($responseUpsells);

        $sumServices = $utilsService->calculateSumWithPrice($servicesVOs);
        $sumUpsells = $utilsService->calculateSumWithPrice($upsellsVOs);

            return $this->render('invoice/step3_manuel_hydrated.html.twig', [
            'user' => $user,
            "services" => $servicesVOs,
            "upsells" => $upsellsVOs,
            "sumServices" => $sumServices,
            "sumUpsells" => $sumUpsells,
            "subtotal" => $sumUpsells + $sumServices,
            "titleDevis" => $description
        ]);
    }



    private function getsumAndOtherElements(Invoice $devis): array
    {
        $sumArticles = 0;
        $sumUpsells = 0;

        /** @var Article $article */
        foreach ($devis->getArticles() as $article) {
            $sumArticles += $article->getPrice();
        }


        /** @var Upsell $upsell */
        foreach ($devis->getUpsells() as $upsell) {
            $sumUpsells += $upsell->getPrice();
        }

        $sumArticles = ($sumArticles === 0) ? $devis->getTotalMain() : $sumArticles/100;

        return [
            "sumUpsells" => $sumUpsells/100,
            "sumArticles" => $sumArticles,
            "tvaUpsells" => $sumUpsells/100*($devis->getTvaRate()/100),
            "tvaArticles" => $sumArticles*($devis->getTvaRate()/100),
            "tvaTotal" => round((float) $devis->getSubtotal(),2)*($devis->getTvaRate()/100),
            "subtotal" => round((float) $devis->getSubtotal(),2),
            "totalMain" => round((float) $devis->getTotalMain(),2),
        ];

    }



    /**
     * Description mapServiceAndUpsell function
     *
     * @param Request $request
     *
     * @return array
     */
    private function mapServiceAndUpsell(Request $request)
    {
        $return = [];

        foreach ($request->request->all() as $key => $value) {
            $explode = explode("-", $key);

            if (count($explode) === 2) {
                $typeData = $explode[0];
                $uniquid = $explode[1];

                if (!array_key_exists($uniquid, $return)) {
                    $return[$uniquid] = [];
                }

                $return[$uniquid][$typeData] = $value;
            }
        }


        foreach ($return as $key => $item) {
            if (!array_key_exists("checkbox", $item) && $item["checkbox"] !== "on") {
                unset($return[$key]);
            }
        }

        return $return;
    }

    /**
     * Description extracted function
     *
     * @param Customer  $customer
     * @param User|null $user
     * @param Devis     $devis
     *
     * @return void
     */
    private function hydrateDevisWithCustomer(Customer $customer, ?User $user, Invoice $devis): void
    {
        if ($customer->getOwner() === $user) {
            $devis->setNameCustomer($customer->getName())
                ->setAddressCustomer($customer->getAddress())
                ->setPostalCodeCustomer($customer->getPostalCode())
                ->setCityCustomer($customer->getCity())
                ->setSiretCustomer($customer->getSiret())
                ->setMentionsLegales($user->getMentionsLegalesDevis());
        }
    }
}