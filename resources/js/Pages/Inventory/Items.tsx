import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { PlusIcon, FunnelIcon, ExclamationTriangleIcon, ArrowUpIcon, ArrowDownIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import SearchInput from '@/Components/SearchInput';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';
import type { PageProps, InventoryItem, InventoryCategory, PaginatedData } from '@/types';

interface Props extends PageProps {
    items: PaginatedData<InventoryItem>;
    categories: InventoryCategory[];
    filters: { search?: string; category_id?: string; low_stock?: string };
}

export default function InventoryItems({ items, categories, filters }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [stockInItem, setStockInItem]   = useState<InventoryItem | null>(null);
    const [stockOutItem, setStockOutItem] = useState<InventoryItem | null>(null);

    const createForm = useForm({
        category_id: '',
        name: '',
        description: '',
        unit: '',
        current_stock: '0',
        min_stock_level: '5',
    });

    const stockInForm = useForm({
        item_id: '',
        quantity: '',
        supplier: '',
        unit_price: '',
        notes: '',
    });

    const stockOutForm = useForm({
        item_id: '',
        quantity: '',
        issued_to_type: '',
        purpose: '',
        notes: '',
    });

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('inventory.items.store'), {
            onSuccess: () => { setCreateOpen(false); createForm.reset(); },
        });
    };

    const submitStockIn = (e: React.FormEvent) => {
        e.preventDefault();
        stockInForm.post(route('inventory.stock-in'), {
            onSuccess: () => { setStockInItem(null); stockInForm.reset(); },
        });
    };

    const submitStockOut = (e: React.FormEvent) => {
        e.preventDefault();
        stockOutForm.post(route('inventory.stock-out'), {
            onSuccess: () => { setStockOutItem(null); stockOutForm.reset(); },
        });
    };

    const openStockIn = (item: InventoryItem) => {
        stockInForm.setData('item_id', String(item.id));
        setStockInItem(item);
    };

    const openStockOut = (item: InventoryItem) => {
        stockOutForm.setData('item_id', String(item.id));
        setStockOutItem(item);
    };

    return (
        <AppLayout title="Inventory Items">
            <Head title="Inventory Items" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Inventory Items</h1>
                    <p className="page-subtitle">{items.total} items</p>
                </div>
                <button onClick={() => setCreateOpen(true)} className="btn-primary">
                    <PlusIcon className="w-4 h-4" /> Add Item
                </button>
            </div>

            <div className="card">
                <div className="card-header gap-3 flex-wrap">
                    <SearchInput value={filters.search} placeholder="Search item name…" />
                    <div className="flex items-center gap-2">
                        <FunnelIcon className="w-4 h-4 text-gray-400" />
                        <select className="form-select !py-2 !text-xs w-44"
                                onChange={e => {
                                    const url = new URL(window.location.href);
                                    url.searchParams.set('category_id', e.target.value);
                                    window.location.href = url.toString();
                                }}
                                defaultValue={filters.category_id ?? ''}>
                            <option value="">All Categories</option>
                            {categories.map(c => (
                                <option key={c.id} value={c.id}>{c.class}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Current Stock</th>
                                <th>Min Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="text-center py-12 text-gray-400">No items found</td>
                                </tr>
                            ) : items.data.map(item => {
                                const isLow = item.current_stock <= item.min_stock_level;
                                return (
                                    <tr key={item.id}>
                                        <td>
                                            <div>
                                                <p className="font-semibold text-gray-900">{item.name}</p>
                                                {item.description && (
                                                    <p className="text-xs text-gray-400 truncate max-w-[200px]">{item.description}</p>
                                                )}
                                            </div>
                                        </td>
                                        <td>{item.category?.name}</td>
                                        <td>{item.unit}</td>
                                        <td>
                                            <div className="flex items-center gap-1.5">
                                                {isLow && <ExclamationTriangleIcon className="w-4 h-4 text-red-500" />}
                                                <span className={`font-semibold ${isLow ? 'text-red-600' : 'text-gray-900'}`}>
                                                    {item.current_stock}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="text-gray-500">{item.min_stock_level}</td>
                                        <td>
                                            <Badge color={isLow ? 'red' : 'green'}>
                                                {isLow ? 'Low Stock' : 'In Stock'}
                                            </Badge>
                                        </td>
                                        <td>
                                            <div className="flex gap-1">
                                                <button onClick={() => openStockIn(item)}
                                                        className="btn-ghost btn-sm text-emerald-600">
                                                    <ArrowUpIcon className="w-3.5 h-3.5" /> In
                                                </button>
                                                <button onClick={() => openStockOut(item)}
                                                        className="btn-ghost btn-sm text-red-600">
                                                    <ArrowDownIcon className="w-3.5 h-3.5" /> Out
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <p className="text-sm text-gray-500">Showing {items.from ?? 0}–{items.to ?? 0} of {items.total}</p>
                    <Pagination data={items.links} />
                </div>
            </div>

            {/* Create Item Modal */}
            <Modal isOpen={createOpen} onClose={() => setCreateOpen(false)} title="Add Inventory Item" size="md">
                <form onSubmit={submitCreate} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="form-group col-span-2">
                            <label className="form-label">Item Name <span className="text-red-500">*</span></label>
                            <input className="form-input" value={createForm.data.name}
                                   onChange={e => createForm.setData('name', e.target.value)}
                                   placeholder="e.g. A4 Paper Ream" required />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Category <span className="text-red-500">*</span></label>
                            <select className="form-select" value={createForm.data.category_id}
                                    onChange={e => createForm.setData('category_id', e.target.value)} required>
                                <option value="">Select…</option>
                                {categories.map(c => <option key={c.id} value={c.id}>{c.class}</option>)}
                            </select>
                        </div>
                        <div className="form-group">
                            <label className="form-label">Unit <span className="text-red-500">*</span></label>
                            <input className="form-input" value={createForm.data.unit}
                                   onChange={e => createForm.setData('unit', e.target.value)}
                                   placeholder="e.g. Ream, Box, Piece" required />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Initial Stock</label>
                            <input type="number" min="0" className="form-input" value={createForm.data.current_stock}
                                   onChange={e => createForm.setData('current_stock', e.target.value)} />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Min Stock Level</label>
                            <input type="number" min="0" className="form-input" value={createForm.data.min_stock_level}
                                   onChange={e => createForm.setData('min_stock_level', e.target.value)} />
                        </div>
                        <div className="form-group col-span-2">
                            <label className="form-label">Description</label>
                            <textarea className="form-textarea" rows={2} value={createForm.data.description}
                                      onChange={e => createForm.setData('description', e.target.value)} />
                        </div>
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                        <button type="button" onClick={() => setCreateOpen(false)} className="btn-secondary">Cancel</button>
                        <button type="submit" disabled={createForm.processing} className="btn-primary">
                            {createForm.processing ? 'Saving…' : 'Add Item'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Stock In Modal */}
            <Modal isOpen={!!stockInItem} onClose={() => setStockInItem(null)} title={`Stock In — ${stockInItem?.name}`} size="sm">
                <form onSubmit={submitStockIn} className="space-y-4">
                    <div className="form-group">
                        <label className="form-label">Quantity <span className="text-red-500">*</span></label>
                        <input type="number" min="1" className="form-input" value={stockInForm.data.quantity}
                               onChange={e => stockInForm.setData('quantity', e.target.value)} required />
                        {stockInForm.errors.quantity && <p className="form-error">{stockInForm.errors.quantity}</p>}
                    </div>
                    <div className="form-group">
                        <label className="form-label">Supplier</label>
                        <input className="form-input" value={stockInForm.data.supplier}
                               onChange={e => stockInForm.setData('supplier', e.target.value)}
                               placeholder="Supplier name" />
                    </div>
                    <div className="form-group">
                        <label className="form-label">Unit Price (£)</label>
                        <input type="number" step="0.01" min="0" className="form-input" value={stockInForm.data.unit_price}
                               onChange={e => stockInForm.setData('unit_price', e.target.value)} />
                    </div>
                    <div className="form-group">
                        <label className="form-label">Notes</label>
                        <textarea className="form-textarea" rows={2} value={stockInForm.data.notes}
                                  onChange={e => stockInForm.setData('notes', e.target.value)} />
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                        <button type="button" onClick={() => setStockInItem(null)} className="btn-secondary">Cancel</button>
                        <button type="submit" disabled={stockInForm.processing} className="btn-success">
                            <ArrowUpIcon className="w-4 h-4" />
                            {stockInForm.processing ? 'Saving…' : 'Add Stock'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Stock Out Modal */}
            <Modal isOpen={!!stockOutItem} onClose={() => setStockOutItem(null)} title={`Issue Stock — ${stockOutItem?.name}`} size="sm">
                <form onSubmit={submitStockOut} className="space-y-4">
                    <div className="form-group">
                        <label className="form-label">Quantity <span className="text-red-500">*</span></label>
                        <input type="number" min="1" className="form-input" value={stockOutForm.data.quantity}
                               onChange={e => stockOutForm.setData('quantity', e.target.value)} required />
                        {stockOutForm.errors.quantity && <p className="form-error">{stockOutForm.errors.quantity}</p>}
                    </div>
                    <div className="form-group">
                        <label className="form-label">Issued To <span className="text-red-500">*</span></label>
                        <select className="form-select" value={stockOutForm.data.issued_to_type}
                                onChange={e => stockOutForm.setData('issued_to_type', e.target.value)} required>
                            <option value="">Select…</option>
                            <option value="teacher">Teacher</option>
                            <option value="class">Class</option>
                            <option value="department">Department</option>
                        </select>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Purpose <span className="text-red-500">*</span></label>
                        <input className="form-input" value={stockOutForm.data.purpose}
                               onChange={e => stockOutForm.setData('purpose', e.target.value)}
                               placeholder="Reason for issuing" required />
                    </div>
                    <div className="form-group">
                        <label className="form-label">Notes</label>
                        <textarea className="form-textarea" rows={2} value={stockOutForm.data.notes}
                                  onChange={e => stockOutForm.setData('notes', e.target.value)} />
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                        <button type="button" onClick={() => setStockOutItem(null)} className="btn-secondary">Cancel</button>
                        <button type="submit" disabled={stockOutForm.processing} className="btn-danger">
                            <ArrowDownIcon className="w-4 h-4" />
                            {stockOutForm.processing ? 'Saving…' : 'Issue Stock'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
