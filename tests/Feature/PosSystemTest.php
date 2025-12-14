<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Ingredient;
use App\Models\Stock;
use App\Models\Item;
use App\Models\ItemVariant;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use App\Services\StockService;
use App\Services\ExpenseService;
use App\Services\SaleService;

class PosSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $stockService;
    protected $expenseService;
    protected $saleService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);
        $this->expenseService = app(ExpenseService::class);
        $this->saleService = app(SaleService::class);

        // Create a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_add_stock_via_expense()
    {
        // Create ingredient
        $ingredient = Ingredient::create([
            'name' => 'Test Ingredient',
            'unit' => 'pcs',
            'note' => 'Test note',
        ]);

        // Create stock record
        Stock::create([
            'ingredient_id' => $ingredient->id,
            'quantity' => 0,
            'unit' => 'pcs',
        ]);

        // Create expense with ingredient
        $expense = $this->expenseService->createExpense([
            'amount' => 1000,
            'ingredient_id' => $ingredient->id,
            'quantity' => 100,
        ]);

        // Assert expense created
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'amount' => 1000,
            'quantity' => 100,
        ]);

        // Assert stock increased
        $stock = Stock::where('ingredient_id', $ingredient->id)->first();
        $this->assertEquals(100, $stock->quantity);
    }

    /** @test */
    public function it_can_deduct_stock_via_sale()
    {
        // Create category
        $category = Category::create([
            'title' => 'Test Category',
            'status' => 1,
        ]);

        // Create ingredient with stock
        $ingredient = Ingredient::create([
            'name' => 'Test Ingredient',
            'unit' => 'pcs',
        ]);

        Stock::create([
            'ingredient_id' => $ingredient->id,
            'quantity' => 100,
            'unit' => 'pcs',
        ]);

        // Create item
        $item = Item::create([
            'name' => 'Test Item',
            'category_id' => $category->id,
            'ingredient_id' => $ingredient->id,
            'ingredient_quantity' => 10,
            'had_variants' => false,
            'price' => 150,
            'cost' => 80,
            'status' => 'active',
        ]);

        // Create sale
        $sale = $this->saleService->createSale([
            'items' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 3,
                ],
            ],
            'payments' => [
                [
                    'amount' => 450,
                    'method' => 'cash',
                ],
            ],
        ]);

        // Assert sale created
        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'total_amount' => 450,
            'payment_status' => 'paid',
        ]);

        // Assert stock deducted (10 pcs per item × 3 items = 30 pcs)
        $stock = Stock::where('ingredient_id', $ingredient->id)->first();
        $this->assertEquals(70, $stock->quantity);
    }

    /** @test */
    public function it_rejects_sale_with_insufficient_stock()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(409);

        // Create category
        $category = Category::create([
            'title' => 'Test Category',
            'status' => 1,
        ]);

        // Create ingredient with low stock
        $ingredient = Ingredient::create([
            'name' => 'Test Ingredient',
            'unit' => 'pcs',
        ]);

        Stock::create([
            'ingredient_id' => $ingredient->id,
            'quantity' => 5,
            'unit' => 'pcs',
        ]);

        // Create item
        $item = Item::create([
            'name' => 'Test Item',
            'category_id' => $category->id,
            'ingredient_id' => $ingredient->id,
            'ingredient_quantity' => 10,
            'had_variants' => false,
            'price' => 150,
            'cost' => 80,
            'status' => 'active',
        ]);

        // Try to create sale (should fail)
        $this->saleService->createSale([
            'items' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 1, // Requires 10 pcs, but only 5 available
                ],
            ],
            'payments' => [
                [
                    'amount' => 150,
                    'method' => 'cash',
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_handle_items_with_variants()
    {
        // Create category
        $category = Category::create([
            'title' => 'Test Category',
            'status' => 1,
        ]);

        // Create ingredient with stock
        $ingredient = Ingredient::create([
            'name' => 'Test Ingredient',
            'unit' => 'pcs',
        ]);

        Stock::create([
            'ingredient_id' => $ingredient->id,
            'quantity' => 100,
            'unit' => 'pcs',
        ]);

        // Create item with variants
        $item = Item::create([
            'name' => 'Test Item',
            'category_id' => $category->id,
            'ingredient_id' => $ingredient->id,
            'had_variants' => true,
            'status' => 'active',
        ]);

        $variantSmall = ItemVariant::create([
            'item_id' => $item->id,
            'name' => 'Small',
            'price' => 100,
            'cost' => 50,
            'ingredient_quantity' => 5,
            'is_default' => true,
        ]);

        $variantLarge = ItemVariant::create([
            'item_id' => $item->id,
            'name' => 'Large',
            'price' => 150,
            'cost' => 80,
            'ingredient_quantity' => 10,
            'is_default' => false,
        ]);

        // Create sale with small variant
        $sale = $this->saleService->createSale([
            'items' => [
                [
                    'item_id' => $item->id,
                    'variant_id' => $variantSmall->id,
                    'quantity' => 2,
                ],
            ],
            'payments' => [
                [
                    'amount' => 200,
                    'method' => 'cash',
                ],
            ],
        ]);

        // Assert sale uses variant pricing
        $this->assertEquals(200, $sale->total_amount); // 100 × 2

        // Assert stock deducted using variant quantity (5 pcs × 2 = 10 pcs)
        $stock = Stock::where('ingredient_id', $ingredient->id)->first();
        $this->assertEquals(90, $stock->quantity);
    }

    /** @test */
    public function it_can_calculate_payment_status_correctly()
    {
        // Create category and item
        $category = Category::create([
            'title' => 'Test Category',
            'status' => 1,
        ]);

        $item = Item::create([
            'name' => 'Test Item',
            'category_id' => $category->id,
            'had_variants' => false,
            'price' => 300,
            'cost' => 150,
            'status' => 'active',
        ]);

        // Test 1: Full payment
        $salePaid = $this->saleService->createSale([
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
            'payments' => [['amount' => 300, 'method' => 'cash']],
        ]);
        $this->assertEquals('paid', $salePaid->payment_status);

        // Test 2: Partial payment
        $salePartial = $this->saleService->createSale([
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
            'payments' => [['amount' => 150, 'method' => 'cash']],
        ]);
        $this->assertEquals('partial', $salePartial->payment_status);

        // Test 3: No payment
        $saleDue = $this->saleService->createSale([
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
        ]);
        $this->assertEquals('due', $saleDue->payment_status);
    }

    /** @test */
    public function it_can_delete_expense_and_reverse_stock()
    {
        // Create ingredient with stock
        $ingredient = Ingredient::create([
            'name' => 'Test Ingredient',
            'unit' => 'pcs',
        ]);

        Stock::create([
            'ingredient_id' => $ingredient->id,
            'quantity' => 50,
            'unit' => 'pcs',
        ]);

        // Create expense (increases stock)
        $expense = $this->expenseService->createExpense([
            'amount' => 1000,
            'ingredient_id' => $ingredient->id,
            'quantity' => 100,
        ]);

        $this->assertEquals(150, Stock::find($ingredient->id)->quantity);

        // Delete expense (should reverse stock)
        $this->expenseService->deleteExpense($expense->id);

        $this->assertEquals(50, Stock::where('ingredient_id', $ingredient->id)->first()->quantity);
    }
}
