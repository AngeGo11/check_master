<?php
namespace App\Services;
use Illuminate\Http\Response;
class MeetingCalendar
{
    public function download($meetings, string $filename): Response
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Check Master//Reunions//FR', 'CALSCALE:GREGORIAN'];
        foreach ($meetings as $m) {
            $lines = [...$lines, 'BEGIN:VEVENT', 'UID:'.$m->reference.'@check-master.local', 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
                'DTSTART:'.$m->starts_at->utc()->format('Ymd\THis\Z'), 'DTEND:'.$m->ends_at->utc()->format('Ymd\THis\Z'), 'SEQUENCE:'.$m->revision,
                'SUMMARY:'.$this->escape($m->title), 'LOCATION:'.$this->escape($m->location), 'DESCRIPTION:'.$this->escape($m->description ?? ''),
                'STATUS:'.($m->cancelled_at ? 'CANCELLED' : 'CONFIRMED'), 'END:VEVENT'];
        }
        $lines[] = 'END:VCALENDAR';
        return response(implode("\r\n", array_map(fn ($line) => $this->fold($line), $lines))."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
    private function escape(string $value): string { return str_replace(["\\", "\r\n", "\r", "\n", ';', ','], ["\\\\", "\n", "\n", '\\n', '\\;', '\\,'], $value); }
    private function fold(string $line): string
    {
        $parts = []; $width = 75;
        while (strlen($line) > $width) { $part = mb_strcut($line, 0, $width, 'UTF-8'); $parts[] = $part; $line = substr($line, strlen($part)); $width = 74; }
        $parts[] = $line; return implode("\r\n ", $parts);
    }
}
