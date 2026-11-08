<?php

namespace Eii\Captcha\Providers;

use Eii\Captcha\Contracts\CaptchaProvider;

class TurnstileProvider extends AbstractHttpProvider implements CaptchaProvider
{
    public function verify(string $token, ?string $ip = null): array
    {
        $secret = $this->config['secret_key'] ?? null;

        if (! is_string($secret) || $secret === '') {
            return ['success' => false, 'errors' => ['missing-secret']];
        }

        $payload = ['secret' => $secret, 'response' => $token];

        if ($ip) {
            $payload['remoteip'] = $ip;
        }

        $data = $this->post($this->config['verify_url'], $payload, true);

        return [
            'success' => ($data['success'] ?? false) === true,
            'hostname' => $data['hostname'] ?? null,
            'errors' => $data['error-codes'] ?? [],
            'raw' => $data,
        ];
    }

    public function siteKey(): ?string
    {
        return $this->config['site_key'] ?? null;
    }

    public function name(): string
    {
        return 'turnstile';
    }
}
