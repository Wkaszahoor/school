<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

echo "=== Testing Laravel Auth::attempt() ===\n\n";

$credentials = [
    'email' => 'admin@kort.org.uk',
    'password' => 'admin123'
];

echo "Attempting login with:\n";
echo "  Email: {$credentials['email']}\n";
echo "  Password: {$credentials['password']}\n\n";

if (Auth::attempt($credentials)) {
    echo "✅ Auth::attempt() SUCCESS!\n";
    $user = Auth::user();
    echo "  - Logged in as: {$user->name}\n";
    echo "  - Role: {$user->role}\n";
    echo "  - Dashboard: {$user->dashboardRoute()}\n";
} else {
    echo "❌ Auth::attempt() FAILED!\n";
    echo "\nDebugging...\n";

    $user = \App\Models\User::where('email', $credentials['email'])->first();
    if ($user) {
        echo "  User exists: Yes\n";
        echo "  Is active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
        echo "  Password check: " . (\Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password) ? 'PASS' : 'FAIL') . "\n";
    } else {
        echo "  User exists: No\n";
    }
}
