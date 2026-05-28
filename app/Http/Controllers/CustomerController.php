<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    private function isSuperAdmin(): bool
    {
        return Auth::user()->is_super_admin;
    }

    private function companyId(): ?int
    {
        return Auth::user()->company_id;
    }

    public function index(Request $request)
    {
        $query = Customer::with('company');

        if (!$this->isSuperAdmin()) {
            $query->where('company_id', $this->companyId());
        } elseif ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$search%")->orWhere('phone', 'like', "%$search%"));
        }

        $customers = $query->latest()->paginate(20)->withQueryString();
        $companies = $this->isSuperAdmin() ? Company::where('is_active', true)->orderBy('name')->get() : collect();

        return view('customers.index', compact('customers', 'companies'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ];

        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $data = $request->validate($rules);
        $data['company_id'] = $this->isSuperAdmin() ? $data['company_id'] : $this->companyId();

        Customer::create($data);
        return back()->with('success', 'Müşteri eklendi.');
    }

    public function update(Request $request, Customer $customer)
    {
        if (!$this->isSuperAdmin()) {
            abort_if($customer->company_id !== $this->companyId(), 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update($data);
        return back()->with('success', 'Müşteri güncellendi.');
    }

    public function destroy(Customer $customer)
    {
        if (!$this->isSuperAdmin()) {
            abort_if($customer->company_id !== $this->companyId(), 403);
        }
        $customer->delete();
        return back()->with('success', 'Müşteri silindi.');
    }
}
