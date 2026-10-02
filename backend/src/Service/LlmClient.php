<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LlmClient
{
    private HttpClientInterface $httpClient;
    private string $baseUrl;
    private string $apiKey;
    private string $model;

    public function __construct(HttpClientInterface $httpClient, string $baseUrl, string $apiKey, string $model)
    {
        $this->httpClient = $httpClient;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function ask(string $prompt, string $systemPrompt = ''): string
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($this->apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        $messages = [];
        if ($systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = $this->httpClient->request('POST', $this->baseUrl . '/chat/completions', [
            'headers' => $headers,
            'json' => [
                'model' => $this->model,
                'messages' => $messages,
            ],
            'timeout' => 60,
        ]);

        $data = $response->toArray();

        return trim($data['choices'][0]['message']['content'] ?? '');
    }
}
