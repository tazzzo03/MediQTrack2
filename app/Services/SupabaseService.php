<?php

namespace App\Services;

use App\Models\Queue;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupabaseService
{
    private Client $client;
    private ?string $baseUrl;
    private ?string $apiKey;

    public function __construct(?Client $client = null)
    {
        $this->baseUrl = rtrim(config('services.supabase.url', ''), '/');
        $this->apiKey = config('services.supabase.key');
        $this->client = $client ?: new Client([
            'timeout' => 8,
        ]);
    }

    private function enabled(): bool
    {
        return filled($this->baseUrl) && filled($this->apiKey);
    }

    /**
     * Create a consultation log entry when doctor calls next patient.
     */
    public function logConsultationStart(Queue $queue): ?string
    {
        if (!$this->enabled()) {
            Log::warning('Supabase disabled, skipping logConsultationStart.');
            return null;
        }

        try {
            $payload = [
                'patient_id' => $queue->patient_id,
                'queue_id' => $queue->queue_id,
                'room_id' => $queue->room_id,
                'start_time' => now()->toIso8601String(),
            ];

            $response = $this->request('POST', 'consultation_logs', [
                'headers' => $this->headers(),
                'json' => $payload,
            ]);

            $data = json_decode($response, true);
            $id = $data[0]['id'] ?? null;
            Log::info('Supabase consultation start logged', ['queue' => $queue->queue_id, 'log_id' => $id]);
            return $id ? (string) $id : null;
        } catch (Throwable $e) {
            Log::error('Supabase logConsultationStart failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update end_time for the existing log once consultation is complete.
     */
    public function logConsultationEnd(Queue $queue): bool
    {
        if (!$this->enabled() || empty($queue->supabase_log_id)) {
            return false;
        }

        try {
            $this->request(
                'PATCH',
                'consultation_logs?id=eq.' . urlencode($queue->supabase_log_id),
                [
                    'headers' => $this->headers(['Prefer' => 'return=minimal']),
                    'json' => [
                        'end_time' => now()->toIso8601String(),
                    ],
                ]
            );

            Log::info('Supabase consultation end logged', [
                'queue' => $queue->queue_id,
                'log_id' => $queue->supabase_log_id,
            ]);
            return true;
        } catch (Throwable $e) {
            Log::error('Supabase logConsultationEnd failed: ' . $e->getMessage());
            return false;
        }
    }

    private function request(string $method, string $path, array $options = []): string
    {
        $url = "{$this->baseUrl}/rest/v1/{$path}";
        $response = $this->client->request($method, $url, $options);
        return (string) $response->getBody();
    }

    private function headers(array $extra = []): array
    {
        return array_merge([
            'apikey' => $this->apiKey,
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ], $extra);
    }
}
