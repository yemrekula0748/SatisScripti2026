@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Bugünkü Satış</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $todaySales }}</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-cart text-indigo-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Bugünkü Ciro</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($todayRevenue, 2, ',', '.') }} ₺</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-lira-sign text-green-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Ürün Sayısı</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ $productCount }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-box text-orange-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Aylık Ciro</p>
                <p class="text-2xl font-bold text-slate-800 mt-1">{{ number_format($monthRevenue, 2, ',', '.') }} ₺</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800">Son Satışlar</h2>
        @can('sales.view')
        <a href="{{ route('sales.pos') }}" class="text-sm text-indigo-600 hover:underline">Yeni Satış →</a>
        @endcan
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">#</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Müşteri</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Kasiyer</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Ödeme</th>
                    <th class="text-right px-5 py-3 text-slate-500 font-medium">Tutar</th>
                    <th class="text-right px-5 py-3 text-slate-500 font-medium">Tarih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($recentSales as $sale)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-slate-400">#{{ $sale->id }}</td>
                    <td class="px-5 py-3 text-slate-700">{{ $sale->customer_name ?? 'Misafir' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $sale->user->name }}</td>
                    <td class="px-5 py-3">
                        @foreach($sale->payments as $p)
                        <span class="inline-block px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-xs mr-1">{{ $p->payment_type }}</span>
                        @endforeach
                    </td>
                    <td class="px-5 py-3 text-right font-semibold text-slate-800">{{ number_format($sale->total, 2, ',', '.') }} ₺</td>
                    <td class="px-5 py-3 text-right text-slate-400">{{ $sale->created_at->format('d.m H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Henüz satış yok</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
