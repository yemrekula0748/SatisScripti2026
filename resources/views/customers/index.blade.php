@extends('layouts.app')
@section('title', 'Müşteriler')
@section('page-title', 'Müşteriler')

@section('content')
<div x-data="{ showAdd: false, editCustomer: null }">
    <div class="flex items-center justify-between mb-5">
        <form class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Ad veya telefon..."
                class="border border-slate-200 rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300 w-64">
            <button type="submit" class="bg-slate-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-slate-600">
                <i class="fas fa-search"></i>
            </button>
        </form>
        @can('customers.create')
        <button @click="showAdd = true" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Müşteri Ekle
        </button>
        @endcan
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Ad Soyad</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Telefon</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">E-posta</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Adres</th>
                    <th class="text-center px-5 py-3 text-slate-500 font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($customers as $customer)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-800">{{ $customer->name }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $customer->phone ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500">{{ $customer->email ?: '—' }}</td>
                    <td class="px-5 py-3 text-slate-500 max-w-xs truncate">{{ $customer->address ?: '—' }}</td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            @can('customers.edit')
                            <button @click="editCustomer = {{ $customer->toJson() }}"
                                class="w-7 h-7 flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcan
                            @can('customers.delete')
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                                onsubmit="return confirm('Bu müşteriyi silmek istiyor musunuz?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-7 h-7 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-100 rounded-lg text-xs">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Henüz müşteri yok</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-100">{{ $customers->links() }}</div>
    </div>

    {{-- Add Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showAdd = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="p-5 border-b flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Müşteri Ekle</h3>
                <button @click="showAdd = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('customers.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Ad Soyad *</label>
                    <input type="text" name="name" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Telefon</label>
                        <input type="text" name="phone" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">E-posta</label>
                        <input type="email" name="email" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Adres</label>
                    <textarea name="address" rows="2" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showAdd = false" class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl text-sm">İptal</button>
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold">Ekle</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="editCustomer" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="editCustomer = null">
        <div x-show="editCustomer" class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="p-5 border-b flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Müşteri Düzenle</h3>
                <button @click="editCustomer = null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <template x-if="editCustomer">
                <form :action="`/customers/${editCustomer.id}`" method="POST" class="p-5 space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Ad Soyad *</label>
                        <input type="text" name="name" :value="editCustomer.name" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Telefon</label>
                            <input type="text" name="phone" :value="editCustomer.phone" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">E-posta</label>
                            <input type="email" name="email" :value="editCustomer.email" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Adres</label>
                        <textarea name="address" rows="2" x-text="editCustomer.address" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="editCustomer = null" class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl text-sm">İptal</button>
                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold">Kaydet</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
