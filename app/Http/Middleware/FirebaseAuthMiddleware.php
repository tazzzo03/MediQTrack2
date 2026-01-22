<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class FirebaseAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['ok' => false, 'error' => 'Missing token'], 401);
        }

        $idToken = substr($authHeader, 7);

        try {
            $firebase = (new Factory)
                ->withServiceAccount(base_path('storage/app/firebase-admin.json'))
                ->createAuth();

            $verified = $firebase->verifyIdToken($idToken);
            $uid = $verified->claims()->get('sub');

            // Simpan UID user supaya boleh guna di controller
            $request->merge(['firebase_uid' => $uid]);

            return $next($request);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['ok' => false, 'error' => 'Invalid or expired token'], 401);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
