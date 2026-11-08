<?php

namespace Eii\Captcha\Livewire;

use Eii\Captcha\CaptchaManager;
use Livewire\Component;

class CaptchaWidget extends Component
{
    public string $token = '';

    public string $field = 'captcha';

    public function mount(?string $field = null): void
    {
        $this->field = $field ?: (string) config('eii-captcha.field', 'captcha');
    }

    public function render()
    {
        return view('eii-captcha::livewire.widget', [
            'captchaManager' => app(CaptchaManager::class),
        ]);
    }
}
