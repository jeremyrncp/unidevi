<?php

namespace App\Service;

use App\Entity\Prompt;
use App\Enum\OpenAIEnum;
use App\Repository\PromptRepository;

class PromptService
{
    public function __construct(private readonly PromptRepository $promptRepository)
    {
    }

    public function getPromptServices():string
    {
        $prompt = $this->promptRepository->findOneBy(["type" => Prompt::TYPE_SERVICES]);

        if ($prompt instanceof Prompt) {
            return $prompt->getContent();
        }

        return OpenAIEnum::PROMPT_SERVICES;
    }

    public function getPromptUpsells():string
    {
        $prompt = $this->promptRepository->findOneBy(["type" => Prompt::TYPE_UPSELLS]);

        if ($prompt instanceof Prompt) {
            return $prompt->getContent();
        }

        return OpenAIEnum::PROMPT_UPSELLS;
    }
}
