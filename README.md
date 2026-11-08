# Eii Laravel CAPTCHA

A configurable CAPTCHA integration for Laravel with Cloudflare Turnstile and Google reCAPTCHA adapters.

> **Development status:** initial scaffold. Review and test the frontend widget lifecycle and Livewire integration before using this package in production. The package is intentionally fail-closed during server verification, but a scaffold is not a security audit.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- Livewire is optional; install a compatible Livewire version if you want the Livewire component.

## Install

```bash
composer require eii/laravel-captcha
php artisan vendor:publish --tag=eii-captcha-config
```

Configure `.env`:

```dotenv
EII_CAPTCHA_ENABLED=true
EII_CAPTCHA_PROVIDER=turnstile
EII_CAPTCHA_VERIFY_HOSTNAME=false
EII_CAPTCHA_STRICT=false

TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=

# Or use Google reCAPTCHA:
# EII_CAPTCHA_PROVIDER=recaptcha
# RECAPTCHA_VERSION=v2
# RECAPTCHA_SITE_KEY=
# RECAPTCHA_SECRET_KEY=
# RECAPTCHA_SCORE_THRESHOLD=0.5
# RECAPTCHA_EXPECTED_ACTION=submit
```

For local testing, you can use provider test keys:

```dotenv
# Cloudflare Turnstile (always-pass testing keys)
TURNSTILE_SITE_KEY=1x00000000000000000000000000000000000000000
TURNSTILE_SECRET_KEY=1x00000000000000000000000000000000000000000

# Google reCAPTCHA v2 (official testing keys)
RECAPTCHA_SITE_KEY=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
RECAPTCHA_SECRET_KEY=6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
```

Google does not provide a single public always-pass key pair for reCAPTCHA v3 like v2. For v3 testing, create your own site keys in the Google admin console for your local/test domains.

Optional defense-in-depth settings:

```dotenv
# Secondary hostname verification in your Laravel app (off by default)
EII_CAPTCHA_VERIFY_HOSTNAME=false
EII_CAPTCHA_ALLOWED_HOSTNAMES=example.com,www.example.com

# Optional success-event logging for audit trails
EII_CAPTCHA_LOG_SUCCESS=false
```

Do not use test credentials in production. Use provider-documented test keys or HTTP fakes in local development and automated tests. Never disable server-side verification merely because `APP_ENV=local`.

## Blade

Make sure your layout includes `@stack('scripts')` before `</body>` so provider scripts and callbacks are loaded.

```blade
<form method="POST" action="{{ route('contact.store') }}">
    @csrf
    <input name="email" type="email" required>
    <x-eii-captcha name="captcha" />
    <button type="submit">Send</button>
</form>
```

Include a scripts stack in your layout:

```blade
@stack('scripts')
```

Controller:

```php
use Eii\Captcha\Rules\CaptchaRule;
use Illuminate\Http\Request;

public function store(Request $request)
{
    $request->validate([
        'email' => ['required', 'email'],
        'captcha' => ['required', new CaptchaRule()],
    ]);

    // Continue processing only after validation succeeds.
}
```

## Manual verification

```php
use Eii\Captcha\Facades\Captcha;

if (! Captcha::verify($request->input('captcha'), $request->ip())) {
    abort(422, 'CAPTCHA verification failed.');
}
```

## Livewire

If Livewire is installed, a component is registered as `eii-captcha-widget`. Example:

```blade
<form wire:submit="save">
    <input type="email" wire:model="email">
    <livewire:eii-captcha-widget field="captcha" />
    <button type="submit">Send</button>
</form>
```

In the Livewire component, validate the submitted token on the server with the `CaptchaManager` or `CaptchaRule`; do not trust a client-side success event alone. This integration is an initial scaffold and needs browser-level tests and lifecycle hardening before production use.

## reCAPTCHA v3 behavior

Set:

```dotenv
EII_CAPTCHA_PROVIDER=recaptcha
RECAPTCHA_VERSION=v3
RECAPTCHA_SITE_KEY=your_v3_site_key
RECAPTCHA_SECRET_KEY=your_v3_secret_key
RECAPTCHA_SCORE_THRESHOLD=0.5
RECAPTCHA_EXPECTED_ACTION=submit
```

When using v3, the widget executes `grecaptcha.execute()` and injects the token into the hidden field:

- when the form is submitted (automatic, recommended)
- when the optional `Verify CAPTCHA` button is clicked

The server validates token success, score threshold, and expected action (if configured).

## Diagnostic command

```bash
php artisan eii-captcha:check
```

The command checks common local configuration errors. It cannot inspect allowed hostnames or keys configured in the Google or Cloudflare dashboards. For production, configure a hostname allowlist and enable `EII_CAPTCHA_VERIFY_HOSTNAME=true`.

To use hostname verification, add this to the published config (if you prefer config over env):

```php
'verify_hostname' => true,
'allowed_hostnames' => ['example.com', 'www.example.com'],
```

## Security notes

- Provider secrets are server-side only.
- Verification failures and provider outages fail closed.
- Do not trust a client-side widget's success state.
- Use separate development and production provider credentials.
- Configure allowed hostnames in both the provider dashboard and this package config when `verify_hostname` is enabled.
- A localhost domain in a provider dashboard does not itself bypass server verification; accidentally accepting unverified tokens does.
- Verify reCAPTCHA v3 score and expected action for each protected operation.
- Review and test multiple widgets, Livewire DOM morphing, expired tokens, retries, CSP, and script loading before production deployment.
