<?php

namespace Eii\Captcha\Tests\Unit;

use Eii\Captcha\CaptchaManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class CaptchaManagerTest extends TestCase
{
    public function test_missing_token_fails_when_enabled(): void
    {
        $manager = new CaptchaManager([
            'enabled' => true,
            'provider' => 'turnstile',
            'timeout' => 1,
            'providers' => [
                'turnstile' => [
                    'site_key' => 'site',
                    'secret_key' => 'secret',
                    'verify_url' => 'https://example.invalid/verify',
                ],
            ],
        ]);

        $this->assertFalse($manager->verify(null));
        $this->assertFalse($manager->verify(''));
    }

    public function test_disabled_captcha_skips_verification(): void
    {
        $manager = new CaptchaManager(['enabled' => false, 'provider' => 'turnstile', 'providers' => []]);

        $this->assertTrue($manager->verify(null));
    }

    public function test_invalid_ip_fails_fast(): void
    {
        Http::fake();
        Log::spy();

        $manager = new CaptchaManager([
            'enabled' => true,
            'provider' => 'turnstile',
            'providers' => [
                'turnstile' => [
                    'site_key' => 'site',
                    'secret_key' => 'secret',
                    'verify_url' => 'https://example.invalid/verify',
                ],
            ],
        ]);

        $this->assertFalse($manager->verify('token', 'not-an-ip'));
        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_hostname_verification_rejects_unknown_hostname(): void
    {
        Http::fake([
            'https://example.invalid/verify' => Http::response([
                'success' => true,
                'hostname' => 'evil.example',
            ]),
        ]);

        $manager = new CaptchaManager([
            'enabled' => true,
            'provider' => 'turnstile',
            'verify_hostname' => true,
            'allowed_hostnames' => ['example.com'],
            'providers' => [
                'turnstile' => [
                    'site_key' => 'site',
                    'secret_key' => 'secret',
                    'verify_url' => 'https://example.invalid/verify',
                ],
            ],
        ]);

        $this->assertFalse($manager->verify('token', '127.0.0.1'));
    }

    public function test_hostname_verification_accepts_allowed_hostname(): void
    {
        Http::fake([
            'https://example.invalid/verify' => Http::response([
                'success' => true,
                'hostname' => 'example.com',
            ]),
        ]);

        $manager = new CaptchaManager([
            'enabled' => true,
            'provider' => 'turnstile',
            'verify_hostname' => true,
            'allowed_hostnames' => ['example.com'],
            'providers' => [
                'turnstile' => [
                    'site_key' => 'site',
                    'secret_key' => 'secret',
                    'verify_url' => 'https://example.invalid/verify',
                ],
            ],
        ]);

        $this->assertTrue($manager->verify('token', '127.0.0.1'));
    }
}
