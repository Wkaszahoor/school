import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { CubeIcon, ExclamationTriangleIcon, PlusIcon, ArrowDownIcon, ArrowUpIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import StatCard from '@/Components/StatCard';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import type { PageProps, InventoryItem, InventoryCategory, InventoryLedger } from '@/types';

interface DashboardProps extends PageProps {
    stats: {
        total_items: number;
        low_stock: number;
        categories: number;
        issued_today: number;
    };
    lowStockItems: InventoryItem[];
    recentTransactions: InventoryLedger[];
    categories: InventoryCategory[];
}

export default function InventoryDashboard({ stats, lowStockItems, recentTransactions, categories }: DashboardProps) {
    const [stockInOpen, setStockInOpen] = useState(false);
    const [stockOutOpen, setStockOutOpen] = useState(false);

    const stockInForm = useForm({ item_id: '', quantity: '', supplier: '', notes: '' });
    const stockOutForm = useForm({ item_id: '', quantity: '', issued_to_type: 'teacher', purpose: '', notes: '' });

    const handleStockIn = (e: React.FormEvent) => {
        e.preventDefault();
        stockInForm.post(route('inventory.stock-in'), {
            onSuccess: () => { setStockInOpen(false); stockInForm.reset(); },
        });
    };

    const handleStockOut = (e: React.FormEvent) => {
        e.preventDefault();
        stockOutForm.post(route('inventory.stock-out'), {
            onSuccess: () => { setStockOutOpen(false); stockOutForm.reset(); },
        });
    };

    return (
        <AppLayout title="Inventory">
            <Head title="Inventory" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Inventory Management</h1>
                    <p className="page-subtitle">Track stock, issues, and procurement</p>
                </div>
                <div className="flex gap-2">
                    <button onClick={() => setStockInOpen(true)} className="btn-success">
                        <ArrowDownIcon className="w-4 h-4" /> Stock In
                    </button>
                    <button onClick={() => setStockOutOpen(true)} className="btn-warning">
                        <ArrowUpIcon className="w-4 h-4" /> Issue Out
                    </button>
                    <Link href={route('inventory.items')} className="btn-secondary">
                        <CubeIcon className="w-4 h-4" /> Items
                    </Link>
                </div>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <StatCard label="Total Items" value={stats.total_items} icon={CubeIcon}
                          iconBg="bg-blue-50" iconColor="text-blue-600" />
                <StatCard label="Low Stock" value={stats.low_stock} icon={ExclamationTriangleIcon}
                          iconBg="bg-red-50" iconColor="text-red-600" />
                <StatCard label="Categories" value={stats.categories} icon={CubeIcon}
                          iconBg="bg-purple-50" iconColor="text-purple-600" />
                <StatCard label="Issued Today" value={stats.issued_today} icon={ArrowUpIcon}
                          iconBg="bg-amber-50" iconColor="text-amber-600" />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {/* Low Stock */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Low Stock Alert</p>
                        <span className="badge-red badge">{stats.low_stock} items</span>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {lowStockItems.length === 0 ? (
                            <div className="card-body empty-state">
                                <p className="empty-state-text text-emerald-600">All items are well stocked</p>
                            </div>
                        ) : lowStockItems.map(item => (
                            <div key={item.id} className="flex items-center justify-between px-5 py-3.5">
                                <div>
                                    <p className="font-medium text-gray-900">{item.name}</p>
                                    <p className="text-xs text-gray-400">{item.category?.name} · Min: {item.min_stock_level} {item.unit}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-lg font-bold text-red-600">{item.current_stock}</p>
                                    <p className="text-xs text-gray-400">{item.unit} remaining</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Recent Transactions */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Recent Transactions</p>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {recentTransactions.length === 0 ? (
                            <div className="card-body empty-state">
                                <CubeIcon className="empty-state-icon" />
                                <p className="empty-state-text">No transactions yet</p>
                            </div>
                        ) : recentTransactions.map(txn => (
                            <div key={txn.id} className="flex items-center gap-3 px-5 py-3.5">
                                <div className={`w-8 h-8 rounded-xl flex items-center justify-center ${
                                    txn.transaction_type === 'in' ? 'bg-emerald-100' : 'bg-red-100'
                                }`}>
                                    {txn.transaction_type === 'in'
                                        ? <ArrowDownIcon className="w-4 h-4 text-emerald-600" />
                                        : <ArrowUpIcon className="w-4 h-4 text-red-600" />
                                    }
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-gray-900">{txn.item?.name}</p>
                                    <p className="text-xs text-gray-400">{txn.notes ?? '—'}</p>
                                </div>
                                <div className="text-right">
                                    <p className={`font-bold text-sm ${txn.transaction_type === 'in' ? 'text-emerald-600' : 'text-red-600'}`}>
                                        {txn.transaction_type === 'in' ? '+' : '-'}{txn.quantity}
                                    </p>
                                    <p className="text-xs text-gray-400">bal: {txn.balance_after}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>

            {/* Stock In Modal */}
            <Modal isOpen={stockInOpen} onClose={() => setStockInOpen(false)} title="Add Stock" size="md">
                <form onSubmit={handleStockIn} className="space-y-4">
                    <div className="form-group">
                        <label className="form-label">Item <span className="text-red-500">*</span></label>
                        <select className="form-select" value={stockInForm.data.item_id}
                                onChange={e => stockInForm.setData('item_id', e.target.value)} required>
                            <option value="">Select item…</option>
                            {categories.map(cat => (
                                <optgroup key={cat.id} label={cat.name}>
                                    {/* Items would be loaded from separate prop in real impl */}
                                </optgroup>
                            ))}
                        </select>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="form-group">
                            <label className="form-label">Quantity <span className="text-red-500">*</span></label>
                            <input type="number" min="1" className="form-input"
                                   value={stockInForm.data.quantity}
                                   onChange={e => stockInForm.setData('quantity', e.target.value)} required />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Supplier</label>
                            <input className="form-input" value={stockInForm.data.supplier}
                                   onChange={e => stockInForm.setData('supplier', e.target.value)} />
                        </div>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Notes</label>
                        <input className="form-input" value={stockInForm.data.notes}
                               onChange={e => stockInForm.setData('notes', e.target.value)} />
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => setStockInOpen(false)} className="btn-secondary flex-1">Cancel</button>
                        <button type="submit" disabled={stockInForm.processing} className="btn-success flex-1">
                            Add Stock
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Stock Out Modal */}
            <Modal isOpen={stockOutOpen} onClose={() => setStockOutOpen(false)} title="Issue Stock" size="md">
                <form onSubmit={handleStockOut} className="space-y-4">
                    <div className="form-group">
                        <label className="form-label">Item <span className="text-red-500">*</span></label>
                        <select className="form-select" value={stockOutForm.data.item_id}
                                onChange={e => stockOutForm.setData('item_id', e.target.value)} required>
                            <option value="">Select item…</option>
                        </select>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="form-group">
                            <label className="form-label">Quantity <span className="text-red-500">*</span></label>
                            <input type="number" min="1" className="form-input"
                                   value={stockOutForm.data.quantity}
                                   onChange={e => stockOutForm.setData('quantity', e.target.value)} required />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Issue To</label>
                            <select className="form-select" value={stockOutForm.data.issued_to_type}
                                    onChange={e => stockOutForm.setData('issued_to_type', e.target.value)}>
                                <option value="teacher">Teacher</option>
                                <option value="class">Class</option>
                                <option value="department">Department</option>
                            </select>
                        </div>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Purpose <span className="text-red-500">*</span></label>
                        <input className="form-input" value={stockOutForm.data.purpose}
                               onChange={e => stockOutForm.setData('purpose', e.target.value)} required />
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => setStockOutOpen(false)} className="btn-secondary flex-1">Cancel</button>
                        <button type="submit" disabled={stockOutForm.processing} className="btn-warning flex-1">
                            Issue Stock
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
