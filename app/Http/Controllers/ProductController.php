<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
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
        $query = Product::with('company');

        if (!$this->isSuperAdmin()) {
            $query->where('company_id', $this->companyId());
        } elseif ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$search%")->orWhere('barcode', 'like', "%$search%"));
        }

        $products = $query->latest()->paginate(20)->withQueryString();
        $companies = $this->isSuperAdmin() ? Company::where('is_active', true)->orderBy('name')->get() : collect();

        return view('products.index', compact('products', 'companies'));
    }

    public function store(Request $request)
    {
        $rules = [
            'barcode' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'sale_price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
        ];

        if ($this->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $data = $request->validate($rules);
        $data['company_id'] = $this->isSuperAdmin() ? $data['company_id'] : $this->companyId();

        Product::create($data);
        return back()->with('success', 'Ürün başarıyla eklendi.');
    }

    public function update(Request $request, Product $product)
    {
        if (!$this->isSuperAdmin()) {
            abort_if($product->company_id !== $this->companyId(), 403);
        }

        $data = $request->validate([
            'barcode' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'sale_price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $product->update($data);
        return back()->with('success', 'Ürün güncellendi.');
    }

    public function destroy(Product $product)
    {
        if (!$this->isSuperAdmin()) {
            abort_if($product->company_id !== $this->companyId(), 403);
        }
        $product->delete();
        return back()->with('success', 'Ürün silindi.');
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $companyId = $this->companyId();

        $products = Product::when(!$this->isSuperAdmin(), fn($query) => $query->where('company_id', $companyId))
            ->where('is_active', true)
            ->where(fn($query) => $query->where('name', 'like', "%$q%")->orWhere('barcode', 'like', "%$q%"))
            ->limit(10)
            ->get(['id', 'name', 'barcode', 'sale_price', 'stock', 'unit']);

        return response()->json($products);
    }
}
