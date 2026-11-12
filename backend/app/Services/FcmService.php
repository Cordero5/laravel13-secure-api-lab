<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * True when push notifications are configured and switched on.
     */
    public function isEnabled(): bool
    {
        return (bool) config('fcm.enabled') && !empty(config('fcm.server_key'));
    }

    /**
     * Device tokens for every tenant account that has registered a device.
     *
     * @return array<int, string>
     */
    public function tenantTokens(): array
    {
        return User::query()
            ->where('role', 'tenant')
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Broadcast an announcement to all registered tenant devices.
     *
     * Never throws: a push failure must not roll back an admin's publish action.
     *
     * @return array{status: string, sent: int, reason?: string}
     */
    public function broadcast(string $title, string $body): array
    {
        if (!$this->isEnabled()) {
            Log::info('FCM skipped: push notifications are not configured.', [
                'title'  => $title,
                'hint'   => 'Set FCM_ENABLED=true and FCM_SERVER_KEY in .env to enable.',
            ]);

            return ['status' => 'skipped', 'sent' => 0, 'reason' => 'not_configured'];
        }

        $tokens = $this->tenantTokens();

        if ($tokens === []) {
            Log::info('FCM skipped: no registered device tokens.');

            return ['status' => 'skipped', 'sent' => 0, 'reason' => 'no_tokens'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('fcm.server_key'),
                'Content-Type'  => 'application/json',
            ])->timeout((int) config('fcm.timeout', 5))
              ->post('https://fcm.googleapis.com/fcm/send', [
                  'registration_ids' => $tokens,
                  'notification' => [
                      'title' => $title,
                      'body'  => $body,
                  ],
                  'data' => [
                      'type' => 'announcement',
                  ],
              ]);

            $results = $response->json('results', []);
            $sent = collect($results)->where('message_id', '!=', null)->count();

            Log::info('FCM broadcast attempted.', [
                'targets'  => count($tokens),
                'sent'     => $sent,
                'http'     => $response->status(),
            ]);

            return ['status' => 'attempted', 'sent' => $sent];
        } catch (\Throwable $e) {
            Log::warning('FCM broadcast failed.', ['error' => $e->getMessage()]);

            return ['status' => 'failed', 'sent' => 0, 'reason' => $e->getMessage()];
        }
    }
}
