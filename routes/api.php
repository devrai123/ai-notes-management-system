<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

Route::get(
    'notes/search',
    [NoteController::class, 'search']
);

Route::apiResource('notes', NoteController::class);

Route::post(
    'notes/{note}/summary',
    [NoteController::class, 'summary']
);