<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Patient;
use Google\Client as GoogleClient;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendToPatient($patientId, $title, $body, $type = 'info',bool $silent = false)
    {
        //  1. Simpan ke MySQL
        $notification = null;

if (!$silent) {

    // 1. Simpan ke MySQL
    $notification = Notification::create([
        'patient_id' => $patientId,
        'title' => $title,
        'body'  => $body,
        'type'  => $type,
    ]);

    // 2. Sync ke Firestore
    try {
        $factory = (new Factory)
            ->withServiceAccount(config('firebase.file'));

        $firestore = $factory->createFirestore()->database();

        $firestore->collection('notifications')
            ->document('N' . $notification->notification_id)
            ->set([
                'patient_id' => $patientId,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'created_at' => now()->toDateTimeString(),
                'is_read' => false,
            ]);
    } catch (\Throwable $e) {
        Log::error('Firestore sync failed: ' . $e->getMessage());
    }
}
        //  3. Push noti ke device user via FCM V1
        $patient = Patient::find($patientId);
        if ($patient && $patient->fcm_token) {
            $this->sendFcmV1($patient->fcm_token, $title, $body);
        }

        return $notification;
    }

    /**
     * " Firebase Cloud Messaging (V1 API)
     */
    private function sendFcmV1($token, $title, $body)
    {
        try {
            // 1 Generate access token guna service account JSON
            $client = new GoogleClient();
            $client->setAuthConfig(storage_path('app/firebase/service-account.json'));
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

            // 2 Project ID ambil dari service account file
            $json = json_decode(file_get_contents(storage_path('app/firebase/service-account.json')), true);
            $projectId = $json['project_id'];

            // 3 Endpoint untuk FCM v1
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // 4 Payload body
            $payload = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => $title,
                        "body"  => $body,
                    ],
                    "android" => [
                        "priority" => "high",
                        "notification" => [
                            "channel_id" => "mediqtrack_channel",
                            "sound" => "default"
                        ]
                    ],
                    "apns" => [
                        "payload" => [
                            "aps" => [
                                "sound" => "default"
                            ]
                        ]
                    ]
                ]
            ];

            // 5 Send guna Guzzle / Http
            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post($url, $payload);

            Log::info(' FCM V1 sent', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error(' FCM V1 failed: ' . $e->getMessage());
        }
    }
}
