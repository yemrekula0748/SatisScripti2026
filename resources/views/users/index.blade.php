@extends('layouts.app')
@section('title', 'Kullanıcılar')
@section('page-title', 'Kullanıcılar')

@section('content')
<div x-data="{ showAdd: false, editUser: null }">
    <div class="flex items-center justify-between mb-5">
        <p class="text-sm text-slate-500">
            @if(auth()->user()->is_super_admin)
                Tüm şirketlerin kullanıcıları
            @else
                Şirketinize bağlı kullanıcılar
            @endif
        </p>
        @can('users.create')
        <button @click="showAdd = true" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Kullanıcı Ekle
        </button>
        @endcan
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Ad</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">E-posta</th>
                    @if(auth()->user()->is_super_admin)
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Şirket</th>
                    @endif
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">Rol</th>
                    <th class="text-left px-5 py-3 text-slate-500 font-medium">İzinler</th>
                    <th class="text-center px-5 py-3 text-slate-500 font-medium">Durum</th>
                    <th class="text-center px-5 py-3 text-slate-500 font-medium">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($users as $user)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <span class="font-medium text-slate-800">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-500">{{ $user->email }}</td>
                    @if(auth()->user()->is_super_admin)
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-medium">
                            {{ $user->company->name ?? '—' }}
                        </span>
                    </td>
                    @endif
                    <td class="px-5 py-3">
                        @foreach($user->roles as $role)
                        <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex flex-wrap gap-1 max-w-xs">
                            @foreach($user->permissions->take(4) as $perm)
                            <span class="px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded text-xs">{{ $perm->name }}</span>
                            @endforeach
                            @if($user->permissions->count() > 4)
                            <span class="text-xs text-slate-400">+{{ $user->permissions->count() - 4 }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($user->is_active)
                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">Aktif</span>
                        @else
                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">Pasif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            @can('users.edit')
                            <button @click="editUser = {{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active, 'role' => $user->roles->first()?->name, 'permissions' => $user->permissions->pluck('name'), 'company_id' => $user->company_id]) }}"
                                class="w-7 h-7 flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcan
                            @can('users.delete')
                            @if($user->id !== auth()->id())
                            <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Bu kullanıcıyı silmek istiyor musunuz?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-7 h-7 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-100 rounded-lg text-xs">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-slate-400">Henüz kullanıcı yok</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showAdd = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-screen overflow-y-auto" @click.stop>
            <div class="p-5 border-b flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="font-bold text-slate-800">Kullanıcı Ekle</h3>
                <button @click="showAdd = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('users.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    @if(auth()->user()->is_super_admin)
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-indigo-600 mb-1">Şirket *</label>
                        <select name="company_id" required class="w-full border-2 border-indigo-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="">-- Şirket Seçin --</option>
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Ad Soyad *</label>
                        <input type="text" name="name" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">E-posta *</label>
                        <input type="email" name="email" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Şifre *</label>
                        <input type="password" name="password" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Rol *</label>
                        <select name="role" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-2">İzinler</label>
                    <div class="grid grid-cols-2 gap-1.5 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3">
                        @foreach($permissions as $perm)
                        <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 rounded p-1">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="w-3.5 h-3.5">
                            <span class="text-xs text-slate-600">{{ $perm->name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showAdd = false" class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl text-sm">İptal</button>
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold">Ekle</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="editUser" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="editUser = null">
        <div x-show="editUser" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-screen overflow-y-auto" @click.stop>
            <div class="p-5 border-b flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="font-bold text-slate-800">Kullanıcı Düzenle</h3>
                <button @click="editUser = null" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <template x-if="editUser">
                <form :action="`/users/${editUser.id}`" method="POST" class="p-5 space-y-4">
                    @csrf @method('PUT')
                    @if(auth()->user()->is_super_admin)
                    <div>
                        <label class="block text-xs font-semibold text-indigo-600 mb-1">Şirket *</label>
                        <select name="company_id" required class="w-full border-2 border-indigo-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="">-- Şirket Seçin --</option>
                            @foreach($companies as $company)
                            <option :value="'{{ $company->id }}'" :selected="editUser.company_id == {{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Ad Soyad *</label>
                            <input type="text" name="name" :value="editUser.name" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">E-posta *</label>
                            <input type="email" name="email" :value="editUser.email" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Yeni Şifre (boş = değişmez)</label>
                            <input type="password" name="password" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Rol *</label>
                            <select name="role" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                                @foreach($roles as $role)
                                <option :value="'{{ $role->name }}'" :selected="editUser.role === '{{ $role->name }}'">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2 flex items-center gap-2">
                            <input type="checkbox" name="is_active" value="1" :checked="editUser.is_active" class="w-4 h-4">
                            <label class="text-sm text-slate-600">Aktif</label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-2">İzinler</label>
                        <div class="grid grid-cols-2 gap-1.5 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3">
                            @foreach($permissions as $perm)
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 rounded p-1">
                                <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                                    :checked="editUser.permissions && editUser.permissions.includes('{{ $perm->name }}')" class="w-3.5 h-3.5">
                                <span class="text-xs text-slate-600">{{ $perm->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="editUser = null" class="flex-1 border border-slate-200 text-slate-600 py-2.5 rounded-xl text-sm">İptal</button>
                        <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold">Kaydet</button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection
