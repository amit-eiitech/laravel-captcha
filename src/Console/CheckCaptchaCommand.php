<?php

namespace Eii\Captcha\Console;

use Illuminate\Console\Command;

class CheckCaptchaCommand extends Command
{
    protected $signature = 'eii-captcha:check';
    protected $description = 'Check Eii CAPTCHA configuration for common deployment mistakes.';

    public function handle(): int
    {
        $config = config('eii-captcha', []);
        $provider = (string) ($config['provider'] ?? '');
        $environment = app()->environment();
        $enabled = (bool) ($config['enabled'] ?? false);
        $errors = [];
        $warnings = [];

        if (! in_array($provider, ['turnstile', 'recaptcha'], true)) {
            $errors[] = 'Unknown provider configured.';
        } else {
            $providerConfig = $config['providers'][$provider] ?? [];
            if (empty($providerConfig['site_key'])) {
                $errors[] = "Missing site key for [{$provider}].";
            }
            if (empty($providerConfig['secret_key'])) {
                $errors[] = "Missing secret key for [{$provider}].";
            }
        }

        if ($environment === 'production' && ! $enabled) {
            $errors[] = 'CAPTCHA is disabled in production.';
        }

        $verifyHostname = (bool) ($config['verify_hostname'] ?? false);
        $allowedHostnames = array_values(array_filter(array_map(
            static fn($host) => strtolower(trim((string) $host)),
            (array) ($config['allowed_hostnames'] ?? [])
        )));

        if ($environment === 'production' && ! $verifyHostname) {
            $warnings[] = 'Hostname verification is not enabled. Configure allowed_hostnames and enable EII_CAPTCHA_VERIFY_HOSTNAME.';
        }

        if ($verifyHostname && $allowedHostnames === []) {
            $errors[] = 'Hostname verification is enabled, but allowed_hostnames is empty.';
        }

        if ($environment === 'production') {
            foreach (['site_key', 'secret_key'] as $key) {
                $value = (string) ($config['providers'][$provider][$key] ?? '');
                if (str_contains(strtolower($value), 'test') || str_contains(strtolower($value), 'dummy')) {
                    $errors[] = "The configured {$key} looks like a test credential.";
                }
            }
        }

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }
        foreach ($errors as $error) {
            $this->error($error);
        }

        if ($errors === []) {
            $this->info('No obvious CAPTCHA configuration errors detected.');
        }

        return $errors !== [] && (($config['strict'] ?? false) || $environment === 'production')
            ? self::FAILURE
            : self::SUCCESS;
    }
}
