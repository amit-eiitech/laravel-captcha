<?php

namespace Eii\Captcha\Contracts;

interface CaptchaProvider
{
    /**
     * Verify a user-supplied token with the provider.
     *
     * @return array{success: bool, hostname?: string, score?: float, action?: string, errors?: array<int, string>, raw?: array}
     */
    public function verify(string $token, ?string $ip = null): array;

    public function siteKey(): ?string;

    public function name(): string;
}
