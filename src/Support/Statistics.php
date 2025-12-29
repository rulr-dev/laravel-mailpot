<?php

namespace Rulr\Mailpot\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;

class Statistics
{
    /**
     * @return Collection<int, SplFileInfo>
     */
    public static function collectMessages(string $directory): Collection
    {
        /** @var Collection<int, SplFileInfo> */
        return collect(File::files($directory))
            ->filter(fn(SplFileInfo $file): bool => Mailpot::isMessageFile($file->getFilename()));
    }

    /**
     * @param Collection<int, SplFileInfo> $files
     * @return array{total: int, total_size: int, largest: int, smallest: int, last_updated: string, latest_message: array{date: string|null, from: string|null, to: list<string>, subject: string|null}}
     */
    public static function generate(Collection $files): array
    {
        $total = $files->count();
        $sizes = $files->map(fn(SplFileInfo $file): int => $file->getSize());
        /** @var int $largest */
        $largest = $sizes->max() ?? 0;
        /** @var int $smallest */
        $smallest = $sizes->min() ?? 0;
        /** @var int $totalSize */
        $totalSize = $sizes->sum();

        $latestFile = $files->sortByDesc(fn(SplFileInfo $file): int => $file->getCTime())->first();
        $latestParsed = null;
        if ($latestFile instanceof SplFileInfo) {
            $content = File::get($latestFile->getRealPath());
            /** @var array{date?: string, from?: string, to?: list<string>, subject?: string}|null $latestParsed */
            $latestParsed = json_decode($content, true);
        }

        /** @var list<string> $toList */
        $toList = is_array($latestParsed) && isset($latestParsed['to']) && is_array($latestParsed['to'])
            ? $latestParsed['to']
            : [];

        return [
            'total'          => $total,
            'total_size'     => $totalSize,
            'largest'        => $largest,
            'smallest'       => $smallest,
            'last_updated'   => now()->toDateTimeString(),
            'latest_message' => [
                'date'    => is_array($latestParsed) ? ($latestParsed['date'] ?? null) : null,
                'from'    => is_array($latestParsed) ? ($latestParsed['from'] ?? null) : null,
                'to'      => $toList,
                'subject' => is_array($latestParsed) ? ($latestParsed['subject'] ?? null) : null,
            ],
        ];
    }

    /**
     * @param array{total: int, total_size: int, largest: int, smallest: int, last_updated: string, latest_message: array{date: string|null, from: string|null, to: list<string>, subject: string|null}} $data
     */
    public static function saveStats(array $data, string $path): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json !== false) {
            File::put($path, $json);
        }
    }

    /**
     * @return array{total: int, total_size: int, largest: int, smallest: int, last_updated: string, latest_message: array{date: string|null, from: string|null, to: list<string>, subject: string|null}}|null
     */
    public static function loadStats(string $path): ?array
    {
        if (!File::exists($path)) {
            return null;
        }

        $content = File::get($path);
        /** @var array{total: int, total_size: int, largest: int, smallest: int, last_updated: string, latest_message: array{date: string|null, from: string|null, to: list<string>, subject: string|null}}|null */
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = (int) floor(($bytes > 0 ? log($bytes) : 0) / log(1024));
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
