@extends('layouts.app')
@section('title', 'Ürünler')
@section('page-title', 'Ürünler')

@section('content')
<div x-data="{ showAdd: false, editProduct: null }">
    {{-- Toolbar --}}
    <div class="flex items-center justify-between mb-5">
        <form class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Ürün adı veya barkod..."
                class="border border-slate-200 rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300 w-64">
            <button type="submit" class="bg-slate-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-slate-600">
                <i class="fas fa-search"></i>
            </button>
        </form>

        @can('products.create')
        <button @click="showAdd = true"
            class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-all">
            <i class="fas fa-plus"></i> Ürün Ekle
        </button>
        @endcan
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Barkod</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Ürün Adı</th>
                    <th class="text-right px-5 py-3 text-slate-500 font-medium">Satış Fiyatı</th>
                    <th class="text-right px-5 py-3 text-slate-500 font-medium">Stok</th>
                    <th class="text-center px-5 py-3 text-slate-500 font-medium">Birim</th>
                    <th class="text-center px-5 py-3 text-slate-500 font-medium">Durum</th>
                    <th class="text-center px-5 py-3 text-slate-500 font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($products as $product)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ $product->barcode ?: '—' }}</td>
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $product->name }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-indigo-700">{{ number_format($product->sale_price, 2, ',', '.') }} ₺</td>
                    <td class="px-5 py-3 text-right {{ $product->stock <= 5 ? 'text-red-600 font-semibold' : 'text-slate-700' }}">
                        {{ number_format($product->stock, 0, ',', '.') }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs">{{ $product->unit }}</span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($product->is_active)
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-medium">Aktif</span>
                        @else
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-medium">Pasif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            @can('products.edit')
                            <button @click="editProduct = {{ $product->toJson() }}"
                                class="w-7 h-7 flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-all text-xs">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcan
                            @can('products.delete')
                            <form action="{{ route('products.destroy', $product) }}" method="POST"
                                onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-7 h-7 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-all text-xs">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Henüz ürün yok</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-100">{{ $products->links() }}</div>
    </div>

    {{-- Add Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showAdd = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Ürün Ekle</h3>
                <button @click="showAdd = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('products.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Ürün Adı *</label>
                        <input type="text" name="name" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Barkod</label>
                        <input type="text" name="barcode" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Satış Fiyatı (₺) *</label>
                        <input type="number" name="sale_price" step="0.01" min="0" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Stok *</label>
                        <input type="number" name="stock" step="0.001" min="0" value="0" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Birim *</label>
                        <select name="unit" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="adet">Adet</option>
                            <option value="kg">Kg</option>
                            <option value="lt">Litre</option>
                            <option value="m">Metre</option>
                            <option value="paket">Paket</option>
                            <option value="kutu">Kutu</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showAdd = false" class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl text-sm hover:bg-slate-50">İptal</button>
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-500">Ekle</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="editProduct" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="editProduct = null">
        <div x-show="editProduct" class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Ürün Düzenle</h3>
                <button @click="editProduct = null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <template x-if="editProduct">
                <form :action="`/products/${editProduct.id}`" method="POST" class="p-5 space-y-4">
                    @csrf @method('PUT')
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Ürün Adı *</label>
                            <input type="text" name="name" :value="editProduct.name" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Barkod</label>
                            <input type="text" name="barcode" :value="editProduct.barcode" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Satış Fiyatı (₺) *</label>
                            <input type="number" name="sale_price" step="0.01" :value="editProduct.sale_price" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Stok *</label>
                            <input type="number" name="stock" step="0.001" :value="editProduct.stock" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Birim *</label>
                            <select name="unit" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                                <template x-for="u in ['adet','kg','lt','m','paket','kutu']" :key="u">
                                    <option :value="u" :selected="editProduct.unit === u" x-text="u.charAt(0).toUpperCase() + u.slice(1)"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-span-2 flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" :checked="editProduct.is_active" id="is_active_edit" class="w-4 h-4">
                            <label for="is_active_edit" class="text-sm text-slate-600">Aktif</label>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="editProduct = null" class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl text-sm hover:bg-slate-50">İptal</button>
                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-500">Kaydet</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
