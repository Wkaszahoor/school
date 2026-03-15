use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin;

// Auth endpoint for Next.js frontend
Route::post('/v1/auth/login', [LoginController::class, 'apiLogin']);
Route::post('/v1/auth/logout', [LoginController::class, 'apiLogout'])->middleware('auth:sanctum');

// Admin API routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin')->group(function () {
    Route::get('/students', [Admin\StudentsController::class, 'apiIndex']);
    Route::get('/students/{student}', [Admin\StudentsController::class, 'apiShow']);
    Route::post('/students', [Admin\StudentsController::class, 'apiStore']);
    Route::put('/students/{student}', [Admin\StudentsController::class, 'apiUpdate']);
    Route::delete('/students/{student}', [Admin\StudentsController::class, 'apiDestroy']);

    Route::get('/teachers', [Admin\TeachersController::class, 'apiIndex']);
    Route::get('/teachers/{teacher}', [Admin\TeachersController::class, 'apiShow']);
    Route::post('/teachers', [Admin\TeachersController::class, 'apiStore']);
    Route::put('/teachers/{teacher}', [Admin\TeachersController::class, 'apiUpdate']);
    Route::delete('/teachers/{teacher}', [Admin\TeachersController::class, 'apiDestroy']);

    Route::get('/classes', [Admin\ClassesController::class, 'apiIndex']);
    Route::post('/classes', [Admin\ClassesController::class, 'apiStore']);
    Route::get('/classes/{class}', [Admin\ClassesController::class, 'apiShow']);
    Route::put('/classes/{class}', [Admin\ClassesController::class, 'apiUpdate']);
    Route::delete('/classes/{class}', [Admin\ClassesController::class, 'apiDestroy']);
});

// Classes endpoint for imports
Route::get('/classes', function () {
    return response()->json([
        'data' => \App\Models\SchoolClass::where('is_active', true)->select('id', 'class')->get()
    ]);
});
