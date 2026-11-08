@php
    $captchaConfig = config('eii-captcha');
    $providerName = $provider ?? ($captchaConfig['provider'] ?? 'turnstile');
    $providerConfig = $captchaConfig['providers'][$providerName] ?? [];
    $fieldName = $name ?? ($captchaConfig['field'] ?? 'captcha');
    $widgetId = 'eii-captcha-' . \Illuminate\Support\Str::uuid();
    $isRecaptchaV3 = $providerName === 'recaptcha' && ($providerConfig['version'] ?? 'v2') === 'v3';
@endphp

@if (($captchaConfig['enabled'] ?? true) && !empty($providerConfig['site_key']))
    <div id="{{ $widgetId }}" data-eii-captcha-provider="{{ $providerName }}" data-eii-captcha-version="{{ $providerConfig['version'] ?? 'v2' }}" data-eii-captcha-sitekey="{{ $providerConfig['site_key'] }}" data-eii-captcha-action="{{ $action ?? ($providerConfig['expected_action'] ?? 'submit') }}" {{ $attributes }}>
        <input type="hidden" name="{{ $fieldName }}" value="">
        @if ($providerName === 'turnstile')
            <div class="cf-turnstile" data-sitekey="{{ $providerConfig['site_key'] }}" data-theme="{{ $theme ?? ($providerConfig['theme'] ?? 'auto') }}" data-size="{{ $size ?? ($providerConfig['size'] ?? 'normal') }}" data-callback="window.eiiCaptchaCallback" data-expired-callback="window.eiiCaptchaExpired" data-error-callback="window.eiiCaptchaExpired"></div>
        @elseif($providerName === 'recaptcha' && ($providerConfig['version'] ?? 'v2') === 'v2')
            <div class="g-recaptcha" data-sitekey="{{ $providerConfig['site_key'] }}" data-size="{{ $size ?? ($providerConfig['size'] ?? 'normal') }}" data-callback="window.eiiCaptchaCallback" data-expired-callback="window.eiiCaptchaExpired" data-error-callback="window.eiiCaptchaExpired"></div>
        @elseif($isRecaptchaV3)
            <button type="button" class="eii-captcha-v3-trigger" hidden aria-hidden="true" tabindex="-1">Verify CAPTCHA</button>
        @endif
    </div>
    @once
        @push('scripts')
            <script>
                window.eiiCaptchaSetToken = function(container, token) {
                    if (!container) {
                        return;
                    }

                    const input = container.querySelector('input[type="hidden"]');

                    if (!input) {
                        return;
                    }

                    input.value = token;
                    input.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    input.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                };

                window.eiiCaptchaCallback = function(token) {
                    const active = document.activeElement;
                    const root = active && active.closest ? active.closest('[id^="eii-captcha-"]') : null;
                    const iframeContainer = document.querySelector('[id^="eii-captcha-"] .cf-turnstile iframe, [id^="eii-captcha-"] .g-recaptcha iframe');
                    const fallbackContainer = iframeContainer ? iframeContainer.closest('[id^="eii-captcha-"]') : null;
                    const container = root || fallbackContainer;

                    window.eiiCaptchaSetToken(container, token);
                };

                window.eiiCaptchaExpired = function() {
                    document.querySelectorAll('[id^="eii-captcha-"] input[type="hidden"]').forEach(input => {
                        input.value = '';
                        input.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                    });
                };

                window.eiiCaptchaExecuteV3 = function(container) {
                    if (!container || !window.grecaptcha) {
                        return Promise.reject(new Error('reCAPTCHA v3 is not available.'));
                    }

                    const siteKey = container.dataset.eiiCaptchaSitekey;
                    const action = container.dataset.eiiCaptchaAction || 'submit';

                    if (!siteKey) {
                        return Promise.reject(new Error('Missing reCAPTCHA site key.'));
                    }

                    return new Promise((resolve, reject) => {
                        window.grecaptcha.ready(() => {
                            window.grecaptcha.execute(siteKey, {
                                action
                            }).then(token => {
                                window.eiiCaptchaSetToken(container, token);
                                resolve(token);
                            }).catch(reject);
                        });
                    });
                };



                document.addEventListener('submit', event => {
                    const form = event.target;

                    if (!(form instanceof HTMLFormElement)) {
                        return;
                    }

                    const container = form.querySelector('[id^="eii-captcha-"][data-eii-captcha-provider="recaptcha"][data-eii-captcha-version="v3"]');

                    if (!container) {
                        return;
                    }

                    if (form.dataset.eiiCaptchaSubmitting === '1') {
                        delete form.dataset.eiiCaptchaSubmitting;

                        return;
                    }

                    event.preventDefault();

                    window.eiiCaptchaExecuteV3(container)
                        .then(() => {
                            form.dataset.eiiCaptchaSubmitting = '1';

                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();

                                return;
                            }

                            form.submit();
                        })
                        .catch(() => {
                            // Intentionally fail closed: keep form blocked when token generation fails.
                        });
                }, true);
            </script>
            @if ($isRecaptchaV3)
                <script src="{{ $providerConfig['script_url'] }}?render={{ $providerConfig['site_key'] }}" async defer></script>
            @else
                <script src="{{ $providerConfig['script_url'] }}" async defer></script>
            @endif
        @endpush
    @endonce
@endif
