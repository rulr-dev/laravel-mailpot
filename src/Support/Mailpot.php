<?php

namespace Rulr\Mailpot\Support;

use Illuminate\Support\Facades\File;

class Mailpot
{
    public static function inboxPath(): string
    {
        return storage_path('framework/mailpot');
    }

    public static function ensureInboxDirectory(): string
    {
        $path = self::inboxPath();

        if (! File::exists($path)) {
            File::makeDirectory($path, 0755, true);
            File::put($path.'/.gitignore', "*\n!.gitignore");
        }

        return $path;
    }

    public static function statsFilePath(): string
    {
        return self::inboxPath().'/stats.json';
    }

    public static function isMessageFile(string $filename): bool
    {
        return str_ends_with($filename, '.json') && $filename !== 'stats.json';
    }
}
