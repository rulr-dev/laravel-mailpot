<?php

namespace Rulr\Mailpot\Actions;

use Illuminate\Support\Collection;
use Rulr\Mailpot\Support\Mailpot;
use Rulr\Mailpot\Support\Statistics;
use Symfony\Component\Finder\SplFileInfo;

class GetInboxMessages
{
    /**
     * @return array{messages: Collection<int, array{filename: string, path: string, parsed: array<string, mixed>|null}>, stats: array{total: int, total_size: int, largest: int, smallest: int, last_updated: string, latest_message: array{date: string|null, from: string|null, to: list<string>, subject: string|null}}|null}
     */
    public function execute(): array
    {
        $directory = Mailpot::ensureInboxDirectory();

        $messages = Statistics::collectMessages($directory)
            ->sortByDesc(fn (SplFileInfo $file): int => $file->getCTime())
            ->map(fn (SplFileInfo $file): array => $this->formatMessage($file))
            ->values();

        return [
            'messages' => $messages,
            'stats' => Statistics::loadStats(Mailpot::statsFilePath()),
        ];
    }

    /**
     * @return array{filename: string, path: string, parsed: array<string, mixed>|null}
     */
    private function formatMessage(SplFileInfo $file): array
    {
        $content = file_get_contents($file->getRealPath());

        /** @var array<string, mixed>|null $parsed */
        $parsed = $content !== false ? json_decode($content, true) : null;

        return [
            'filename' => $file->getFilename(),
            'path' => $file->getRealPath(),
            'parsed' => $parsed,
        ];
    }
}
