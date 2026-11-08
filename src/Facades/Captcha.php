<?php

namespace Eii\Captcha\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool enabled()
 * @method static string providerName()
 * @method static \Eii\Captcha\Contracts\CaptchaProvider provider(?string $name = null)
 * @method static bool verify(?string $token, ?string $ip = null, ?string $provider = null)
 */
class Captcha extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Eii\Captcha\CaptchaManager::class;
    }
}
