<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('artists', 'artist.index')->name('artists.index');
    Route::livewire('artists/create', 'artist.form')->name('artists.create');
    Route::livewire('artists/{artist}/edit', 'artist.form')->name('artists.edit');
});

require __DIR__ . '/settings.php';
