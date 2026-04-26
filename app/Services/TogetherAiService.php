<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TogetherAiService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.together.xyz/v1';
    protected $model = 'google/gemma-4-31B-it';

    protected $fallbackModels = [
        'meta-llama/Llama-3-8b-chat-hf',
        'google/gemma-2-9b-it',
        'mistralai/Mistral-7B-Instruct-v0.2',
        'Qwen/Qwen2-7B-Instruct',
    ];
    
    public function __construct()
    {
        $this->apiKey = env('TOGETHER_AI_KEY');
    }

    /**
     * Generate article content based on title
     *
     * @param string $title
     * @return string
     */
    public function generateArticleContent($title, $fallbackAttempt = 0)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Together AI API Key is missing');
        }

        $isArabicTitle = preg_match('/[\x{0600}-\x{06FF}]/u', (string) $title) === 1;

        if ($isArabicTitle) {
            $systemPrompt = 'أنت كاتب عربي محترف من الأردن. اكتب نصاً عربياً فقط بدون أي مقدمات أو تعليقات أو عناوين.';
            $userPrompt = "اكتب محتوى مقال احترافي عن العنوان التالي: \"{$title}\". طول النص من 6 إلى 10 أسطر. لا تكتب العنوان، اكتب نص المقال فقط. ممنوع كتابة أي جملة تمهيدية مثل: \"هذا مسودة...\" أو \"إليك\".";
        } else {
            $systemPrompt = 'You are a professional writer. Write only the article body without any preface or labels.';
            $userPrompt = "Write a professional article about: \"{$title}\". The content should be 6 to 10 lines. Do not include the title, only the body.";
        }

        $currentModel = $fallbackAttempt === 0 ? $this->model : $this->fallbackModels[$fallbackAttempt - 1] ?? null;

        if (!$currentModel) {
            throw new \Exception('Failed to generate content: All AI models unavailable');
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
                'model' => $currentModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'max_tokens' => 512,
                'temperature' => 0.7,
                'top_p' => 0.7,
                'top_k' => 50,
                'repetition_penalty' => 1,
                'stop' => ['<|eot_id|>']
            ]);

            if ($response->failed()) {
                $errorBody = $response->body();
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown error';

                Log::warning("Together AI API Error (model: {$currentModel}): {$errorMessage}");

                if ($fallbackAttempt < count($this->fallbackModels)) {
                    Log::info("Attempting fallback model: " . $this->fallbackModels[$fallbackAttempt]);
                    return $this->generateArticleContent($title, $fallbackAttempt + 1);
                }

                throw new \Exception('Failed to generate content: ' . $errorMessage);
            }

            $data = $response->json();

            if (isset($data['choices'][0]['message']['content'])) {
                $content = trim($data['choices'][0]['message']['content']);

                if ($isArabicTitle) {
                    $arabicPos = preg_match('/[\x{0600}-\x{06FF}]/u', $content, $m, PREG_OFFSET_CAPTURE)
                        ? $m[0][1]
                        : null;
                    if ($arabicPos !== null && $arabicPos > 0) {
                        $content = ltrim(substr($content, $arabicPos));
                    }
                }

                if ($fallbackAttempt > 0) {
                    Log::info("Content generated successfully using fallback model: {$currentModel}");
                }

                return $content;
            }

            throw new \Exception('Invalid response format from AI provider');

        } catch (\Exception $e) {
            Log::error('AI Generation Exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
