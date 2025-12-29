<?php

use Illuminate\Support\Facades\Route;
use Rulr\Mailpot\Actions\GetInboxMessages;

Route::get('/', fn (GetInboxMessages $action) => view('mailpot::inbox', $action->execute()));
