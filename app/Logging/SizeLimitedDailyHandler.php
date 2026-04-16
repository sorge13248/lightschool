<?php

namespace App\Logging;

use DateTimeImmutable;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Writes to daily rotating log files, splitting into numbered parts when a
 * file exceeds $maxBytes. Files older than $maxDays days are pruned on rotation.
 *
 * File naming: laravel-YYYY-MM-DD.log, laravel-YYYY-MM-DD-2.log, …
 */
class SizeLimitedDailyHandler extends AbstractProcessingHandler
{
    private ?StreamHandler $currentStream = null;
    private string $currentDate = '';
    private int $sequence = 0;

    public function __construct(
        private readonly string $basePath,
        private readonly int $maxBytes,
        private readonly int $maxDays,
        Level $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $today = date('Y-m-d');

        if ($today !== $this->currentDate) {
            $this->closeStream();
            $this->currentDate = $today;
            $this->sequence    = 0;
            $this->pruneOldFiles();
        } elseif ($this->currentStream !== null) {
            $path = $this->currentPath();
            if (is_file($path) && filesize($path) >= $this->maxBytes) {
                $this->closeStream();
                $this->sequence++;
            }
        }

        if ($this->currentStream === null) {
            $this->currentStream = new StreamHandler($this->currentPath(), $this->level, $this->bubble);
        }

        $this->currentStream->handle($record);
    }

    private function currentPath(): string
    {
        $dir  = dirname($this->basePath);
        $stem = pathinfo($this->basePath, PATHINFO_FILENAME);
        $ext  = pathinfo($this->basePath, PATHINFO_EXTENSION) ?: 'log';
        $seq  = $this->sequence > 0 ? "-{$this->sequence}" : '';

        return "{$dir}/{$stem}-{$this->currentDate}{$seq}.{$ext}";
    }

    private function pruneOldFiles(): void
    {
        $dir    = dirname($this->basePath);
        $stem   = pathinfo($this->basePath, PATHINFO_FILENAME);
        $ext    = pathinfo($this->basePath, PATHINFO_EXTENSION) ?: 'log';
        $cutoff = new DateTimeImmutable("-{$this->maxDays} days");

        foreach (glob("{$dir}/{$stem}-*.{$ext}") ?: [] as $file) {
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', basename($file), $m)
                && new DateTimeImmutable($m[1]) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function closeStream(): void
    {
        $this->currentStream?->close();
        $this->currentStream = null;
    }

    public function close(): void
    {
        $this->closeStream();
        parent::close();
    }
}
