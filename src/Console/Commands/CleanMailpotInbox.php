<?php

namespace Rulr\Mailpot\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Rulr\Mailpot\Support\Mailpot;

class CleanMailpotInbox extends Command
{
    protected $signature = 'mailpot:clean';

    protected $description = 'Clean all stored Mailpot emails, with optional stats reset.';

    public function handle(): void
    {
        $path = Mailpot::ensureInboxDirectory();

        $deleted = $this->deleteInboxMessages($path);
        $this->info("Cleaned $deleted message(s) from Mailpot inbox.");

        if ($this->confirm('Do you also want to delete the stats file?', false)) {
            $this->deleteStatsFile($path);
        } else {
            $this->info('Stats were preserved.');
        }
    }

    protected function deleteInboxMessages(string $directory): int
    {
        $deleted = 0;

        foreach (File::files($directory) as $file) {
            if ($file->getFilename() === 'stats.json') {
                continue;
            }

            if ($file->getExtension() === 'json') {
                File::delete($file->getRealPath());
                $deleted++;
            }
        }

        return $deleted;
    }

    protected function deleteStatsFile(string $directory): void
    {
        $statsPath = $directory.'/stats.json';

        if (File::exists($statsPath)) {
            File::delete($statsPath);
            $this->info('Deleted stats.json.');
        } else {
            $this->warn('No stats.json file found.');
        }
    }
}
