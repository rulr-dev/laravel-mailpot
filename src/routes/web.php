<?php
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $files = collect(File::files(storage_path('framework/mailpot')))
        ->filter(fn ($file) =>
            str_ends_with($file->getFilename(), '.json') &&
            $file->getFilename() !== 'stats.json'
        )
        ->sortByDesc(fn ($file) => $file->getCTime())
        ->map(function ($file) {
            $content = file_get_contents($file->getRealPath());
            return [
                'filename' => $file->getFilename(),
                'path'     => $file->getRealPath(),
                'parsed'   => json_decode($content, true),
            ];
        });

    return view('mailpot::inbox', ['messages' => $files]);
});
