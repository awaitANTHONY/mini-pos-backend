<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ExpenseService
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Create a new expense and update stock if ingredient is provided.
     *
     * @param array $data
     * @return Expense
     * @throws Exception
     */
    public function createExpense(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            // Create the expense
            $expense = new Expense();
            $expense->total_amount = $data['total_amount'];
            $expense->due_amount = $data['due_amount'] ?? 0;
            $expense->expense_date = $data['expense_date'] ?? now()->toDateString();
            $expense->description = $data['description'] ?? null;
            $expense->ingredient_id = $data['ingredient_id'] ?? null;
            $expense->quantity = $data['quantity'] ?? 0;
            $expense->created_by = Auth::id();
            $expense->save();

            // If ingredient and quantity are provided, update stock
            if ($expense->ingredient_id && $expense->quantity > 0) {
                $this->stockService->addStock($expense->ingredient_id, $expense->quantity);
            }

            return $expense->load('ingredient');
        });
    }

    /**
     * Get all expenses with optional filtering.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpenses(array $filters = [])
    {
        $query = Expense::with(['ingredient', 'creator']);

        if (isset($filters['date_from'])) {
            $query->where('expense_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('expense_date', '<=', $filters['date_to']);
        }

        if (isset($filters['ingredient_id'])) {
            $query->where('ingredient_id', $filters['ingredient_id']);
        }

        return $query->orderBy('expense_date', 'desc')->get();
    }

    /**
     * Get a single expense by ID.
     *
     * @param int $id
     * @return Expense
     */
    public function getExpense(int $id): Expense
    {
        return Expense::with(['ingredient', 'creator'])->findOrFail($id);
    }

    /**
     * Update an expense.
     *
     * @param int $id
     * @param array $data
     * @return Expense
     * @throws Exception
     */
    public function updateExpense(int $id, array $data): Expense
    {
        return DB::transaction(function () use ($id, $data) {
            $expense = Expense::findOrFail($id);
            
            // Store old values
            $oldIngredientId = $expense->ingredient_id;
            $oldQuantity = $expense->quantity;

            // Update expense fields
            $expense->fill($data);
            $expense->save();

            // Handle stock adjustments if ingredient/quantity changed
            if ($oldIngredientId && $oldQuantity > 0) {
                // Reverse the old stock addition
                try {
                    $this->stockService->reduceStock($oldIngredientId, $oldQuantity, true);
                } catch (Exception $e) {
                    // Stock might not exist anymore, continue
                }
            }

            if ($expense->ingredient_id && $expense->quantity > 0) {
                // Add new stock
                $this->stockService->addStock($expense->ingredient_id, $expense->quantity);
            }

            return $expense->load('ingredient');
        });
    }

    /**
     * Delete an expense and reverse stock changes.
     *
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function deleteExpense(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $expense = Expense::findOrFail($id);

            // Reverse stock addition if applicable
            if ($expense->ingredient_id && $expense->quantity > 0) {
                try {
                    $this->stockService->reduceStock($expense->ingredient_id, $expense->quantity, true);
                } catch (Exception $e) {
                    // Stock might not exist anymore, continue with deletion
                }
            }

            return $expense->delete();
        });
    }
}
