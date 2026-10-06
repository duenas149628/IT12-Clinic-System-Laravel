<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $from = (string) $request->query('from_date', '');
        $to = (string) $request->query('to_date', '');
        $event = mb_substr(trim((string) $request->query('event', '')), 0, 100);
        $userId = trim((string) $request->query('user_id', ''));
        $search = mb_substr(trim((string) $request->query('search', '')), 0, 150);
        $filterError = '';

        if (($from !== '' && ! $this->validDate($from))
            || ($to !== '' && ! $this->validDate($to))
            || ($from !== '' && $to !== '' && $from > $to)) {
            $filterError = 'Choose valid dates, with the start date on or before the end date.';
            $from = $to = '';
        }
        if ($userId !== '' && ! ctype_digit($userId)) {
            $userId = '';
        }

        $path = storage_path('app/private/activity.jsonl');
        $entries = [];
        if (is_file($path) && is_readable($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach (array_slice(array_reverse($lines), 0, 5000) as $line) {
                $entry = json_decode($line, true);
                if (is_array($entry)) {
                    $entries[] = $entry;
                }
            }
        }

        $events = collect($entries)->pluck('event')->filter()->unique()->sort()->values();
        $entries = collect($entries)->filter(function (array $entry) use ($from, $to, $event, $userId, $search): bool {
            $timestamp = (string) ($entry['time_utc'] ?? '');
            $day = substr($timestamp, 0, 10);
            if (($from !== '' && ($day < $from || ! $this->validDate($day)))
                || ($to !== '' && ($day > $to || ! $this->validDate($day)))) {
                return false;
            }
            if ($event !== '' && ($entry['event'] ?? '') !== $event) {
                return false;
            }
            if ($userId !== '' && (string) ($entry['user_id'] ?? '') !== $userId) {
                return false;
            }
            if ($search !== '') {
                $haystack = ($entry['event'] ?? '').' '.($entry['user_id'] ?? '').' '.json_encode($entry['details'] ?? []);
                if (stripos($haystack, $search) === false) {
                    return false;
                }
            }

            return true;
        })->values();

        return view('staff.activity-log', compact('entries', 'events', 'from', 'to', 'event', 'userId', 'search', 'filterError'));
    }

    private function validDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && \DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;
    }
}
