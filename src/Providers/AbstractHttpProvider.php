<?php

namespace Eii\Captcha\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractHttpProvider
{
    public function __construct(protected array $config, protected int $timeout = 5)
    {
    }

    protected function post(string $url, array $payload, bool $asForm = false): array
    {
        $request = Http::timeout($this->timeout)->acceptJson();

        $response = $asForm
            ? $request->asForm()->post($url, $payload)
            : $request->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('CAPTCHA provider verification request failed with HTTP '.$response->status().'.');
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('CAPTCHA provider returned an invalid response.');
        }

        return $data;
    }
}
