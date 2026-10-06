<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLog
{
    public static function write(string $event, array $details = []): void
    {
        $entry = [
            'time_utc' => now('UTC')->toIso8601String(),
            'event' => preg_replace('/[^a-z0-9_.-]/i', '_', $event),
            'user_id' => Auth::id(),
            'ip_hash' => hash('sha256', (string) request()->ip()),
            'details' => $details,
        ];

        try {
            $path = storage_path('app/private/activity.jsonl');
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0700, true);
            }
            file_put_contents($path, json_encode($entry, JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $exception) {
            Log::warning('Unable to write clinic audit event.', ['event' => $entry['event']]);
        }
    }
}
