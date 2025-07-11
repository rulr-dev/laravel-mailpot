<?php

namespace Rulr\Mailpot\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class Statistics
{
    public static function collectMessages(string $directory): Collection
    {
        return collect(File::files($directory))
            ->filter(fn($file) => Mailpot::isMessageFile($file->getFilename()));
    }

    public static function generate(Collection $files): array
    {
        $total = $files->count();
        $sizes = $files->map(fn($file) => $file->getSize());
        $largest = $sizes->max() ?? 0;
        $smallest = $sizes->min() ?? 0;
        $totalSize = $sizes->sum();

        $latestFile = $files->sortByDesc(fn($file) => $file->getCTime())->first();
        $latestParsed = $latestFile ? json_decode(File::get($latestFile->getRealPath()), true) : null;

        return [
            'total'          => $total,
            'total_size'     => $totalSize,
            'largest'        => $largest,
            'smallest'       => $smallest,
            'last_updated'   => now()->toDateTimeString(),
            'latest_message' => [
                'date'    => $latestParsed['date'] ?? null,
                'from'    => $latestParsed['from'] ?? null,
                'to'      => $latestParsed['to'] ?? [],
                'subject' => $latestParsed['subject'] ?? null,
            ],
        ];
    }

    public static function saveStats(array $data, string $path): void
    {
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function loadStats(string $path): ?array
    {
        return File::exists($path)
            ? json_decode(File::get($path), true)
            : null;
    }

    public static function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1 << (10 * $pow)), $precision) . ' ' . $units[$pow];
    }

    public static function update(string $directory): void
    {
        $statsPath = $directory . '/stats.json';

        $files = self::collectMessages($directory);
        $summary = self::generate($files);
        self::saveStats($summary, $statsPath);
    }
}
