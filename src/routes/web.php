<?php
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\SplFileInfo;

Route::get('/', function () {
    $files = collect(File::files(storage_path('framework/mailpot')))
        ->filter(fn (SplFileInfo $file): bool =>
            str_ends_with($file->getFilename(), '.json') &&
            $file->getFilename() !== 'stats.json'
        )
        ->sortByDesc(fn (SplFileInfo $file): int => $file->getCTime())
        ->map(function (SplFileInfo $file): array {
            $content = file_get_contents($file->getRealPath());
            return [
                'filename' => $file->getFilename(),
                'path'     => $file->getRealPath(),
                'parsed'   => $content !== false ? json_decode($content, true) : null,
            ];
        });

    return view('mailpot::inbox', ['messages' => $files]);
});
