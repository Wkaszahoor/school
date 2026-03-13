<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\TimetableEntry;

class TimetableEntryController extends Controller
{
    public function update($entry) { return response()->json(['success' => true]); }
    public function lock($entry) { return response()->json(['success' => true]); }
    public function unlock($entry) { return response()->json(['success' => true]); }
    public function destroy($entry) { return response()->json(['success' => true]); }
    public function getAvailableOptions($entry) { return response()->json(['success' => true]); }
}
