<?php

namespace App\Service;

use App\VO\ServiceVO;
use App\VO\UpsellVO;

class OpenAIAssistant
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function getAssistantId($type="Assistant services", $instructions = "À partir de la description du client, rédige uniquement une liste professionnelle et concise des prestations incluses dans le devis. Structure en zones principales (ex : entrée, chambres, cuisine, salle de bain…). 2 à 3 bullet points maximum par zone. Interdiction absolue d’ajouter : préparation, plan, diagnostic, engagement, sécurité, conformité, organisation ou rapport. Pas d’introduction ni de conclusion : uniquement la liste des prestations visibles avec pour chaque catégorie deux * et le prix de chaque prestation en euro entre parenthèses. Limite totale : 10 bullet points maximum.")
    {
        $data = [
            'name' => $type,
            'instructions' => $instructions,
            'model' => 'gpt-4o'
        ];

        $ch = curl_init('https://api.openai.com/v1/assistants');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'OpenAI-Beta: assistants=v2'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        return $result['id'];
    }

    /**
     * Extracts service details from the given content.
     *
     * @param string $content The content from which service details will be extracted.
     *
     * @return array An array containing the extracted service details.
     */
    public function extractServices(string $content): array
    {
        $services = [];

        $explodeType = explode("**", $content);

        foreach ($explodeType as $key => $typeElements) {
            if (is_string($typeElements) and $typeElements !== "" and strlen($typeElements) <= 50) {
                $type = $typeElements;
                $explodeServices = explode("- ", $explodeType[$key+1]);

                foreach ($explodeServices as $keyService => $serviceAndPrice) {
                    if (strlen($serviceAndPrice) >= 5) {
                        $explodePrice = explode("(", $serviceAndPrice);
                        $nameService = trim($explodePrice[0]);

                        $explodeEuro = explode("€)", $explodePrice[1]);
                        $price = (int) trim($explodeEuro[0]);

                        $serviceVO = new ServiceVO();
                        $serviceVO->description = $type . ":". $nameService;
                        $serviceVO->id    = $key.$keyService;
                        $serviceVO->price = $price;
                        $services[]       = $serviceVO;

                    }
                }

            }
        }

        return $services;
    }

    /**
     * Extracts upsell details from the given content.
     *
     * @param string $content The content from which upsel details will be extracted.
     *
     * @return array An array containing the extracted upsell details.
     */
    public function extractUpsells(string $content): array
    {
        $upsells = [];
        ;
        $explodeServices = explode("- ", $content);

        foreach ($explodeServices as $keyService => $serviceAndPrice) {
            if (strlen($serviceAndPrice) >= 5) {
                $explodePrice = explode("(", $serviceAndPrice);
                $nameUpsell= trim($explodePrice[0]);

                $explodeEuro = explode("€)", $explodePrice[1]);
                $price = (int) trim($explodeEuro[0]);

                $upsellVO = new UpsellVO();
                $upsellVO->description = $nameUpsell;
                $upsellVO->id    = $keyService;
                $upsellVO->price = $price;
                $upsells[]       = $upsellVO;

            }
        }

        return $upsells;
    }

    public function createMessage(string $description, int $duree, string $unite)
    {
        return "Desription du client : " . $description . " pour une durée de " . $duree . " " . $unite;
    }

    private function request(string $method, string $endpoint, array $data = [], bool $isJson = true)
    {
        $url = "{$this->baseUrl}/$endpoint";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = ['Authorization: Bearer ' . $this->apiKey, 'OpenAI-Beta: assistants=v2'];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($isJson) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Erreur cURL : $error");
        }

        $decoded = json_decode($response, true);

        if (isset($decoded['error'])) {
            throw new \Exception("Erreur API : " . $decoded['error']['message']);
        }

        return $decoded;
    }

    public function uploadFiles(array $filePaths): array
    {
        $fileIds = [];

        foreach ($filePaths as $path) {
            if (!file_exists($path)) {
                throw new \Exception("Fichier introuvable : $path");
            }

            $data = [
                'purpose' => 'assistants',
                'file' => new \CURLFile($path)
            ];

            $response = $this->request('POST', 'files', $data, false);
            $fileIds[] = $response['id'];
        }

        return $fileIds;
    }

    public function createThread(): string
    {
        $response = $this->request('POST', 'threads');
        return $response['id'];
    }

    public function sendMessage(string $threadId, string $content): void
    {
        $data = [
            'role' => 'user',
            'content' => $content
        ];

        $this->request('POST', "threads/$threadId/messages", $data);
    }

    public function runAssistant(string $threadId, string $assistantId): string
    {
        $data = ['assistant_id' => $assistantId];
        $response = $this->request('POST', "threads/$threadId/runs", $data);
        return $response['id'];
    }

    public function waitForRun(string $threadId, string $runId): void
    {
        $status = '';
        do {
            sleep(2);
            $response = $this->request('GET', "threads/$threadId/runs/$runId");
            $status = $response['status'];
        } while (!in_array($status, ['completed', 'failed', 'cancelled']));
    }

    public function getLatestAssistantResponse(string $threadId): ?string
    {
        $response = $this->request('GET', "threads/$threadId/messages");

        foreach (array_reverse($response['data']) as $message) {
            if ($message['role'] === 'assistant') {
                return $message['content'][0]['text']['value'];
            }
        }

        return null;
    }
}
