# POS System Setup Quick Guide

## What Has Been Generated

### ✅ Complete Backend Structure

1. **Configuration**
   - `config/pos.php` - POS system settings

2. **Database Migrations** (8 new tables)
   - `2024_01_01_000001_create_ingredients_table.php`
   - `2024_01_01_000002_create_items_table.php`
   - `2024_01_01_000003_create_item_variants_table.php`
   - `2024_01_01_000004_create_stocks_table.php`
   - `2024_01_01_000005_create_expenses_table.php`
   - `2024_01_01_000006_create_sales_table.php`
   - `2024_01_01_000007_create_sale_items_table.php`
   - `2024_01_01_000008_create_payments_table.php`

3. **Eloquent Models** (8 models with relationships)
   - `app/Models/Ingredient.php`
   - `app/Models/Item.php`
   - `app/Models/ItemVariant.php`
   - `app/Models/Stock.php`
   - `app/Models/Expense.php`
   - `app/Models/Sale.php`
   - `app/Models/SaleItem.php`
   - `app/Models/Payment.php`

4. **Services** (Business logic layer)
   - `app/Services/StockService.php` - Stock management
   - `app/Services/ExpenseService.php` - Expense with auto stock addition
   - `app/Services/SaleService.php` - Sales with auto stock deduction

5. **Controllers** (Following your existing pattern)
   - `app/Http/Controllers/IngredientController.php`
   - `app/Http/Controllers/ItemController.php`
   - `app/Http/Controllers/ItemVariantController.php`
   - `app/Http/Controllers/StockController.php`
   - `app/Http/Controllers/ExpenseController.php`
   - `app/Http/Controllers/SaleController.php`
   - `app/Http/Controllers/PaymentController.php`
   - `app/Http/Controllers/ReportController.php`

6. **Routes**
   - Updated `routes/web.php` with all POS routes

7. **Database Seeder**
   - `database/seeders/PosSeeder.php` - Sample data

8. **Documentation**
   - `POS_README.md` - Complete setup and usage guide

---

## Quick Setup Commands

### 1. Run Migrations

```bash
php artisan migrate
```

This will create all 8 new tables in your database.

### 2. Seed Sample Data (Optional)

```bash
php artisan db:seed --class=PosSeeder
```

This creates:
- 3 Categories (Momo, Wings, Drinks)
- 5 Ingredients with initial stock
- 5 Menu items (3 with variants)

### 3. Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
```

---

## Key Features Implemented

### ✅ NO SKU Fields
- Completely removed from all tables and models

### ✅ NO stock_movements Table
- All stock changes directly update the `stocks` table
- Stock additions via `ExpenseService`
- Stock deductions via `SaleService`

### ✅ Direct Stock Updates
- **Expense Creation** → Stock increases immediately
- **Sale Creation** → Stock decreases immediately (with transaction safety)

### ✅ Transaction Safety
- DB transactions with `lockForUpdate()` for all stock operations
- Prevents race conditions in concurrent sales

### ✅ Business Logic
- Pricing: `COALESCE(variant.price, item.price)`
- Ingredient quantity: `COALESCE(variant.ingredient_quantity, item.ingredient_quantity)`
- Stock checking based on `config('pos.allow_negative_stock')`

---

## Testing the System

### 1. Create an Ingredient
```
POST /ingredients
{
  "name": "Raw Momo",
  "unit": "pcs",
  "note": "Raw momo pieces"
}
```

### 2. Add Stock via Expense
```
POST /expenses
{
  "amount": 5000,
  "ingredient_id": 1,
  "quantity": 500,
  "description": "Purchased raw ingredients"
}
```
✅ Stock automatically increased by 500

### 3. Create Menu Item with Variants
```
POST /items
{
  "name": "Momo BBQ",
  "category_id": 1,
  "ingredient_id": 1,
  "had_variants": true,
  "status": "active"
}

POST /item-variants
{
  "item_id": 1,
  "name": "Small",
  "price": 150,
  "cost": 80,
  "ingredient_quantity": 8
}
```

### 4. Create a Sale
```
POST /sales
{
  "items": [
    {
      "item_id": 1,
      "variant_id": 1,
      "quantity": 3
    }
  ],
  "payments": [
    {
      "amount": 450,
      "method": "cash"
    }
  ]
}
```
✅ Stock automatically deducted: 8 pcs × 3 = 24 pcs
✅ Returns error 409 if insufficient stock

---

## Routes Available

All routes follow your existing pattern with AJAX modal support:

```
/ingredients         - CRUD for ingredients
/items               - CRUD for menu items
/item-variants       - CRUD for variants
/stocks              - View stocks, low stock alerts
/expenses            - CRUD for expenses
/sales               - Create and view sales
/payments            - Add payments to sales
/reports             - Various reports
```

---

## Architecture Highlights

### Service Layer Pattern
Controllers are thin, business logic is in Services:
- `StockService` - All stock operations
- `ExpenseService` - Expense creation with stock addition
- `SaleService` - Sale creation with stock deduction

### Transaction Safety
```php
DB::transaction(function () {
    $stock = Stock::lockForUpdate()->first();
    // Atomic operations
});
```

### Clean Separation
- **Models**: Data structure + relationships
- **Services**: Business logic
- **Controllers**: Request handling + response formatting
- **Validators**: Input validation (inline in controllers following your pattern)

---

## Next Steps

1. **Run migrations** to create the tables
2. **Optionally seed** sample data to test
3. **Create views** for the UI (following your existing blade templates pattern)
4. **Test the API** using the examples above
5. **Customize** based on your specific cafe needs

---

## Important Notes

⚠️ **Your existing system is Laravel 8.x**, not Laravel 10. All code is compatible with Laravel 8.

⚠️ **Categories table** uses `title` field (your existing structure), not `name`. The seeder and controllers work with your existing structure.

⚠️ **DataTables** integration follows your existing pattern (CategoryController style).

⚠️ **AJAX modals** support is built-in following your existing pattern.

---

## File Locations Reference

```
config/pos.php
database/migrations/2024_01_01_*.php (8 files)
app/Models/*.php (8 files)
app/Services/*.php (3 files)
app/Http/Controllers/*.php (8 files)
routes/web.php (updated)
database/seeders/PosSeeder.php
POS_README.md (detailed documentation)
```

---

**Everything is ready to use!** Just run the migrations and start testing. 🚀
