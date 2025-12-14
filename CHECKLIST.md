# ✅ POS System - Setup & Verification Checklist

## Pre-Setup Checklist

- [ ] PHP 8.0+ installed (`php -v`)
- [ ] Composer installed (`composer --version`)
- [ ] MySQL 8+ running
- [ ] Database created (e.g., `mini_pos`)
- [ ] `.env` file configured with database credentials
- [ ] Laravel project accessible

---

## Setup Checklist

### 1. Run Migrations
```bash
php artisan migrate
```
- [ ] Migration completed without errors
- [ ] Check 8 new tables created:
  - [ ] `ingredients`
  - [ ] `items`
  - [ ] `item_variants`
  - [ ] `stocks`
  - [ ] `expenses`
  - [ ] `sales`
  - [ ] `sale_items`
  - [ ] `payments`

### 2. Seed Sample Data (Optional)
```bash
php artisan db:seed --class=PosSeeder
```
- [ ] Seeding completed
- [ ] Check database has sample data:
  - [ ] 3 categories in `categories` table
  - [ ] 5 ingredients in `ingredients` table
  - [ ] 5 stocks in `stocks` table
  - [ ] 5 items in `items` table
  - [ ] 6 variants in `item_variants` table

### 3. Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
```
- [ ] Cache cleared successfully

---

## Verification Checklist

### Database Verification

Run these SQL queries to verify:

```sql
-- Check ingredients
SELECT * FROM ingredients;

-- Check stocks
SELECT * FROM stocks;

-- Check items
SELECT * FROM items;

-- Check item variants
SELECT * FROM item_variants;
```

- [ ] Tables have correct structure
- [ ] Sample data is present (if seeded)
- [ ] Foreign keys are set up correctly

### File Verification

Check these files exist:

**Config:**
- [ ] `config/pos.php`

**Migrations:**
- [ ] `database/migrations/2024_01_01_000001_create_ingredients_table.php`
- [ ] `database/migrations/2024_01_01_000002_create_items_table.php`
- [ ] `database/migrations/2024_01_01_000003_create_item_variants_table.php`
- [ ] `database/migrations/2024_01_01_000004_create_stocks_table.php`
- [ ] `database/migrations/2024_01_01_000005_create_expenses_table.php`
- [ ] `database/migrations/2024_01_01_000006_create_sales_table.php`
- [ ] `database/migrations/2024_01_01_000007_create_sale_items_table.php`
- [ ] `database/migrations/2024_01_01_000008_create_payments_table.php`

**Models:**
- [ ] `app/Models/Ingredient.php`
- [ ] `app/Models/Item.php`
- [ ] `app/Models/ItemVariant.php`
- [ ] `app/Models/Stock.php`
- [ ] `app/Models/Expense.php`
- [ ] `app/Models/Sale.php`
- [ ] `app/Models/SaleItem.php`
- [ ] `app/Models/Payment.php`

**Services:**
- [ ] `app/Services/StockService.php`
- [ ] `app/Services/ExpenseService.php`
- [ ] `app/Services/SaleService.php`

**Controllers:**
- [ ] `app/Http/Controllers/IngredientController.php`
- [ ] `app/Http/Controllers/ItemController.php`
- [ ] `app/Http/Controllers/ItemVariantController.php`
- [ ] `app/Http/Controllers/StockController.php`
- [ ] `app/Http/Controllers/ExpenseController.php`
- [ ] `app/Http/Controllers/SaleController.php`
- [ ] `app/Http/Controllers/PaymentController.php`
- [ ] `app/Http/Controllers/ReportController.php`

**Other:**
- [ ] `routes/web.php` updated with POS routes
- [ ] `database/seeders/PosSeeder.php`
- [ ] `tests/Feature/PosSystemTest.php`

---

## Functional Testing Checklist

### Test 1: Stock Addition via Expense

```bash
# Via tinker
php artisan tinker

# Then run:
$expense = app(\App\Services\ExpenseService::class)->createExpense([
    'amount' => 5000,
    'ingredient_id' => 1,
    'quantity' => 100,
]);

$stock = \App\Models\Stock::where('ingredient_id', 1)->first();
echo "Stock quantity: " . $stock->quantity;
```

- [ ] Expense created successfully
- [ ] Stock quantity increased by 100
- [ ] No errors thrown

### Test 2: Stock Deduction via Sale

```bash
php artisan tinker

# Run:
$sale = app(\App\Services\SaleService::class)->createSale([
    'items' => [
        ['item_id' => 1, 'variant_id' => 1, 'quantity' => 2]
    ],
    'payments' => [
        ['amount' => 300, 'method' => 'cash']
    ]
]);

echo "Sale ID: " . $sale->id;
echo "Invoice: " . $sale->invoice_no;
```

- [ ] Sale created successfully
- [ ] Stock deducted automatically
- [ ] Invoice number generated
- [ ] Payment recorded

### Test 3: Insufficient Stock Protection

```bash
php artisan tinker

