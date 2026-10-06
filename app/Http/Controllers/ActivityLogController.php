<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        $events = collect($entries)->pluck('event')->filter()->unique()
            ->mapWithKeys(fn (string $key): array => [$key => $this->eventLabel($key)])
            ->sort()->all();
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
                $haystack = ($entry['event'] ?? '').' '.$this->eventLabel((string) ($entry['event'] ?? '')).' '
                    .($entry['user_id'] ?? '').' '.$this->detailsSummary($entry);
                if (stripos($haystack, $search) === false) {
                    return false;
                }
            }

            return true;
        })->values();

        $userIds = $entries->pluck('user_id')->filter(fn ($id): bool => is_numeric($id))->unique()->values();
        $userNames = $userIds->isEmpty()
            ? collect()
            : User::whereIn('user_id', $userIds)->pluck('name', 'user_id');
        $entries = $entries->map(function (array $entry) use ($userNames): array {
            $event = (string) ($entry['event'] ?? 'unknown');
            $userId = $entry['user_id'] ?? null;
            $entry['event_label'] = $this->eventLabel($event);
            $entry['user_label'] = is_numeric($userId)
                ? ($userNames->get($userId) ?? 'Account #'.$userId)
                : 'Visitor / unauthenticated';
            $entry['details_summary'] = $this->detailsSummary($entry);

            return $entry;
        });

        return view('staff.activity-log', compact('entries', 'events', 'from', 'to', 'event', 'userId', 'search', 'filterError'));
    }

    private function eventLabel(string $event): string
    {
        return match ($event) {
            'account.registered' => 'Patient account registered',
            'appointment.action.submitted' => 'Appointment action submitted',
            'appointment.cancelled' => 'Appointment cancelled',
            'appointment.completed' => 'Appointment completed',
            'appointment.no_show' => 'Patient marked as no-show',
            'appointment.request.created' => 'Appointment requested',
            'appointment.request.created_by_staff' => 'Appointment request created by staff',
            'appointment.schedule.confirmed' => 'Appointment scheduled',
            'login.failure' => 'Sign-in failed',
            'login.success' => 'Signed in',
            'logout' => 'Signed out',
            'password.change.failure' => 'Password change failed',
            'password.change.success' => 'Password changed',
            'patient.account.created_by_staff' => 'Patient account created by staff',
            'patient.profile.updated' => 'Patient profile updated',
            'patient.record.updated' => 'Patient record updated',
            'visit.record.created' => 'Visit record created',
            'visit.record.updated' => 'Visit record updated',
            default => ucfirst(str_replace(['.', '_'], ' ', $event)),
        };
    }

    private function detailsSummary(array $entry): string
    {
        $details = is_array($entry['details'] ?? null) ? $entry['details'] : [];
        $event = (string) ($entry['event'] ?? '');

        if ($event === 'login.failure') {
            return 'A sign-in attempt was rejected.';
        }

        $summary = [];
        foreach (['appointment_id' => 'Appointment', 'patient_id' => 'Patient record', 'visit_id' => 'Visit record', 'new_user_id' => 'Account'] as $key => $label) {
            if (isset($details[$key]) && is_scalar($details[$key])) {
                $summary[] = $label.' #'.$details[$key];
            }
        }

        if (isset($details['action']) && is_string($details['action'])) {
            $action = match ($details['action']) {
                'accept' => 'Accept appointment',
                'confirm' => 'Confirm appointment',
                'reschedule' => 'Reschedule appointment',
                'cancel' => 'Cancel appointment',
                'completed' => 'Mark completed',
                'no_show' => 'Mark no-show',
                default => ucfirst(str_replace('_', ' ', $details['action'])),
            };
            $summary[] = 'Action: '.$action;
        }

        return $summary === [] ? 'No additional details.' : implode(' · ', $summary);
    }

    private function validDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && \DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') === $date;
    }
}
