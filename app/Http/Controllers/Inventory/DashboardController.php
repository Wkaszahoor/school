<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\{InventoryItem, InventoryCategory, InventoryStockEntry, InventoryStockIssue, InventoryLedger, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_items'     => InventoryItem::where('is_active', true)->count(),
            'low_stock'       => InventoryItem::where('is_active', true)
                ->whereRaw('current_stock <= min_stock_level')->count(),
            'categories'      => InventoryCategory::count(),
            'issued_today'    => InventoryStockIssue::whereDate('issue_date', today())->count(),
        ];

        $lowStockItems = InventoryItem::with('category')
            ->where('is_active', true)
            ->whereRaw('current_stock <= min_stock_level')
            ->get();

        $recentTransactions = InventoryLedger::with(['item', 'createdBy'])
            ->latest()
            ->take(10)
            ->get();

        $categories = InventoryCategory::withCount('items')->get();

        return Inertia::render('Inventory/Dashboard', compact(
            'stats', 'lowStockItems', 'recentTransactions', 'categories'
        ));
    }

    public function items(Request $request)
    {
        $items = InventoryItem::with('category')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->low_stock, fn($q) => $q->whereRaw('current_stock <= min_stock_level'))
            ->paginate(20)
            ->withQueryString();

        $categories = InventoryCategory::all(['id', 'name']);
        return Inertia::render('Inventory/Items', compact('items', 'categories'));
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'category_id'     => 'required|exists:inventory_categories,id',
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'unit'            => 'required|string|max:50',
            'current_stock'   => 'integer|min:0',
            'min_stock_level' => 'integer|min:0',
        ]);

        InventoryItem::create($data);
        return back()->with('success', 'Item added successfully.');
    }

    public function stockIn(Request $request)
    {
        $data = $request->validate([
            'item_id'    => 'required|exists:inventory_items,id',
            'quantity'   => 'required|integer|min:1',
            'supplier'   => 'nullable|string',
            'unit_price' => 'nullable|numeric',
            'notes'      => 'nullable|string',
        ]);

        $item = InventoryItem::findOrFail($data['item_id']);

        InventoryStockEntry::create([...$data, 'type' => 'in', 'received_by' => auth()->id(), 'entry_date' => today()]);

        $newStock = $item->current_stock + $data['quantity'];
        $item->update(['current_stock' => $newStock]);

        InventoryLedger::create([
            'item_id'          => $item->id,
            'transaction_type' => 'in',
            'quantity'         => $data['quantity'],
            'balance_after'    => $newStock,
            'created_by'       => auth()->id(),
            'notes'            => $data['notes'] ?? null,
        ]);

        AuditLog::log('stock_in', 'InventoryItem', $item->id, null, ['quantity' => $data['quantity'], 'new_stock' => $newStock]);
        return back()->with('success', 'Stock added.');
    }

    public function stockOut(Request $request)
    {
        $data = $request->validate([
            'item_id'        => 'required|exists:inventory_items,id',
            'quantity'       => 'required|integer|min:1',
            'issued_to_type' => 'required|in:teacher,class,department',
            'purpose'        => 'required|string',
            'notes'          => 'nullable|string',
        ]);

        $item = InventoryItem::findOrFail($data['item_id']);
        if ($item->current_stock < $data['quantity']) {
            return back()->withErrors(['quantity' => 'Insufficient stock.']);
        }

        InventoryStockIssue::create([...$data, 'issued_by' => auth()->id(), 'issue_date' => today()]);

        $newStock = $item->current_stock - $data['quantity'];
        $item->update(['current_stock' => $newStock]);

        InventoryLedger::create([
            'item_id'          => $item->id,
            'transaction_type' => 'out',
            'quantity'         => $data['quantity'],
            'balance_after'    => $newStock,
            'created_by'       => auth()->id(),
            'notes'            => $data['purpose'],
        ]);

        AuditLog::log('stock_out', 'InventoryItem', $item->id, null, ['quantity' => $data['quantity'], 'new_stock' => $newStock]);
        return back()->with('success', 'Stock issued.');
    }
}
