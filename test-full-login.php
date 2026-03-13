<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;

echo "=== Full Login Flow Test ===\n\n";

// Step 1: Get login page to get CSRF token
echo "1. Getting login page...\n";
$getRequest = Request::create('/login', 'GET');
$getResponse = $kernel->handle($getRequest);
echo "   Status: {$getResponse->getStatusCode()}\n";

// Extract CSRF token from session
$session = $getRequest->getSession();
$csrfToken = $session->token();
echo "   CSRF Token: " . substr($csrfToken, 0, 20) . "...\n\n";

// Step 2: Submit login form
echo "2. Submitting login form...\n";
$postRequest = Request::create('/login', 'POST', [
    '_token' => $csrfToken,
    'email' => 'admin@kort.org.uk',
    'password' => 'admin123',
    'remember' => false,
]);

// Copy session from GET request
$postRequest->setLaravelSession($session);

$postResponse = $kernel->handle($postRequest);
echo "   Status: {$postResponse->getStatusCode()}\n";

if ($postResponse->isRedirection()) {
    echo "   ✅ Redirected to: {$postResponse->headers->get('Location')}\n";
    echo "   This is SUCCESS - login worked!\n";
} else {
    echo "   ❌ Not redirected\n";
    echo "   Response content (first 500 chars):\n";
    echo "   " . substr($postResponse->getContent(), 0, 500) . "\n";
}

// Check session
if ($session->has('url.intended')) {
    echo "\n3. Intended URL: {$session->get('url.intended')}\n";
}

// Check for errors
$errors = $session->get('errors');
if ($errors) {
    echo "\n❌ Errors found:\n";
    foreach ($errors->all() as $error) {
        echo "   - $error\n";
    }
}

$kernel->terminate($postRequest, $postResponse);
