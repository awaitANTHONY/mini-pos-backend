<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Item;
use App\Models\ItemVariant;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class SaleService
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Create a new sale with items and payments.
     *
     * @param array $data
     * @return Sale
     * @throws Exception
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            // Validate items exist
            if (empty($data['items'])) {
                throw new Exception('Sale must have at least one item.');
            }

            // Calculate ingredient usage and check stock availability
            $ingredientUsage = $this->calculateIngredientUsage($data['items']);
            $this->checkStockAvailability($ingredientUsage);

            // Create the sale
            $sale = new Sale();
            $sale->invoice_no = $data['invoice_no'] ?? $this->generateInvoiceNumber();
            $sale->user_id = Auth::id();
            $sale->total_amount = 0;
            $sale->total_cost = 0;
            $sale->paid_amount = 0;
            $sale->due_amount = 0;
            $sale->payment_status = 'due';
            $sale->note = $data['note'] ?? null;
            $sale->save();

            // Add sale items
            $totalAmount = 0;
            $totalCost = 0;

            foreach ($data['items'] as $itemData) {
                $saleItem = $this->createSaleItem($sale->id, $itemData);
                $totalAmount += $saleItem->total_price;
                $totalCost += $saleItem->total_cost;
            }

            // Update sale totals
            $sale->total_amount = $totalAmount;
            $sale->total_cost = $totalCost;

            // Add payments
            $paidAmount = 0;
            if (!empty($data['payments'])) {
                foreach ($data['payments'] as $paymentData) {
                    $payment = $this->createPayment($sale->id, $paymentData);
                    $paidAmount += $payment->amount;
                }
            }

            $sale->paid_amount = $paidAmount;
            $sale->due_amount = $totalAmount - $paidAmount;
            $sale->payment_status = $this->determinePaymentStatus($totalAmount, $paidAmount);
            $sale->save();

            // Deduct stock for all ingredients
            $this->deductStock($ingredientUsage);

            return $sale->load(['saleItems.item', 'saleItems.variant', 'payments']);
        });
    }

    /**
     * Calculate ingredient usage from sale items.
     *
     * @param array $items
     * @return array
     */
    protected function calculateIngredientUsage(array $items): array
    {
        $usage = [];

        foreach ($items as $itemData) {
            $item = Item::with('ingredient')->findOrFail($itemData['item_id']);

            $ingredientQuantity = null;

            // Check if variant is provided
            if (!empty($itemData['variant_id'])) {
                $variant = ItemVariant::findOrFail($itemData['variant_id']);
                $ingredientQuantity = $variant->ingredient_quantity ?? $item->ingredient_quantity;
            } else {
                $ingredientQuantity = $item->ingredient_quantity;
            }

            // Calculate usage
            if ($item->ingredient_id && $ingredientQuantity > 0) {
                $usedQty = $ingredientQuantity * $itemData['quantity'];

                if (!isset($usage[$item->ingredient_id])) {
                    $usage[$item->ingredient_id] = 0;
                }

                $usage[$item->ingredient_id] += $usedQty;
            }
        }

        return $usage;
    }

    /**
     * Check if sufficient stock is available.
     *
     * @param array $ingredientUsage
     * @return void
     * @throws Exception
     */
    protected function checkStockAvailability(array $ingredientUsage): void
    {
        $allowNegative = config('pos.allow_negative_stock', false);

        if ($allowNegative) {
            return; // Skip check if negative stock is allowed
        }

        foreach ($ingredientUsage as $ingredientId => $requiredQty) {
            $stock = Stock::where('ingredient_id', $ingredientId)->lockForUpdate()->first();

            if (!$stock) {
                throw new Exception("Stock record not found for ingredient ID: {$ingredientId}", 409);
            }

            if ($stock->quantity < $requiredQty) {
                throw new Exception(
                    "Insufficient stock for ingredient ID: {$ingredientId}.  Available: " . number_format($stock->quantity, 0) . ", Required: " . number_format($requiredQty, 0),
                    409
                );
            }
        }
    }

    /**
     * Deduct stock for ingredients.
     *
     * @param array $ingredientUsage
     * @return void
     */
    protected function deductStock(array $ingredientUsage): void
    {
        $allowNegative = config('pos.allow_negative_stock', false);

        foreach ($ingredientUsage as $ingredientId => $quantity) {
            $this->stockService->reduceStock($ingredientId, $quantity, $allowNegative);
        }
    }

    /**
     * Create a sale item.
     *
     * @param int $saleId
     * @param array $data
     * @return SaleItem
     */
    protected function createSaleItem(int $saleId, array $data): SaleItem
    {
        $item = Item::findOrFail($data['item_id']);
        $variant = null;

        // Determine price and cost
        if (!empty($data['variant_id'])) {
            $variant = ItemVariant::findOrFail($data['variant_id']);
            $unitPrice = $variant->price;
            $unitCost = $variant->cost;
        } else {
            $unitPrice = $item->price;
            $unitCost = $item->cost;
        }

        $saleItem = new SaleItem();
        $saleItem->sale_id = $saleId;
        $saleItem->item_id = $data['item_id'];
        $saleItem->variant_id = $data['variant_id'] ?? null;
        $saleItem->quantity = $data['quantity'];
        $saleItem->unit_price = $unitPrice;
        $saleItem->unit_cost = $unitCost;
        $saleItem->total_price = $unitPrice * $data['quantity'];
        $saleItem->total_cost = $unitCost * $data['quantity'];
        $saleItem->save();

        return $saleItem;
    }

    /**
     * Create a payment.
     *
     * @param int $saleId
     * @param array $data
     * @return Payment
     */
    protected function createPayment(int $saleId, array $data): Payment
    {
        $payment = new Payment();
        $payment->sale_id = $saleId;
        $payment->amount = $data['amount'];
        $payment->method = $data['method'];
        $payment->paid_at = $data['paid_at'] ?? now();
        $payment->note = $data['note'] ?? null;
        $payment->created_by = Auth::id();
        $payment->save();

        return $payment;
    }

    /**
     * Generate a unique invoice number.
     *
     * @return string
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = config('pos.invoice_prefix', 'INV-');
        $lastSale = Sale::orderBy('id', 'desc')->first();
        $number = $lastSale ? ($lastSale->id + 1) : 1;

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Determine payment status.
     *
     * @param float $totalAmount
     * @param float $paidAmount
     * @return string
     */
    protected function determinePaymentStatus(float $totalAmount, float $paidAmount): string
    {
        if ($paidAmount >= $totalAmount) {
            return 'paid';
        } elseif ($paidAmount > 0) {
            return 'partial';
        } else {
            return 'due';
        }
    }

    /**
     * Get a sale by ID.
     *
     * @param int $id
     * @return Sale
     */
    public function getSale(int $id): Sale
    {
        return Sale::with(['saleItems.item', 'saleItems.variant', 'payments', 'user'])->findOrFail($id);
    }

    /**
     * Update an existing sale.
     *
     * @param int $id
     * @param array $data
     * @return Sale
     * @throws Exception
     */
    public function updateSale(int $id, array $data): Sale
    {
        return DB::transaction(function () use ($id, $data) {
            $sale = Sale::with(['saleItems'])->lockForUpdate()->findOrFail($id);

            // Validate items exist
            if (empty($data['items'])) {
                throw new Exception('Sale must have at least one item.');
            }

            // Restore stock from previous sale items
            $previousIngredientUsage = $this->calculateIngredientUsage($sale->saleItems->map(function($item) {
                return [
                    'item_id' => $item->item_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                ];
            })->toArray());

            foreach ($previousIngredientUsage as $ingredientId => $quantity) {
                $this->stockService->addStock($ingredientId, $quantity); // Add back to stock
            }

            // Calculate new ingredient usage and check availability
            $ingredientUsage = $this->calculateIngredientUsage($data['items']);
            $this->checkStockAvailability($ingredientUsage);

            // Update sale basic info
            $sale->note = $data['note'] ?? null;

            // Delete old sale items
            SaleItem::where('sale_id', $sale->id)->delete();

            // Add new sale items
            $totalAmount = 0;
            $totalCost = 0;

            foreach ($data['items'] as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);
                $variant = null;

                if (!empty($itemData['variant_id'])) {
                    $variant = ItemVariant::findOrFail($itemData['variant_id']);
                    $price = $variant->price;
                    $cost = $variant->cost;
                } else {
                    $price = $item->price;
                    $cost = $item->cost;
                }

                $quantity = $itemData['quantity'];
                $totalPrice = $price * $quantity;
                $totalCostAmount = $cost * $quantity;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'item_id' => $item->id,
                    'variant_id' => $variant?->id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'unit_cost' => $cost,
                    'total_price' => $totalPrice,
                    'total_cost' => $totalCostAmount,
                ]);

                $totalAmount += $totalPrice;
                $totalCost += $totalCostAmount;
            }

            // Update sale totals
            $sale->total_amount = $totalAmount;
            $sale->total_cost = $totalCost;

            // Handle new payments if provided
            if (!empty($data['payments'])) {
                // Delete old payments
                Payment::where('sale_id', $sale->id)->delete();
                
                // Add new payments
                $paidAmount = 0;
                foreach ($data['payments'] as $paymentData) {
                    $payment = $this->createPayment($sale->id, $paymentData);
                    $paidAmount += $payment->amount;
                }
                
                $sale->paid_amount = $paidAmount;
            }

            $sale->due_amount = $totalAmount - $sale->paid_amount;

            // Update payment status
            if ($sale->paid_amount >= $totalAmount) {
                $sale->payment_status = 'paid';
                $sale->due_amount = 0;
            } elseif ($sale->paid_amount > 0) {
                $sale->payment_status = 'partial';
            } else {
                $sale->payment_status = 'due';
            }

            $sale->save();

            // Deduct stock for new items
            foreach ($ingredientUsage as $ingredientId => $quantity) {
                $this->stockService->reduceStock($ingredientId, $quantity);
            }

            return $sale->fresh(['saleItems.item', 'saleItems.variant', 'payments']);
        });
    }

    /**
     * Get sales with optional filtering.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSales(array $filters = [])
    {
        $query = Sale::with(['saleItems', 'payments', 'user']);

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Add a payment to an existing sale.
     *
     * @param int $saleId
     * @param array $data
     * @return Payment
     */
    public function addPayment(int $saleId, array $data): Payment
    {
        return DB::transaction(function () use ($saleId, $data) {
            $sale = Sale::lockForUpdate()->findOrFail($saleId);

            $payment = $this->createPayment($saleId, $data);

            // Update sale payment status
            $sale->paid_amount += $payment->amount;
            $sale->due_amount = $sale->total_amount - $sale->paid_amount;
            $sale->payment_status = $this->determinePaymentStatus($sale->total_amount, $sale->paid_amount);
            $sale->save();

            return $payment;
        });
    }
}
