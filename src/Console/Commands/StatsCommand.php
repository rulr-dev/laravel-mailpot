<?php

namespace Rulr\Mailpot\Console\Commands;

use Illuminate\Console\Command;
use Rulr\Mailpot\Support\Mailpot;
use Rulr\Mailpot\Support\Statistics;

class StatsCommand extends Command
{
    protected $signature = 'mailpot:stats';
    protected $description = 'Show statistics about the stored emails in Mailpot';

    public function handle(): void
    {
        $directory = Mailpot::ensureInboxDirectory();
        $statsPath = Mailpot::statsFilePath();

        $messages = Statistics::collectMessages($directory);

        if ($messages->isEmpty()) {
            if (file_exists($statsPath)) {
                $summary = Statistics::loadStats($statsPath);
                if ($summary !== null) {
                    $this->info("📬 Mailpot Stats (From Previously Saved Data)");
                    $this->displayStats($summary);
                }
            } else {
                $this->warn("No messages and no stats file found.");
            }
            return;
        }

        $summary = Statistics::generate($messages);
        Statistics::saveStats($summary, $statsPath);

        $this->info("📬 Mailpot Inbox Stats (Updated)");
        $this->displayStats($summary);
    }

    /**
     * @param array{total: int, total_size: int, largest: int, smallest: int, last_updated: string, latest_message: array{date: string|null, from: string|null, to: list<string>, subject: string|null}} $summary
     */
    protected function displayStats(array $summary): void
    {
        $this->line("Total messages:     <info>{$summary['total']}</info>");
        $this->line("Total storage used: <info>" . Statistics::formatBytes($summary['total_size']) . "</info>");
        $this->line("Largest message:    <info>" . Statistics::formatBytes($summary['largest']) . "</info>");
        $this->line("Smallest message:   <info>" . Statistics::formatBytes($summary['smallest']) . "</info>");

        $latest = $summary['latest_message'];
        if ($latest['date'] !== null) {
            $this->line("Latest message:");
            $this->line("  📅 Date:     " . $latest['date']);
            $this->line("  ✉️ From:     " . ($latest['from'] ?? 'N/A'));
            $this->line("  ➡️ To:       " . implode(', ', $latest['to']));
            $this->line("  📝 Subject:  " . ($latest['subject'] ?? '(No subject)'));
        }
    }
}
