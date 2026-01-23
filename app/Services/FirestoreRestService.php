<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirestoreRestService
{
    private const SCOPE = 'https://www.googleapis.com/auth/datastore';

    private string $projectId;
    private string $baseUrl;
    private array $creds;

    public function __construct()
    {
        $this->projectId = (string) env('FIREBASE_PROJECT_ID', '');
        $keyPath = config('firebase.file');

        if (!is_string($keyPath) || !file_exists($keyPath)) {
            throw new \RuntimeException('Firestore credentials file not found: ' . (string) $keyPath);
        }

        $json = file_get_contents($keyPath);
        $this->creds = json_decode((string) $json, true) ?: [];
        $this->baseUrl = sprintf(
            'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents',
            $this->projectId
        );
    }

    public function getDocument(string $path): ?array
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        $response = Http::withToken($token)->get($url);
        if (!$response->successful()) {
            Log::error('Firestore REST get failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    public function patchDocument(string $path, array $fields, array $updateMask = []): bool
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        if (!empty($updateMask)) {
            $query = [];
            foreach ($updateMask as $fieldPath) {
                $query[] = 'updateMask.fieldPaths=' . rawurlencode($fieldPath);
            }
            $url .= '?' . implode('&', $query);
        }

        $payload = [
            'fields' => $this->encodeFields($fields),
        ];

        $response = Http::withToken($token)->patch($url, $payload);
        if (!$response->successful()) {
            Log::error('Firestore REST patch failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    public function deleteDocument(string $path): bool
    {
        $token = $this->getAccessToken();
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        $response = Http::withToken($token)->delete($url);
        if (!$response->successful() && $response->status() !== 404) {
            Log::error('Firestore REST delete failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    public function decodeFields(?array $fields): array
    {
        if (!$fields) {
            return [];
        }

        $result = [];
        foreach ($fields as $key => $value) {
            $result[$key] = $this->decodeValue($value);
        }
        return $result;
    }

    private function getAccessToken(): string
    {
        $jwt = new ServiceAccountCredentials([self::SCOPE], $this->creds);
        $token = $jwt->fetchAuthToken();
        return $token['access_token'] ?? '';
    }

    private function encodeFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->encodeValue($value);
        }
        return $fields;
    }

    private function encodeValue($value): array
    {
        if ($value === null) {
            return ['nullValue' => null];
        }

        if ($value instanceof \DateTimeInterface) {
            return ['timestampValue' => $value->format(\DateTimeInterface::ATOM)];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_string($value)) {
            return ['stringValue' => $value];
        }

        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                return ['mapValue' => ['fields' => $this->encodeFields($value)]];
            }

            $values = [];
            foreach ($value as $item) {
                $values[] = $this->encodeValue($item);
            }
            return ['arrayValue' => ['values' => $values]];
        }

        return ['stringValue' => (string) $value];
    }

    private function decodeValue(array $value)
    {
        if (array_key_exists('stringValue', $value)) {
            return $value['stringValue'];
        }
        if (array_key_exists('integerValue', $value)) {
            return (int) $value['integerValue'];
        }
        if (array_key_exists('doubleValue', $value)) {
            return (float) $value['doubleValue'];
        }
        if (array_key_exists('booleanValue', $value)) {
            return (bool) $value['booleanValue'];
        }
        if (array_key_exists('timestampValue', $value)) {
            return $value['timestampValue'];
        }
        if (array_key_exists('mapValue', $value)) {
            return $this->decodeFields($value['mapValue']['fields'] ?? []);
        }
        if (array_key_exists('arrayValue', $value)) {
            $items = [];
            foreach ($value['arrayValue']['values'] ?? [] as $item) {
                $items[] = $this->decodeValue($item);
            }
            return $items;
        }
        return null;
    }

    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
