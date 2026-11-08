<?php

namespace Eii\Captcha;

use Eii\Captcha\Contracts\CaptchaProvider;
use Eii\Captcha\Providers\RecaptchaProvider;
use Eii\Captcha\Providers\TurnstileProvider;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CaptchaManager
{
    public function __construct(private array $config) {}

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    public function providerName(): string
    {
        return (string) ($this->config['provider'] ?? 'turnstile');
    }

    public function provider(?string $name = null): CaptchaProvider
    {
        $name ??= $this->providerName();
        $providerConfig = $this->config['providers'][$name] ?? null;

        if (! is_array($providerConfig)) {
            throw new InvalidArgumentException("Unknown CAPTCHA provider [{$name}].");
        }

        $timeout = (int) ($providerConfig['timeout'] ?? $this->config['timeout'] ?? 5);

        return match ($name) {
            'turnstile' => new TurnstileProvider($providerConfig, $timeout),
            'recaptcha' => new RecaptchaProvider($providerConfig, $timeout),
            default => throw new InvalidArgumentException("Unsupported CAPTCHA provider [{$name}]."),
        };
    }

    public function verify(?string $token, ?string $ip = null, ?string $provider = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        $selectedProvider = $provider ?? $this->providerName();
        $ip = is_string($ip) ? trim($ip) : null;
        $ip = $ip === '' ? null : $ip;

        if (! is_string($token) || trim($token) === '') {
            Log::warning('CAPTCHA verification failed: missing or empty token.', [
                'provider' => $selectedProvider,
                'ip' => $ip,
            ]);

            return false;
        }

        if ($ip !== null && filter_var($ip, FILTER_VALIDATE_IP) === false) {
            Log::warning('CAPTCHA verification failed: invalid client IP.', [
                'provider' => $selectedProvider,
                'ip' => $ip,
            ]);

            return false;
        }

        try {
            $result = $this->provider($selectedProvider)->verify($token, $ip);
        } catch (\Throwable $exception) {
            Log::error('CAPTCHA verification request failed.', [
                'provider' => $selectedProvider,
                'ip' => $ip,
                'exception' => $exception,
            ]);
            report($exception);

            // Fail closed if the provider cannot be reached or returns invalid data.
            return false;
        }

        if (! ($result['success'] ?? false)) {
            Log::info('CAPTCHA verification rejected by provider.', [
                'provider' => $selectedProvider,
                'ip' => $ip,
                'errors' => $result['errors'] ?? [],
            ]);

            return false;
        }

        if (($this->config['verify_hostname'] ?? false) === true) {
            $hostname = strtolower((string) ($result['hostname'] ?? ''));
            $allowed = array_values(array_filter(array_map(
                static fn($host) => strtolower(trim((string) $host)),
                (array) ($this->config['allowed_hostnames'] ?? [])
            )));

            if ($hostname === '' || ! in_array($hostname, $allowed, true)) {
                Log::warning('CAPTCHA verification failed: hostname not allowed.', [
                    'provider' => $selectedProvider,
                    'ip' => $ip,
                    'hostname' => $hostname,
                    'allowed_hostnames' => $allowed,
                ]);

                return false;
            }
        }

        if (($this->config['log_success'] ?? false) === true) {
            Log::info('CAPTCHA verification successful.', [
                'provider' => $selectedProvider,
                'ip' => $ip,
            ]);
        }

        return true;
    }

    public function configuration(): array
    {
        return $this->config;
    }
}
