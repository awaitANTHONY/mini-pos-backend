<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    /**
     * Add stock for an ingredient.
     *
     * @param int $ingredientId
     * @param float $quantity
     * @return Stock
     * @throws Exception
     */
    public function addStock(int $ingredientId, float $quantity): Stock
    {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than zero.');
        }

        $ingredient = Ingredient::findOrFail($ingredientId);

        return DB::transaction(function () use ($ingredient, $quantity) {
            $stock = Stock::where('ingredient_id', $ingredient->id)->lockForUpdate()->first();

            if (!$stock) {
                $stock = Stock::create([
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $quantity,
                    'unit' => $ingredient->unit,
                ]);
            } else {
                $stock->quantity += $quantity;
                $stock->save();
            }

            return $stock;
        });
    }

    /**
     * Reduce stock for an ingredient.
     *
     * @param int $ingredientId
     * @param float $quantity
     * @param bool $allowNegative
     * @return Stock
     * @throws Exception
     */
    public function reduceStock(int $ingredientId, float $quantity, bool $allowNegative = false): Stock
    {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($ingredientId, $quantity, $allowNegative) {
            $stock = Stock::where('ingredient_id', $ingredientId)->lockForUpdate()->first();

            if (!$stock) {
                throw new Exception("Stock record not found for ingredient ID: {$ingredientId}");
            }

            $newQuantity = $stock->quantity - $quantity;

            if (!$allowNegative && $newQuantity < 0) {
                throw new Exception("Insufficient stock for ingredient ID: {$ingredientId}. Available: {$stock->quantity}, Required: {$quantity}");
            }

            $stock->quantity = $newQuantity;
            $stock->save();

            return $stock;
        });
    }

    /**
     * Get current stock level for an ingredient.
     *
     * @param int $ingredientId
     * @return float
     */
    public function getStockLevel(int $ingredientId): float
    {
        $stock = Stock::where('ingredient_id', $ingredientId)->first();
        return $stock ? $stock->quantity : 0;
    }

    /**
     * Get low stock items.
     *
     * @param float $threshold
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockItems(float $threshold = null)
    {
        $threshold = $threshold ?? config('pos.low_stock_threshold', 10);

        return Stock::with('ingredient')
            ->where('quantity', '<=', $threshold)
            ->get();
    }

    /**
     * Get all stocks with ingredient details.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllStocks()
    {
        return Stock::with('ingredient')->get();
    }

    /**
     * Check if sufficient stock exists for an ingredient.
     *
     * @param int $ingredientId
     * @param float $requiredQuantity
     * @return bool
     */
    public function hasSufficientStock(int $ingredientId, float $requiredQuantity): bool
    {
        $currentStock = $this->getStockLevel($ingredientId);
        return $currentStock >= $requiredQuantity;
    }
}
