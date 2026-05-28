<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    private function companyId()
    {
        return Auth::user()->company_id;
    }

    public function index(Request $request)
    {
        $query = Customer::where('company_id', $this->companyId());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$search%")->orWhere('phone', 'like', "%$search%"));
        }

        $customers = $query->latest()->paginate(20)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $data['company_id'] = $this->companyId();
        Customer::create($data);

        return back()->with('success', 'Müşteri eklendi.');
    }

    public function update(Request $request, Customer $customer)
    {
        abort_if($customer->company_id !== $this->companyId(), 403);

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
        abort_if($customer->company_id !== $this->companyId(), 403);
        $customer->delete();
        return back()->with('success', 'Müşteri silindi.');
    }
}
