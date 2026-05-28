<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    private function companyId()
    {
        return Auth::user()->company_id;
    }

    public function pos()
    {
        $customers = Customer::where('company_id', $this->companyId())->orderBy('name')->get();
        $rates = CurrencyRate::where('company_id', $this->companyId())->get()->keyBy('currency');

        return view('sales.pos', compact('customers', 'rates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|integer',
            'customer_name' => 'nullable|string',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer',
            'items.*.product_name' => 'required|string',
            'items.*.product_barcode' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string',
            'payments' => 'required|array|min:1',
            'payments.*.payment_type' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.exchange_rate' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $companyId = $this->companyId();

            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $discountPercent = $data['discount_percent'] ?? 0;
            $discountAmount = $subtotal * ($discountPercent / 100);
            $total = $subtotal - $discountAmount;

            $customerName = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::where('company_id', $companyId)->find($data['customer_id']);
                $customerName = $customer?->name;
            } elseif (!empty($data['customer_name'])) {
                $customerName = $data['customer_name'];
            }

            $sale = Sale::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $customerName ?? 'Misafir Müşteri',
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total' => $total,
            ]);

            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'product_barcode' => $item['product_barcode'] ?? null,
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['unit_price'] * $item['quantity'],
                ]);

                if (!empty($item['product_id'])) {
                    Product::where('id', $item['product_id'])->where('company_id', $companyId)
                        ->decrement('stock', $item['quantity']);
                }
            }

            foreach ($data['payments'] as $payment) {
                $rate = $payment['exchange_rate'] ?? 1;
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_type' => $payment['payment_type'],
                    'amount' => $payment['amount'],
                    'exchange_rate' => $rate,
                    'amount_in_tl' => $payment['amount'] * $rate,
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Satış tamamlandı.']);
    }

    public function saveCurrencyRates(Request $request)
    {
        $data = $request->validate([
            'rates' => 'required|array',
            'rates.EUR' => 'required|numeric|min:0',
            'rates.USD' => 'required|numeric|min:0',
            'rates.GBP' => 'required|numeric|min:0',
            'rates.RUB' => 'required|numeric|min:0',
        ]);

        foreach ($data['rates'] as $currency => $rate) {
            CurrencyRate::updateOrCreate(
                ['company_id' => $this->companyId(), 'currency' => $currency],
                ['rate' => $rate]
            );
        }

        return response()->json(['success' => true]);
    }

    public function getCurrencyRates()
    {
        $rates = CurrencyRate::where('company_id', $this->companyId())
            ->get()
            ->keyBy('currency')
            ->map(fn($r) => $r->rate);

        return response()->json($rates);
    }
}
