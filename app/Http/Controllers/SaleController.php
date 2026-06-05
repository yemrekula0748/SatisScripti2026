<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    private function companyId(): int
    {
        // Super admin için ilk aktif şirketi kullan (POS genellikle şirket kullanıcısı kullanır)
        $user = Auth::user();
        if ($user->company_id) {
            return $user->company_id;
        }
        // Super admin ise session'dan şirket seç, yoksa ilk şirketi al
        return session('pos_company_id', \App\Models\Company::first()?->id ?? 1);
    }

    public function pos()
    {
        $companyId = $this->companyId();
        $company = \App\Models\Company::find($companyId);
        $customers = Customer::where('company_id', $companyId)->orderBy('name')->get();
        $rates = CurrencyRate::where('company_id', $companyId)->get()->keyBy('currency');

        return view('sales.pos', compact('customers', 'rates', 'company'));
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

    public function history(Request $request)
    {
        $query = Sale::with(['items.returnItems', 'payments', 'user', 'returns.items', 'returns.user'])
            ->where('company_id', $this->companyId());

        if ($request->filled('search')) {
            $query->where('customer_name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sales = $query->latest()->paginate(30)->withQueryString();
        return view('sales.history', compact('sales'));
    }

    public function refund(Request $request, Sale $sale)
    {
        abort_if($sale->company_id !== $this->companyId(), 404);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
        ]);

        $requestedItems = collect($data['items'])
            ->map(function ($item) {
                return [
                    'sale_item_id' => (int) $item['sale_item_id'],
                    'quantity' => round((float) $item['quantity'], 3),
                ];
            })
            ->filter(fn($item) => $item['quantity'] > 0)
            ->values();

        if ($requestedItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'İade için en az bir ürün ve miktar seçin.',
            ], 422);
        }

        if ($requestedItems->pluck('sale_item_id')->duplicates()->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aynı ürün birden fazla kez gönderildi. Lütfen tekrar deneyin.',
            ], 422);
        }

        $sale->loadMissing(['items.returnItems']);
        $saleItems = $sale->items->keyBy('id');
        $refundRows = collect();
        $subtotal = 0;

        foreach ($requestedItems as $requestedItem) {
            /** @var \App\Models\SaleItem|null $saleItem */
            $saleItem = $saleItems->get($requestedItem['sale_item_id']);

            if (!$saleItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Satış kalemlerinden biri bulunamadı.',
                ], 422);
            }

            $alreadyRefundedQuantity = round((float) $saleItem->returnItems->sum('quantity'), 3);
            $originalQuantity = round((float) $saleItem->quantity, 3);
            $maxRefundableQuantity = round(max($originalQuantity - $alreadyRefundedQuantity, 0), 3);

            if ($requestedItem['quantity'] - $maxRefundableQuantity > 0.0001) {
                return response()->json([
                    'success' => false,
                    'message' => "{$saleItem->product_name} için iade miktarı satıştaki kalan adedi aşıyor.",
                ], 422);
            }

            $lineSubtotal = round((float) $saleItem->unit_price * $requestedItem['quantity'], 2);
            $subtotal += $lineSubtotal;

            $refundRows->push([
                'sale_item' => $saleItem,
                'quantity' => $requestedItem['quantity'],
                'line_subtotal' => $lineSubtotal,
            ]);
        }

        $discountPercent = (float) $sale->discount_percent;
        $discountAmount = round($subtotal * ($discountPercent / 100), 2);
        $total = round($subtotal - $discountAmount, 2);

        DB::transaction(function () use ($sale, $refundRows, $subtotal, $discountPercent, $discountAmount, $total) {
            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'company_id' => $sale->company_id,
                'user_id' => Auth::id(),
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total' => $total,
            ]);

            foreach ($refundRows as $refundRow) {
                /** @var \App\Models\SaleItem $saleItem */
                $saleItem = $refundRow['sale_item'];

                $saleReturn->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'product_name' => $saleItem->product_name,
                    'product_barcode' => $saleItem->product_barcode,
                    'unit' => $saleItem->unit,
                    'unit_price' => $saleItem->unit_price,
                    'quantity' => $refundRow['quantity'],
                    'total' => $refundRow['line_subtotal'],
                ]);

                if ($saleItem->product_id) {
                    Product::where('company_id', $sale->company_id)
                        ->where('id', $saleItem->product_id)
                        ->increment('stock', $refundRow['quantity']);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'İade başarıyla kaydedildi.',
            'refund_total' => $total,
        ]);
    }

    public function saveCurrencyRates(Request $request)
    {
        $data = $request->validate([
            'rates' => 'required|array',
            'rates.EUR' => 'nullable|numeric|min:0',
            'rates.USD' => 'nullable|numeric|min:0',
            'rates.GBP' => 'nullable|numeric|min:0',
            'rates.RUB' => 'nullable|numeric|min:0',
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