# Try to create sale with insufficient stock
try {
    $sale = app(\App\Services\SaleService::class)->createSale([
        'items' => [
            ['item_id' => 1, 'variant_id' => 1, 'quantity' => 1000]
        ],
        'payments' => [
            ['amount' => 1000, 'method' => 'cash']
        ]
    ]);
} catch (\Exception $e) {
    echo "Error (expected): " . $e->getMessage();
}
```

- [ ] Exception thrown
- [ ] Error message mentions insufficient stock
- [ ] No sale created
- [ ] Stock unchanged

### Test 4: Run Feature Tests

```bash
php artisan test --filter PosSystemTest
```

- [ ] All tests pass
- [ ] No errors or failures
- [ ] Tests cover:
  - [ ] Stock addition via expense
  - [ ] Stock deduction via sale
  - [ ] Insufficient stock rejection
  - [ ] Variant handling
  - [ ] Payment status calculation

---

## Route Testing Checklist

Test each route is accessible:

### Ingredients
- [ ] `GET /ingredients` - Lists ingredients
- [ ] `GET /ingredients/create` - Shows create form
- [ ] `POST /ingredients` - Creates ingredient
- [ ] `GET /ingredients/{id}/edit` - Shows edit form

### Items
- [ ] `GET /items` - Lists items
- [ ] `GET /items/create` - Shows create form
- [ ] `POST /items` - Creates item

### Stocks
- [ ] `GET /stocks` - Lists all stocks
- [ ] `GET /stocks/low-stock` - Shows low stock items

### Sales
- [ ] `GET /sales` - Lists sales
- [ ] `GET /sales/create` - Shows sale form
- [ ] `POST /sales` - Creates sale

### Reports
- [ ] `GET /reports` - Reports dashboard
- [ ] `GET /reports/sales-summary` - Sales report
- [ ] `GET /reports/stock-status` - Stock report

---

## Business Logic Verification

### Pricing Logic Test
```sql
-- Item with variant
SELECT 
    i.name as item,
    i.price as item_price,
    iv.name as variant,
    iv.price as variant_price
FROM items i
LEFT JOIN item_variants iv ON i.id = iv.item_id
WHERE i.had_variants = 1;
```

- [ ] Items with variants have NULL prices at item level
- [ ] Variants have non-NULL prices
- [ ] Pricing follows COALESCE(variant.price, item.price)

### Ingredient Quantity Logic
```sql
-- Check ingredient quantities
SELECT 
    i.name as item,
    i.ingredient_quantity as item_qty,
    iv.name as variant,
    iv.ingredient_quantity as variant_qty
FROM items i
LEFT JOIN item_variants iv ON i.id = iv.item_id
WHERE i.ingredient_id IS NOT NULL;
```

- [ ] Quantity resolution follows COALESCE pattern
- [ ] Variant overrides work correctly

---

## Configuration Verification

Check `config/pos.php`:

```php
return [
    'allow_negative_stock' => false,  // Should reject if insufficient
    'low_stock_threshold' => 10,      // Alert threshold
    'invoice_prefix' => 'INV-',       // Invoice format
];
```

- [ ] Config file exists
- [ ] Values are sensible for your business
- [ ] `allow_negative_stock` is false (recommended)

---

## Performance Verification

### Check Transaction Usage

Look for this pattern in Services:

```php
DB::transaction(function () {
    $stock = Stock::lockForUpdate()->first();
    // ... operations
});
```

- [ ] All stock operations use transactions
- [ ] `lockForUpdate()` is used
- [ ] Prevents race conditions

### Check No N+1 Queries

```bash
# Enable query logging
DB::enableQueryLog();

# Run operation
$sales = Sale::with(['saleItems.item', 'payments'])->get();

# Check queries
dd(DB::getQueryLog());
```

- [ ] Relationships use eager loading
- [ ] No N+1 query problems

---

## Documentation Verification

- [ ] `POS_README.md` exists and is comprehensive
- [ ] `SETUP_GUIDE.md` provides quick start
- [ ] `IMPLEMENTATION_SUMMARY.md` lists all files
- [ ] `QUICK_REFERENCE.md` has API examples
- [ ] `ARCHITECTURE.md` explains design
- [ ] `CHECKLIST.md` (this file) guides setup

---

## Security Checklist

- [ ] All controllers validate input
- [ ] Foreign key constraints in place
- [ ] Transactions protect critical operations
- [ ] User authentication required (via middleware)
- [ ] No SQL injection vulnerabilities (using Eloquent)

---

## Production Readiness Checklist

Before deploying to production:

- [ ] All tests pass
- [ ] Error handling is comprehensive
- [ ] Logging is set up
- [ ] Database backups configured
- [ ] `.env` has production values
- [ ] `APP_DEBUG` is false
- [ ] Cache is optimized
- [ ] Stock threshold configured
- [ ] Invoice prefix set
- [ ] User roles/permissions set up

---

## Common Issues & Solutions

### Issue: Migration fails
**Solution:**
```bash
php artisan migrate:rollback
php artisan migrate:fresh
php artisan db:seed --class=PosSeeder
```

### Issue: Class not found
**Solution:**
```bash
composer dump-autoload
php artisan cache:clear
php artisan config:clear
```

### Issue: Foreign key constraint error
**Solution:**
- Check migration order (2024_01_01_000001 comes before 000002)
- Ensure parent records exist before creating child records

### Issue: Stock not updating
**Solution:**
- Check expense has both `ingredient_id` and `quantity`
- Verify stock record exists for ingredient
- Check transaction committed successfully

---

## Success Criteria

✅ **System is ready when:**

1. All migrations run successfully
2. Sample data seeded correctly
3. Routes are accessible
4. Services create/update records properly
5. Stock increases with expenses
6. Stock decreases with sales
7. Insufficient stock is rejected
8. Tests pass
9. No errors in logs
10. Documentation is clear

---

## Next Steps After Verification

Once everything is checked:

1. **Create UI views** (following your blade template patterns)
2. **Add authentication** (if not already present)
3. **Customize for your cafe** (menu items, prices, etc.)
4. **Train staff** on the system
5. **Go live!** 🚀

---

**Need help?** Check the documentation files or review the code comments.
