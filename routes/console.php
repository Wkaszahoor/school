<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:auth', function () {
    $user = \App\Models\User::where('email', 'admin@kort.org.uk')->first();
    $this->info('User: ' . $user->email);
    $this->info('Role: ' . $user->role);

    $password = 'admin123';
    $valid = \Illuminate\Support\Facades\Hash::check($password, $user->password);
    $this->info('Password "admin123" valid: ' . ($valid ? 'YES ✓' : 'NO ✗'));

    // Also try to authenticate
    if (\Illuminate\Support\Facades\Auth::attempt(['email' => $user->email, 'password' => $password])) {
        $this->info('Auth::attempt() successful ✓');
    } else {
        $this->error('Auth::attempt() failed ✗');
    }
})->purpose('Test authentication');
