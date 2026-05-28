<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::withCount('users', 'products');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $companies = $query->latest()->paginate(20)->withQueryString();
        return view('companies.index', compact('companies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:50',
        ]);

        Company::create($data);
        return back()->with('success', 'Şirket eklendi.');
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $company->update($data);
        return back()->with('success', 'Şirket güncellendi.');
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return back()->with('success', 'Şirket silindi.');
    }
}
