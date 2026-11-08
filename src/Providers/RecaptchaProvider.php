<?php

namespace Eii\Captcha\Providers;

use Eii\Captcha\Contracts\CaptchaProvider;

class RecaptchaProvider extends AbstractHttpProvider implements CaptchaProvider
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
        $success = ($data['success'] ?? false) === true;
        $version = strtolower((string) ($this->config['version'] ?? 'v2'));

        if ($success && $version === 'v3') {
            $score = isset($data['score']) ? (float) $data['score'] : 0.0;
            $threshold = (float) ($this->config['score_threshold'] ?? 0.5);
            $expectedAction = $this->config['expected_action'] ?? null;
            $actualAction = $data['action'] ?? null;

            $success = $score >= $threshold;

            if (is_string($expectedAction) && $expectedAction !== '') {
                $success = $success && hash_equals($expectedAction, (string) $actualAction);
            }
        }

        return [
            'success' => $success,
            'hostname' => $data['hostname'] ?? null,
            'score' => isset($data['score']) ? (float) $data['score'] : null,
            'action' => $data['action'] ?? null,
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
        return 'recaptcha';
    }
}
