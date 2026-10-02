<?php

namespace App\Service;

use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use React\Promise\PromiseInterface;

class AsyncLlmClient
{

    private const TIMEOUT = 60;

    private Browser $browser;
    private string $baseUrl;
    private string $apiKey;
    private string $model;

    public function __construct(string $baseUrl, string $apiKey, string $model)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->model = $model;

        $this->browser = (new Browser())->withTimeout(self::TIMEOUT);
    }

    public function ask(string $prompt, string $systemPrompt = ''): PromiseInterface
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

        $body = json_encode([
            'model' => $this->model,
            'messages' => $messages,
        ], JSON_UNESCAPED_UNICODE);

        return $this->browser
            ->post($this->baseUrl . '/chat/completions', $headers, $body)
            ->then(function (ResponseInterface $response) {
                $data = json_decode((string) $response->getBody(), true);
                $answer = trim($data['choices'][0]['message']['content'] ?? '');

                if ($answer === '') {
                    throw new \RuntimeException('Модель вернула пустой ответ');
                }

                return $answer;
            });
    }

    public function explainError(\Throwable $e): string
    {
        if ($e instanceof ResponseException) {
            $code = $e->getCode();
            if ($code === 404) {
                return 'модель ' . $this->model . ' не найдена. Если это ollama, она, возможно, ещё скачивается';
            }
            if ($code === 401 || $code === 403) {
                return 'LLM не приняла ключ API, проверьте LLM_API_KEY';
            }
            if ($code === 429) {
                return 'слишком много запросов к LLM, попробуйте через минуту';
            }

            return 'LLM ответила ошибкой ' . $code;
        }

        $message = $e->getMessage();
        if (str_contains($message, 'timed out')) {
            return 'модель не ответила за ' . self::TIMEOUT . ' секунд';
        }
        if (str_contains($message, 'Connection') || str_contains($message, 'DNS')) {
            return 'сервер LLM недоступен';
        }

        return $message;
    }
}
