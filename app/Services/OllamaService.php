<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;
    private string $model;
    private int $timeout;
    private string $keepAlive;

    public function __construct()
    {
        $this->baseUrl   = rtrim(config('services.ollama.url', 'http://127.0.0.1:11434'), '/');
        $this->model     = config('services.ollama.model', 'llama3.2:3b');
        $this->timeout   = (int) config('services.ollama.timeout', 180);
        $this->keepAlive = config('services.ollama.keep_alive', '24h');
    }

    /**
     * Pre-carga el modelo en memoria (sin generar). Ideal para warmup.
     */
    public function warmup(): bool
    {
        try {
            $response = Http::timeout(120)->post($this->baseUrl . '/api/generate', [
                'model'      => $this->model,
                'prompt'     => '',
                'stream'     => false,
                'keep_alive' => $this->keepAlive,
            ]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Ollama warmup failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Genera respuesta a partir de un prompt.
     */
    public function generate(string $prompt, ?string $system = null): string
    {
        $payload = [
            'model'      => $this->model,
            'prompt'     => $prompt,
            'stream'     => false,
            'keep_alive' => $this->keepAlive,
            'options'    => [
                'temperature' => 0.3,
                'num_predict' => 600,
            ],
        ];

        if ($system) {
            $payload['system'] = $system;
        }

        $response = Http::timeout($this->timeout)
            ->post($this->baseUrl . '/api/generate', $payload);

        if (! $response->successful()) {
            Log::error('Ollama error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Servicio IA no disponible (HTTP ' . $response->status() . ')');
        }

        return trim($response->json('response', ''));
    }

    public function ping(): bool
    {
        try {
            return Http::timeout(3)->get($this->baseUrl)->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
