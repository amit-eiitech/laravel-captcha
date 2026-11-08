@php
    $config = $captchaManager->configuration();
    $providerName = $config['provider'] ?? 'turnstile';
    $providerConfig = $config['providers'][$providerName] ?? [];
@endphp

<div>
    @if(($config['enabled'] ?? true) && !empty($providerConfig['site_key']))
        <input type="hidden" wire:model="token" name="{{ $field }}" value="{{ $token }}">
        <x-eii-captcha::widget :name="$field" />
        <script>
            document.addEventListener('livewire:init', () => {
                const componentId = @js($this->getId());
                window.addEventListener('eii-captcha-token', event => {
                    if (event.detail && event.detail.token) {
                        Livewire.find(componentId)?.set('token', event.detail.token);
                    }
                });
            }, { once: true });
        </script>
    @endif
</div>
