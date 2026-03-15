use App\Http\Controllers\Auth\LoginController;

// Auth endpoint for Next.js frontend
Route::post('/v1/auth/login', [LoginController::class, 'apiLogin']);
Route::post('/v1/auth/logout', [LoginController::class, 'apiLogout'])->middleware('auth:sanctum');

// Classes endpoint for imports
Route::get('/classes', function () {
    return response()->json([
        'data' => \App\Models\SchoolClass::where('is_active', true)->select('id', 'class')->get()
    ]);
});
