<?php

namespace App\Logging;

use Monolog\Logger;

class DailyRotatingLogFactory
{
    public function __invoke(array $config): Logger
    {
        return new Logger('app', [
            new SizeLimitedDailyHandler(
                basePath: $config['path'],
                maxBytes: (int) (($config['max_mb'] ?? 10) * 1024 * 1024),
                maxDays:  (int) ($config['days'] ?? 90),
            ),
        ]);
    }
}
