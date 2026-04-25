<?php

namespace App\Logging;

use App\Models\AppLog;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseLogHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            AppLog::create([
                'level'   => strtolower($record->level->name),
                'channel' => $record->channel,
                'message' => $record->message,
                'context' => !empty($record->context) ? $record->context : null,
            ]);
        } catch (\Throwable $e) {
            // Silently fail — we can't log errors about logging failing
        }
    }
}
