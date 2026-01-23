<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;

class FCMController extends Controller
{
    // " Tukar ikut Project ID Firebase kau
    private const PROJECT_ID = 'mediqtrack-d6aa7';

    // " Pastikan path ni sama dengan fail service account kau
    // (kalau kau guna nama lain, ubah sini)
    private const SERVICE_JSON_PATH = 'app/firebase/service-account.json';

    /**
     * Dapatkan access_token untuk FCM v1 API menggunakan service account
     */
    private static function getAccessToken(): string
    {
        $jsonPath = storage_path(self::SERVICE_JSON_PATH);
        $credsArr = json_decode(file_get_contents($jsonPath), true);

        $jwt = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $credsArr
        );

        $token = $jwt->fetchAuthToken();
        return $token['access_token'];
    }

    /**
     * Hantar notifikasi ke token tertentu
     */
    private static function pushToToken(string $token, string $title, string $body): array
    {
        $accessToken = self::getAccessToken();

        $endpoint = "https://fcm.googleapis.com/v1/projects/" . self::PROJECT_ID . "/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                // Notification message ?+' akan keluar walaupun app ditutup
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'mediqtrack_channel',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
                // (Optional) data tambahan
                // 'data' => [
                //     'route' => 'queue_detail',
                //     'queue_id' => '123',
                // ],
            ],

        ];

        $res = Http::withToken($accessToken)->post($endpoint, $payload);

        return [
            'status' => $res->status(),
            'body'   => $res->json(),
        ];
    }

    /**
     *  Helper UTAMA: panggil dari mana-mana controller
     * Contoh guna:
     *   FCMController::sendToPatient($patientId, 'Now Serving', 'Sila ke Room 3');
     */
    public static function sendToPatient(int $patientId, string $title, string $body): bool
    {
        // Delegate to NotificationService so it also saves to MySQL and Firestore
        try {
            (new \App\Services\NotificationService())->sendToPatient($patientId, $title, $body);
            return true;
        } catch (\Throwable $e) {
            Log::error('FCM sendToPatient failed', [
                'patient_id' => $patientId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * (Opsyenal) Endpoint manual untuk test dari Postman
     * POST /api/send-fcm
     * { "token": "...", "title": "Hello", "body": "World" }
     */
    public function sendToToken(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'title' => 'required|string',
            'body'  => 'required|string',
        ]);

        $result = self::pushToToken($data['token'], $data['title'], $data['body']);

        return response()->json($result, $result['status']);
    }
}
