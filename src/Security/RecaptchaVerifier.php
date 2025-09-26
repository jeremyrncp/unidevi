<?php

namespace App\Security;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class RecaptchaVerifier
{
    private string $secret;
    private ?float $minScore;

    public function __construct(private HttpClientInterface $http, string $secret, ?float $minScore = null)
    {
        $this->secret = $secret;
        $this->minScore = $minScore;
    }

    /**
     * @param string $token token renvoyé par le frontend (v2/v3)
     * @param string|null $remoteIp IP du client (optionnel)
     * @param string|null $expectedAction action attendue (v3) ex: "contact_submit"
     */
    public function verify(string $token, ?string $remoteIp = null, ?string $expectedAction = null): bool
    {
        if ($token === '') return false;

        $response = $this->http->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => array_filter([
                'secret'   => $this->secret,
                'response' => $token,
                'remoteip' => $remoteIp,
            ]),
            'timeout' => 5,
        ])->toArray(false);

        // v2 : success bool
        if (!($response['success'] ?? false)) {
            return false;
        }

        if ($this->minScore !== null && isset($response['score']) && $response['score'] < $this->minScore) {
            return false;
        }

        return true;
    }
}
