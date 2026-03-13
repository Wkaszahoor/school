<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== Testing Login Credentials ===\n\n";

$user = User::where('email', 'admin@kort.org.uk')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "✓ User found:\n";
echo "  - ID: {$user->id}\n";
echo "  - Email: {$user->email}\n";
echo "  - Name: {$user->name}\n";
echo "  - Role: {$user->role}\n";
echo "  - Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
echo "  - Password Hash: " . substr($user->password, 0, 30) . "...\n\n";

$testPassword = 'admin123';
echo "Testing password: '{$testPassword}'\n";

if (Hash::check($testPassword, $user->password)) {
    echo "✅ Password matches! Login should work.\n";
} else {
    echo "❌ Password does NOT match!\n";
    echo "\nLet's check the hash:\n";
    echo "Stored hash: {$user->password}\n";
    echo "Test hash:   " . Hash::make($testPassword) . "\n";
}
