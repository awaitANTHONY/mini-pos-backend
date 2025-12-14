<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Expense;
use App\Services\SaleService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * Download all data needed for offline mode
     * GET /api/v1/sync/download
     */
    public function download(Request $request)
    {
        try {
            $lastSyncAt = $request->get('last_sync_at', null);

            // Get all active categories
            $categories = Category::where('status', 1)
                ->orderBy('title', 'asc')
                ->get(['id', 'title', 'description', 'image', 'status', 'updated_at']);

            // Get all active items with variants
            $items = Item::with(['category', 'ingredient', 'variants'])
                ->where('status', 1)
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'image_url' => $item->image_url,
                        'category_id' => $item->category_id,
                        'category' => $item->category ? [
                            'id' => $item->category->id,
                            'title' => $item->category->title,
                        ] : null,
                        'ingredient_id' => $item->ingredient_id,
                        'ingredient' => $item->ingredient ? [
                            'id' => $item->ingredient->id,
                            'name' => $item->ingredient->name,
                            'unit' => $item->ingredient->unit,
                        ] : null,
                        'ingredient_quantity' => $item->ingredient_quantity,
                        'had_variants' => $item->had_variants,
                        'price' => $item->price,
                        'cost' => $item->cost,
                        'status' => $item->status,
                        'variants' => $item->variants->map(function ($variant) {
                            return [
                                'id' => $variant->id,
                                'name' => $variant->name,
                                'price' => $variant->price,
                                'cost' => $variant->cost,
                                'ingredient_quantity' => $variant->ingredient_quantity,
                                'is_default' => $variant->is_default,
                            ];
                        }),
                        'updated_at' => $item->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'items' => $items,
                    'synced_at' => now()->toIso8601String(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download data',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload offline data to server (batch sync)
     * POST /api/v1/sync/upload
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sales' => 'nullable|array',
            'sales.*.temp_id' => 'required|string',
            'sales.*.items' => 'required|array|min:1',
            'sales.*.items.*.item_id' => 'required|exists:items,id',
            'sales.*.items.*.variant_id' => 'nullable|exists:item_variants,id',
            'sales.*.items.*.quantity' => 'required|numeric|min:1',
            'sales.*.items.*.unit_price' => 'required|numeric|min:0',
            'sales.*.items.*.total_price' => 'required|numeric|min:0',
            'sales.*.payment_amount' => 'nullable|numeric|min:0',
            'sales.*.payment_method' => 'nullable|string|in:cash,online',
            'sales.*.note' => 'nullable|string',
            'sales.*.created_at_offline' => 'required|date',

            'expenses' => 'nullable|array',
            'expenses.*.temp_id' => 'required|string',
            'expenses.*.expense_date' => 'required|date',
            'expenses.*.total_amount' => 'required|numeric|min:0',
            'expenses.*.ingredient_id' => 'nullable|exists:ingredients,id',
            'expenses.*.quantity' => 'nullable|numeric|min:0',
            'expenses.*.unit_price' => 'nullable|numeric|min:0',
            'expenses.*.supplier_name' => 'nullable|string|max:191',
            'expenses.*.supplier_contact' => 'nullable|string|max:191',
            'expenses.*.description' => 'nullable|string',
            'expenses.*.note' => 'nullable|string',
            'expenses.*.created_at_offline' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $syncResults = [
                'sales' => [
                    'success' => [],
                    'failed' => []
                ],
                'expenses' => [
                    'success' => [],
                    'failed' => []
                ]
            ];

            // Process Sales
            if ($request->has('sales')) {
                foreach ($request->sales as $saleData) {
                    try {
                        // Convert payment_amount to payments array format
                        $payments = [];
                        if (!empty($saleData['payment_amount']) && $saleData['payment_amount'] > 0) {
                            $payments[] = [
                                'amount' => $saleData['payment_amount'],
                                'method' => $saleData['payment_method'] ?? 'cash',
                                'paid_at' => $saleData['created_at_offline'] ?? now(),
                            ];
                        }

                        $sale = $this->saleService->createSale([
                            'items' => $saleData['items'],
                            'payments' => $payments,
                            'note' => $saleData['note'] ?? null,
                        ], auth()->id());

                        // Update created_at to match offline timestamp
                        $sale->created_at = $saleData['created_at_offline'];
                        $sale->save();

                        $syncResults['sales']['success'][] = [
                            'temp_id' => $saleData['temp_id'],
                            'server_id' => $sale->id,
                            'invoice_no' => $sale->invoice_no,
                        ];
                    } catch (\Exception $e) {
                        $syncResults['sales']['failed'][] = [
                            'temp_id' => $saleData['temp_id'],
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            // Process Expenses
            if ($request->has('expenses')) {
                foreach ($request->expenses as $expenseData) {
                    try {
                        $expense = Expense::create([
                            'expense_date' => $expenseData['expense_date'],
                            'total_amount' => $expenseData['total_amount'],
                            'due_amount' => $expenseData['due_amount'] ?? 0,
                            'ingredient_id' => $expenseData['ingredient_id'] ?? null,
                            'quantity' => $expenseData['quantity'] ?? null,
                            'unit_price' => $expenseData['unit_price'] ?? null,
                            'supplier_name' => $expenseData['supplier_name'] ?? null,
                            'supplier_contact' => $expenseData['supplier_contact'] ?? null,
                            'description' => $expenseData['description'] ?? null,
                            'note' => $expenseData['note'] ?? null,
                            'created_by' => auth()->id(),
                            'created_at' => $expenseData['created_at_offline'],
                        ]);

                        $syncResults['expenses']['success'][] = [
                            'temp_id' => $expenseData['temp_id'],
                            'server_id' => $expense->id,
                        ];
                    } catch (\Exception $e) {
                        $syncResults['expenses']['failed'][] = [
                            'temp_id' => $expenseData['temp_id'],
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sync completed',
                'data' => [
                    'results' => $syncResults,
                    'synced_at' => now()->toIso8601String(),
                    'summary' => [
                        'sales_synced' => count($syncResults['sales']['success']),
                        'sales_failed' => count($syncResults['sales']['failed']),
                        'expenses_synced' => count($syncResults['expenses']['success']),
                        'expenses_failed' => count($syncResults['expenses']['failed']),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync status - check for updates
     * GET /api/v1/sync/status
     */
    public function status(Request $request)
    {
        try {
            $lastSyncAt = $request->get('last_sync_at');

            if (!$lastSyncAt) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'requires_full_sync' => true,
                        'message' => 'First sync required'
                    ]
                ]);
            }

            $lastSync = Carbon::parse($lastSyncAt);

            // Check if categories or items have been updated
            $categoriesUpdated = Category::where('updated_at', '>', $lastSync)->exists();
            $itemsUpdated = Item::where('updated_at', '>', $lastSync)->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'requires_sync' => $categoriesUpdated || $itemsUpdated,
                    'categories_updated' => $categoriesUpdated,
                    'items_updated' => $itemsUpdated,
                    'checked_at' => now()->toIso8601String(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check sync status',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update offline sale (within 24 hours)
     * PUT /api/v1/sync/sales/{temp_id}
     */
    public function updateOfflineSale(Request $request, $tempId)
    {
        $validator = Validator::make($request->all(), [
            'server_id' => 'required|exists:sales,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.variant_id' => 'nullable|exists:item_variants,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $sale = Sale::find($request->server_id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        // Check time restriction
        $updateTime = (int) get_option('update_time', 24);
        $createdAt = Carbon::parse($sale->created_at);
        $now = Carbon::now();
        $hoursDifference = $createdAt->diffInHours($now);

        if ($hoursDifference > $updateTime) {
            return response()->json([
                'success' => false,
                'message' => "Cannot edit sale after {$updateTime} hours"
            ], 403);
        }

        try {
            $updatedSale = $this->saleService->updateSale($request->server_id, [
                'items' => $request->items,
                'note' => $request->note,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sale updated successfully',
                'data' => [
                    'temp_id' => $tempId,
                    'server_id' => $updatedSale->id,
                    'invoice_no' => $updatedSale->invoice_no,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sale',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update offline expense (within 24 hours)
     * PUT /api/v1/sync/expenses/{temp_id}
     */
    public function updateOfflineExpense(Request $request, $tempId)
    {
        $validator = Validator::make($request->all(), [
            'server_id' => 'required|exists:expenses,id',
            'expense_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'ingredient_id' => 'nullable|exists:ingredients,id',
            'quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'supplier_name' => 'nullable|string|max:191',
            'supplier_contact' => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $expense = Expense::find($request->server_id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found'
            ], 404);
        }

        // Check time restriction
        $updateTime = (int) get_option('update_time', 24);
        $createdAt = Carbon::parse($expense->created_at);
        $now = Carbon::now();
        $hoursDifference = $createdAt->diffInHours($now);

        if ($hoursDifference > $updateTime) {
            return response()->json([
                'success' => false,
                'message' => "Cannot edit expense after {$updateTime} hours"
            ], 403);
        }

        try {
            $expense->update([
                'expense_date' => $request->expense_date,
                'total_amount' => $request->total_amount,
                'due_amount' => $request->get('due_amount', $expense->due_amount),
                'ingredient_id' => $request->ingredient_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'supplier_name' => $request->supplier_name,
                'supplier_contact' => $request->supplier_contact,
                'description' => $request->description,
                'note' => $request->note,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'data' => [
                    'temp_id' => $tempId,
                    'server_id' => $expense->id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
