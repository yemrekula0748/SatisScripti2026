<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    private function companyId()
    {
        return Auth::user()->company_id;
    }

    public function index(Request $request)
    {
        $query = Product::where('company_id', $this->companyId());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$search%")->orWhere('barcode', 'like', "%$search%"));
        }

        $products = $query->latest()->paginate(20)->withQueryString();
        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'barcode' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'sale_price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
        ]);

        $data['company_id'] = $this->companyId();
        Product::create($data);

        return back()->with('success', 'Ürün başarıyla eklendi.');
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->company_id !== $this->companyId(), 403);

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
        abort_if($product->company_id !== $this->companyId(), 403);
        $product->delete();
        return back()->with('success', 'Ürün silindi.');
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $companyId = $this->companyId();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(fn($query) => $query->where('name', 'like', "%$q%")->orWhere('barcode', 'like', "%$q%"))
            ->limit(10)
            ->get(['id', 'name', 'barcode', 'sale_price', 'stock', 'unit']);

        return response()->json($products);
    }
}
