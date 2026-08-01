<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private string $baseUrl;

    private string $apiKey;

    private string $model;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.groq.url', 'https://api.groq.com/openai/v1'), '/');
        $this->apiKey = (string) config('services.groq.key', '');
        $this->model = config('services.groq.model', 'llama-3.1-8b-instant');
        $this->timeout = (int) config('services.groq.timeout', 60);
    }

    /**
     * Genera respuesta a partir de un prompt.
     */
    public function generate(string $prompt, ?string $system = null): string
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('Falta configurar GROQ_API_KEY.');
        }

        $messages = [];
        if ($system) {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = Http::timeout($this->timeout)
            ->withToken($this->apiKey)
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.3,
                'max_tokens' => 600,
            ]);

        if (! $response->successful()) {
            Log::error('Groq error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Servicio IA no disponible (HTTP '.$response->status().')');
        }

        return trim((string) $response->json('choices.0.message.content', ''));
    }

    /**
     * Responde una pregunta del usuario usando un snapshot del inventario
     * como contexto.
     *
     * @param  array<string,mixed>  $context  Snapshot de stats del inventario.
     */
    public function chat(string $message, array $context = []): string
    {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $system = <<<SYS
Eres el asistente del módulo de inventario de un ERP. Respondes SIEMPRE en español,
de forma breve, clara y accionable. Usa ÚNICAMENTE los datos del contexto para
responder; si el dato no está en el contexto, dilo con honestidad y no lo inventes.

CONTEXTO ACTUAL DEL INVENTARIO (JSON):
```json
{$json}
```
SYS;

        return $this->generate($message, $system);
    }

    public function ping(): bool
    {
        if ($this->apiKey === '') {
            return false;
        }

        try {
            return Http::timeout(5)
                ->withToken($this->apiKey)
                ->get($this->baseUrl.'/models')
                ->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
