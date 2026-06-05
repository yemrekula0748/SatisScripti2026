<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    private function companyId(): int
    {
        // Super admin için ilk aktif şirketi kullan
        $user = Auth::user();
        if ($user->company_id) {
            return $user->company_id;
        }
        // Super admin ise session'dan şirket seç, yoksa ilk şirketi al
        return session('pos_company_id', Company::first()?->id ?? 1);
    }

    public function index()
    {
        $company = Company::find($this->companyId());
        return view('settings.index', compact('company'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'show_customer_field' => 'boolean',
        ]);

        $company = Company::find($this->companyId());
        $company->update($data);

        return redirect()->route('settings.index')
            ->with('success', 'Ayarlar başarıyla kaydedildi.');
    }
}
