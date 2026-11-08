<?php

namespace Eii\Captcha\Rules;

use Closure;
use Eii\Captcha\CaptchaManager;
use Illuminate\Contracts\Validation\ValidationRule;

class CaptchaRule implements ValidationRule
{
    public function __construct(
        private ?string $provider = null,
        private ?string $field = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $manager = app(CaptchaManager::class);

        if (! $manager->enabled()) {
            return;
        }

        $token = is_string($value) ? $value : null;

        if (($token === null || trim($token) === '') && is_string($this->field) && $this->field !== '') {
            $fieldValue = request()->input($this->field);
            $token = is_string($fieldValue) ? $fieldValue : $token;
        }

        if (! $manager->verify($token, request()->ip(), $this->provider)) {
            $fail('The CAPTCHA verification failed. Please try again.');
        }
    }
}
