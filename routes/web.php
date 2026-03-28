<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard');

    Volt::route('/dashboard', 'admin.dashboard')->name('dashboard');
    Volt::route('/tables', 'admin.tables.index')->name('tables');
    Volt::route('/categories', 'admin.categories.index')->name('categories');
    Volt::route('/menus', 'admin.menus.index')->name('menus');
    Volt::route('/users', 'admin.users.index')->name('users');
});

require __DIR__.'/auth.php';
