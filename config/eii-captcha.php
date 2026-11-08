<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CAPTCHA protection
    |--------------------------------------------------------------------------
    |
    | This switch controls rendering and verification. It is deliberately not
    | inferred from APP_ENV. Do not disable CAPTCHA in production.
    |
    */
    'enabled' => env('EII_CAPTCHA_ENABLED', true),

    'provider' => env('EII_CAPTCHA_PROVIDER', 'turnstile'),

    'field' => 'captcha',

    'timeout' => 5,

    'verify_hostname' => env('EII_CAPTCHA_VERIFY_HOSTNAME', false),

    /*
     * Log successful CAPTCHA verifications for security auditing.
     * Enable this only if you need to track verification events for security monitoring.
     */
    'log_success' => env('EII_CAPTCHA_LOG_SUCCESS', false),

    /*
     * Allowed hostnames for hostname verification when EII_CAPTCHA_VERIFY_HOSTNAME is enabled.
     * This should match the domains configured in your provider's dashboard.
     * Separate multiple domains with commas in the .env file.
     */
    'allowed_hostnames' => array_values(array_filter(array_map(
        static fn($host) => trim($host),
        explode(',', env('EII_CAPTCHA_ALLOWED_HOSTNAMES', ''))
    ))),

    /*
     * If enabled, the diagnostic command exits unsuccessfully when it detects
     * obvious unsafe configuration. It cannot inspect provider dashboard
     * settings, and it is not a substitute for deployment review.
     */
    'strict' => env('EII_CAPTCHA_STRICT', false),

    'providers' => [
        'turnstile' => [
            'site_key' => env('TURNSTILE_SITE_KEY'),
            'secret_key' => env('TURNSTILE_SECRET_KEY'),
            'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            'theme' => env('TURNSTILE_THEME', 'auto'),
            'size' => env('TURNSTILE_SIZE', 'normal'),
            'appearance' => env('TURNSTILE_APPEARANCE', 'always'),
            'script_url' => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
        ],
        'recaptcha' => [
            'version' => env('RECAPTCHA_VERSION', 'v2'),
            'site_key' => env('RECAPTCHA_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_SECRET_KEY'),
            'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
            'script_url' => 'https://www.google.com/recaptcha/api.js',
            'score_threshold' => (float) env('RECAPTCHA_SCORE_THRESHOLD', 0.5),
            'expected_action' => env('RECAPTCHA_EXPECTED_ACTION'),
            'badge' => env('RECAPTCHA_BADGE', 'bottomright'),
            'size' => env('RECAPTCHA_SIZE', 'normal'),
        ],
    ],
];
