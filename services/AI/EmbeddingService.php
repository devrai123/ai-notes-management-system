<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EmbeddingService
{
    public function generate(string $text): array
    {
        $apiKey = config('services.openai.api_key');

        if (!$apiKey) {
            throw new RuntimeException(
                'OpenAI API key is not configured.'
            );
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(60)
            ->post(
                'https://api.openai.com/v1/embeddings',
                [
                    'model' => 'text-embedding-3-small',
                    'input' => $text,
                ]
            );

        if ($response->failed()) {
            throw new RuntimeException(
                'Embedding request failed: ' .
                $response->body()
            );
        }

        $embedding = $response->json(
            'data.0.embedding'
        );

        if (!$embedding) {
            throw new RuntimeException(
                'Embedding was not returned.'
            );
        }

        return $embedding;
    }
}