
// Classes endpoint for imports
Route::get('/classes', function () {
    return response()->json([
        'data' => \App\Models\SchoolClass::where('is_active', true)->select('id', 'class')->get()
    ]);
});
