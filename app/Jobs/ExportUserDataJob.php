<?php

namespace App\Jobs;

use App\Mail\DataExportReady;
use App\Models\Contact;
use App\Models\DataExport;
use App\Models\File;
use App\Models\Message;
use App\Models\Timetable;
use App\Models\User;
use App\Services\CryptoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportUserDataJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public readonly int $exportId,
    ) {}

    public function handle(CryptoService $crypto): void
    {
        $export = DataExport::findOrFail($this->exportId);
        $export->update(['status' => 'processing']);

        try {
            $user = User::with('expanded')->findOrFail($export->user_id);

            Storage::disk('local')->makeDirectory('exports');
            $zipPath = Storage::disk('local')->path('exports/' . $export->token . '.zip');

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Cannot create zip archive at: ' . $zipPath);
            }

            $this->addAccountSection($zip, $user);
            $this->addContactsSection($zip, $user);
            $this->addFilesSection($zip, $user, $crypto);
            $this->addChatsSection($zip, $user, $crypto);
            $this->addDiarySection($zip, $user);
            $this->addTimetableSection($zip, $user);

            $zip->close();

            $export->update([
                'status'   => 'ready',
                'zip_path' => 'exports/' . $export->token . '.zip',
                'ready_at' => now(),
            ]);

            $locale = $user->expanded->language ?? config('app.locale', 'en');
            Mail::to($user->email)->locale($locale)->send(new DataExportReady(
                route('export.download', ['token' => $export->token])
            ));
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed']);
            Log::error('Data export failed for user ' . $export->user_id . ': ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    // ── Account ───────────────────────────────────────────────────────────────

    private function addAccountSection(ZipArchive $zip, User $user): void
    {
        $expanded = $user->expanded;
        $registered = $user->registered
            ? date('Y-m-d H:i:s', $user->registered)
            : 'N/A';

        $name    = htmlspecialchars($expanded->name ?? '');
        $surname = htmlspecialchars($expanded->surname ?? '');
        $username = htmlspecialchars($user->username ?? '');
        $email   = htmlspecialchars($user->email ?? '');

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"><title>Account Data</title>
        <style>body{font-family:Arial,sans-serif;max-width:600px;margin:2rem auto;padding:1rem}
        table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:.6rem 1rem;text-align:left}
        th{background:#f4f4f4;width:35%}</style>
        </head>
        <body>
        <h1>Account Data</h1>
        <table>
            <tr><th>Name</th><td>{$name}</td></tr>
            <tr><th>Surname</th><td>{$surname}</td></tr>
            <tr><th>Username</th><td>{$username}</td></tr>
            <tr><th>Email</th><td>{$email}</td></tr>
            <tr><th>Registered</th><td>{$registered}</td></tr>
        </table>
        </body>
        </html>
        HTML;

        $zip->addFromString('account/account.html', $html);
    }

    // ── Contacts (vCard .vcf format) ──────────────────────────────────────────

    private function addContactsSection(ZipArchive $zip, User $user): void
    {
        // Include all contacts: active, trashed, and soft-deleted
        $contacts = Contact::where('user_id', $user->id)
            ->with(['contactUser.expanded'])
            ->get();

        foreach ($contacts as $contact) {
            $isDeleted = $contact->deleted || $contact->trash;

            if ($contact->contact_id && $contact->contactUser) {
                $linked   = $contact->contactUser;
                $name     = $linked->expanded->name ?? $linked->username ?? 'Unknown';
                $surname  = $linked->expanded->surname ?? '';
                $username = $linked->username ?? '';
            } else {
                $name     = $contact->name;
                $surname  = $contact->surname ?? '';
                $username = '';
            }

            $vcf      = $this->buildVCard($name, $surname, $username);
            $filename = $this->sanitizeFilename($name . ($surname ? ' ' . $surname : ''));

            if ($isDeleted) {
                $filename .= '.trashed';
            }

            $zip->addFromString("contacts/{$filename}.vcf", $vcf);
        }
    }

    private function buildVCard(string $name, string $surname, string $username): string
    {
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:' . $surname . ';' . $name . ';;;',
            'FN:' . trim($name . ' ' . $surname),
        ];

        if ($username !== '') {
            $lines[] = 'NICKNAME:' . $username;
        }

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines) . "\r\n";
    }

    // ── Files (folder hierarchy, notebooks, uploaded files) ───────────────────

    private function addFilesSection(ZipArchive $zip, User $user, CryptoService $crypto): void
    {
        // All non-diary file types (folders, notebooks, uploaded files)
        $files = File::where('user_id', $user->id)
            ->whereNotIn('type', ['diary'])
            ->get()
            ->keyBy('id');

        foreach ($files as $file) {
            if ($file->type === 'folder') {
                continue; // directories are created implicitly by ZipArchive
            }

            $dirPath   = $this->buildDirPath($file, $files);
            $isDeleted = $file->trash || $file->deleted_at !== null;

            if ($file->type === 'notebook') {
                $content = $this->decryptFileContent($file, $user->id, $crypto);
                $html    = $this->wrapInHtml(
                    htmlspecialchars($file->name),
                    ($file->header ?? '') . $content . ($file->footer ?? '')
                );
                $suffix   = $isDeleted ? '.trashed.html' : '.html';
                $filename = $this->sanitizeFilename($file->name) . $suffix;
                $zip->addFromString("files/{$dirPath}{$filename}", $html);

            } elseif ($file->type === 'file' && $file->file_url) {
                try {
                    $localPath = Storage::disk('uploads')->path($file->file_url);
                    if (is_file($localPath)) {
                        $nameExt  = pathinfo($file->name, PATHINFO_EXTENSION);
                        if ($nameExt !== '') {
                            $base = $this->sanitizeFilename(pathinfo($file->name, PATHINFO_FILENAME));
                            $ext  = $nameExt;
                        } else {
                            $base = $this->sanitizeFilename($file->name);
                            $ext  = $this->extensionFromMime($file->file_type)
                                ?? pathinfo($file->file_url, PATHINFO_EXTENSION);
                        }
                        $suffix   = $isDeleted ? '.trashed' : '';
                        $filename = $base . $suffix . ($ext ? '.' . $ext : '');
                        $zip->addFile($localPath, "files/{$dirPath}{$filename}");
                    }
                } catch (\Throwable) {
                    // Skip files that cannot be read from storage
                }
            }
        }
    }

    private function decryptFileContent(File $file, int $userId, CryptoService $crypto): string
    {
        if (!$file->html) {
            return '';
        }

        if ($file->cypher) {
            try {
                $raw = $crypto->decrypt($file->html, $file->cypher, $userId);
            } catch (\Throwable) {
                return '<p><em>[Content could not be decrypted]</em></p>';
            }
        } else {
            $raw = $file->html;
        }

        return $this->normalizeNotebookContent($raw);
    }

    /**
     * Detect and convert Quill Delta JSON to HTML.
     * Handles both raw JSON strings and base64-encoded JSON (used in legacy unencrypted storage).
     */
    private function normalizeNotebookContent(string $raw): string
    {
        // Try direct parse first (decrypted sodium content may be Quill Delta JSON)
        $ops = json_decode($raw, true);
        if (is_array($ops) && isset($ops[0]['insert'])) {
            return $this->quillDeltaToHtml($ops);
        }

        // Try base64-decode (unencrypted notebooks sometimes store base64-encoded Quill Delta)
        $decoded = base64_decode($raw, true);
        if ($decoded !== false) {
            $ops = json_decode($decoded, true);
            if (is_array($ops) && isset($ops[0]['insert'])) {
                return $this->quillDeltaToHtml($ops);
            }
        }

        return $raw;
    }

    /**
     * Convert a Quill Delta ops array to basic HTML.
     */
    private function quillDeltaToHtml(array $ops): string
    {
        $lines       = [];
        $currentLine = '';

        foreach ($ops as $op) {
            if (!array_key_exists('insert', $op) || !is_string($op['insert'])) {
                continue; // skip embeds
            }

            $insert = $op['insert'];
            $attrs  = $op['attributes'] ?? [];
            $parts  = explode("\n", $insert);

            foreach ($parts as $i => $part) {
                if ($part !== '') {
                    $text = htmlspecialchars($part);
                    if (!empty($attrs['bold']))      $text = "<strong>{$text}</strong>";
                    if (!empty($attrs['italic']))     $text = "<em>{$text}</em>";
                    if (!empty($attrs['underline']))  $text = "<u>{$text}</u>";
                    $currentLine .= $text;
                }

                // Every \n flushes the current line; the op's attributes describe the line type
                if ($i < count($parts) - 1) {
                    $header = $attrs['header'] ?? null;
                    $list   = $attrs['list'] ?? null;

                    if (is_int($header) && $header >= 1 && $header <= 6) {
                        $lines[] = "<h{$header}>{$currentLine}</h{$header}>";
                    } elseif ($list) {
                        $lines[] = "<li>{$currentLine}</li>";
                    } else {
                        $lines[] = '<p>' . ($currentLine !== '' ? $currentLine : '&nbsp;') . '</p>';
                    }
                    $currentLine = '';
                }
            }
        }

        if ($currentLine !== '') {
            $lines[] = "<p>{$currentLine}</p>";
        }

        return implode("\n", $lines);
    }

    /**
     * Build the zip directory path for a file by walking its parent folders.
     *
     * @param Collection<int, File> $allFiles
     */
    private function buildDirPath(File $file, Collection $allFiles): string
    {
        if (!$file->folder) {
            return '';
        }

        $parts   = [];
        $current = $file;

        while ($current->folder && isset($allFiles[$current->folder])) {
            /** @var File $parent */
            $parent = $allFiles[$current->folder];
            array_unshift($parts, $this->sanitizeFilename($parent->name));
            $current = $parent;
        }

        return implode('/', $parts) . '/';
    }

    // ── Chats ─────────────────────────────────────────────────────────────────

    private function addChatsSection(ZipArchive $zip, User $user, CryptoService $crypto): void
    {
        $threads = Message::forUser($user->id)
            ->with(['actors.user.expanded', 'chats.senderUser.expanded'])
            ->get();

        $indexItems = '';
        $counter    = 1;

        foreach ($threads as $thread) {
            $dirName = 'chat_' . $counter;

            // Build a human-readable title from the other participants' names
            if ($thread->subject) {
                $subject = $thread->subject;
            } else {
                $names = $thread->actors
                    ->where('user_id', '!=', $user->id)
                    ->map(function ($actor) {
                        $exp      = $actor->user?->expanded;
                        $fullName = trim(($exp->name ?? '') . ' ' . ($exp->surname ?? ''));
                        return $fullName ?: ($actor->user->username ?? 'Unknown');
                    })
                    ->filter()
                    ->join(', ');
                $subject = $names ?: ('Chat ' . $counter);
            }

            $subjectEsc  = htmlspecialchars($subject);
            $indexItems .= "<li><a href=\"{$dirName}/index.html\">{$subjectEsc}</a></li>\n";

            $messagesHtml = '';
            foreach ($thread->chats->sortBy('date') as $msg) {
                /** @var \App\Models\MessageChat $msg */
                $sender     = $msg->senderUser;
                $exp        = $sender?->expanded;
                $senderName = htmlspecialchars(
                    trim(($exp->name ?? '') . ' ' . ($exp->surname ?? ''))
                    ?: ($sender->username ?? 'Unknown')
                );
                $date = $msg->date ? $msg->date->format('Y-m-d H:i') : '';
                $body = $this->decryptMessageBody($msg, $user->id, $crypto);

                $messagesHtml .= <<<HTML
                <div class="message">
                    <div class="meta"><strong>{$senderName}</strong> <small>{$date}</small></div>
                    <div class="body">{$body}</div>
                </div>
                HTML;
            }

            $chatHtml = $this->wrapInHtml(
                $subjectEsc,
                "<p><a href=\"../index.html\">&larr; Back to chats</a></p>"
                . "<h1>{$subjectEsc}</h1>"
                . "<div class=\"messages\">{$messagesHtml}</div>"
            );
            $zip->addFromString("chats/{$dirName}/index.html", $chatHtml);

            $counter++;
        }

        $indexHtml = $this->wrapInHtml('Chats', "<h1>Chats</h1><ul>{$indexItems}</ul>");
        $zip->addFromString('chats/index.html', $indexHtml);
    }

    private function decryptMessageBody(\App\Models\MessageChat $msg, int $userId, CryptoService $crypto): string
    {
        if (!$msg->body || !$msg->cypher) {
            return htmlspecialchars($msg->body ?? '');
        }

        try {
            $result = $crypto->decryptMessage($msg->body, null, $msg->cypher, $userId);
            return htmlspecialchars($result['body']);
        } catch (\Throwable) {
            return '<em>[Message could not be decrypted]</em>';
        }
    }

    // ── Diary (ICS format) ────────────────────────────────────────────────────

    private function addDiarySection(ZipArchive $zip, User $user): void
    {
        $entries = File::where('user_id', $user->id)
            ->where('type', 'diary')
            ->get();

        $events = '';

        foreach ($entries as $entry) {
            $isDeleted = $entry->trash || $entry->deleted_at !== null;
            $summary   = $this->escapeIcsText($entry->name . ($isDeleted ? ' [TRASHED]' : ''));
            $uid       = $entry->id . '-diary@lightschool-export';
            $dtStamp   = now()->format('Ymd\THis\Z');

            $events .= "BEGIN:VEVENT\r\n";
            $events .= "UID:{$uid}\r\n";
            $events .= "DTSTAMP:{$dtStamp}\r\n";

            if ($entry->diary_date) {
                $date    = \Carbon\Carbon::parse($entry->diary_date)->format('Ymd');
                $events .= "DTSTART;VALUE=DATE:{$date}\r\n";
            }

            $events .= "SUMMARY:{$summary}\r\n";

            if ($entry->diary_type) {
                $events .= 'CATEGORIES:' . $this->escapeIcsText($entry->diary_type) . "\r\n";
            }

            if ($entry->diary_priority) {
                // ICS PRIORITY: 1=high, 5=medium, 9=low (map from 0-based priority)
                $events .= 'PRIORITY:' . max(1, min(9, (int) $entry->diary_priority)) . "\r\n";
            }

            $events .= "END:VEVENT\r\n";
        }

        $ics = "BEGIN:VCALENDAR\r\n"
            . "VERSION:2.0\r\n"
            . "PRODID:-//LightSchool//Data Export//EN\r\n"
            . "CALSCALE:GREGORIAN\r\n"
            . $events
            . "END:VCALENDAR\r\n";

        $zip->addFromString('diary/diary.ics', $ics);
    }

    // ── Timetable (.md + .json) ───────────────────────────────────────────────

    private function addTimetableSection(ZipArchive $zip, User $user): void
    {
        $entries = Timetable::forUser($user->id)
            ->orderBy('year')
            ->orderBy('day')
            ->orderBy('slot')
            ->get();

        $zip->addFromString(
            'timetable/timetable.json',
            json_encode($entries->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $md       = "# Timetable\n\n";

        foreach ($entries->groupBy('year') as $year => $yearEntries) {
            $md .= "## Year: {$year}\n\n";
            $md .= "| Day | Slot | Subject | Book |\n";
            $md .= "|-----|------|---------|------|\n";

            foreach ($yearEntries as $entry) {
                $day = $dayNames[$entry->day - 1] ?? "Day {$entry->day}";
                $md .= '| ' . $day
                    . ' | ' . $entry->slot
                    . ' | ' . $entry->subject
                    . ' | ' . ($entry->book ?? '')
                    . " |\n";
            }

            $md .= "\n";
        }

        $zip->addFromString('timetable/timetable.md', $md);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function wrapInHtml(string $title, string $body): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"><title>{$title}</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 2rem auto; padding: 1rem; color: #333; }
            .message { border-bottom: 1px solid #eee; padding: .75rem 0; }
            .message .meta { margin-bottom: .25rem; color: #555; }
            .message .body { white-space: pre-wrap; }
            a { color: #1E6BC9; }
        </style>
        </head>
        <body>{$body}</body>
        </html>
        HTML;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9\-_. ]/', '_', $name);
        return trim($name) ?: 'file';
    }

    private function escapeIcsText(string $text): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $text);
    }

    private function extensionFromMime(?string $mime): ?string
    {
        if (!$mime) {
            return null;
        }

        return match ($mime) {
            'application/pdf'      => 'pdf',
            'image/jpeg'           => 'jpg',
            'image/png'            => 'png',
            'image/gif'            => 'gif',
            'image/webp'           => 'webp',
            'image/svg+xml'        => 'svg',
            'text/plain'           => 'txt',
            'text/html'            => 'html',
            'application/msword'   => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/vnd.ms-excel'                                                  => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/zip'      => 'zip',
            'video/mp4'            => 'mp4',
            'video/quicktime'      => 'mov',
            'audio/mpeg'           => 'mp3',
            'audio/ogg'            => 'ogg',
            default                => null,
        };
    }
}
