<?php


namespace App\Service;

use App\Entity\Logger;
use Doctrine\ORM\EntityManagerInterface;

class LoggerService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function saveServicePrompt(string $prompt, string $text, string $result): Logger
    {
        $logger = new Logger();
        $logger->setCreatedAt(new \DateTime());
        $logger->setType(Logger::TYPE_SERVICE)
                ->setText($text)
                ->setPrompt($prompt)
                ->setResult($result);

        $this->entityManager->persist($logger);
        $this->entityManager->flush();

        return $logger;
    }

    public function saveUpsellsPrompt(string $prompt, string $text,  string $result): Logger
    {
        $logger = new Logger();
        $logger->setCreatedAt(new \DateTime());
        $logger->setType(Logger::TYPE_UPSELLS)
            ->setText($text)
            ->setPrompt($prompt)
            ->setResult($result);

        $this->entityManager->persist($logger);
        $this->entityManager->flush();

        return $logger;
    }
}
