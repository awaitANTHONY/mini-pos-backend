# 🚀 POS System - Quick Reference Card

## Setup (One Time)

```bash
# Run this script to set everything up
./setup-pos.sh

# Or manually:
php artisan migrate
php artisan db:seed --class=PosSeeder
php artisan cache:clear
```

---

## Key Routes

### Ingredients
```
GET    /ingredients              List all
POST   /ingredients              Create new
GET    /ingredients/{id}/edit    Edit form
PUT    /ingredients/{id}         Update
DELETE /ingredients/{id}         Delete
```

### Items (Menu)
```
GET    /items                    List all menu items
POST   /items                    Create new item
GET    /items/{id}               View details
PUT    /items/{id}               Update item
```

### Variants
```
GET    /item-variants            List all variants
POST   /item-variants            Create variant
GET    /items/{id}/variants      Variants for specific item
```

### Stocks
```
GET    /stocks                   View all stocks
GET    /stocks/low-stock         Low stock alert
POST   /stocks/{id}/adjust       Manual adjustment
```

### Expenses
```
GET    /expenses                 List expenses
POST   /expenses                 Create (auto-adds stock)
```

### Sales
```
GET    /sales                    List all sales
POST   /sales                    Create sale (auto-deducts stock)
GET    /sales/{id}               View sale details
```

### Payments
```
GET    /payments                 List payments
POST   /payments                 Add payment to sale
```

### Reports
```
GET    /reports/ingredients-consumed   Ingredient usage
GET    /reports/sales-summary          Revenue & profit
GET    /reports/stock-status           Stock levels
GET    /reports/expenses               Expense tracking
```

---

## Sample Requests

### 1. Create Ingredient
```json
POST /ingredients
{
  "name": "Raw Momo",
  "unit": "pcs",
  "note": "Raw momo pieces"
}
```

### 2. Add Stock via Expense
```json
POST /expenses
{
  "amount": 5000,
  "ingredient_id": 1,
  "quantity": 500,
  "description": "Stock purchase"
}
```
**Result:** Stock +500 automatically

### 3. Create Menu Item
```json
POST /items
{
  "name": "Momo BBQ",
  "category_id": 1,
  "ingredient_id": 1,
  "ingredient_quantity": 10,
  "had_variants": false,
  "price": 150,
  "cost": 80,
  "status": "active"
}
```

### 4. Create Variant
```json
POST /item-variants
{
  "item_id": 1,
  "name": "Small",
  "price": 150,
  "cost": 80,
  "ingredient_quantity": 8,
  "is_default": true
}
```

### 5. Create Sale
```json
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
  ],
  "note": "Table 5"
}
```
**Result:** Stock automatically deducted, returns error if insufficient

### 6. Add Payment to Sale
```json
POST /payments
{
  "sale_id": 1,
  "amount": 200,
  "method": "card"
}
```

---

## Stock Logic

### Adding Stock
**Via Expense:**
```
expense.ingredient_id = X
expense.quantity = 100
→ stocks.quantity += 100
```

### Deducting Stock
**Via Sale:**
```
item.ingredient_id = X
item.ingredient_quantity = 10
sale_item.quantity = 3
→ stocks.quantity -= (10 × 3) = -30
```

**With Variants:**
```
variant.ingredient_quantity = 8 (overrides item)
sale_item.quantity = 3
→ stocks.quantity -= (8 × 3) = -24
```

---

## Pricing Logic

```php
unit_price = variant.price ?? item.price
unit_cost = variant.cost ?? item.cost
ingredient_qty = variant.ingredient_quantity ?? item.ingredient_quantity
```

---

## Payment Status

| Status   | Condition                         |
|----------|-----------------------------------|
| `paid`   | paid_amount >= total_amount       |
| `partial`| 0 < paid_amount < total_amount    |
| `due`    | paid_amount = 0                   |

---

## Configuration

Edit `config/pos.php`:

```php
'allow_negative_stock' => false,  // Block sales if insufficient stock
'low_stock_threshold' => 10,      // Alert threshold
'invoice_prefix' => 'INV-',       // Invoice number prefix
```

---

## Testing

```bash
# Run all POS tests
php artisan test --filter PosSystemTest

# Run specific test
php artisan test --filter test_name
```

---

## Important Notes

✅ **NO SKU fields** - Removed from all tables
✅ **NO stock_movements table** - Direct updates only
✅ **Transaction safety** - All stock ops use DB transactions
✅ **Row locking** - `lockForUpdate()` prevents race conditions
✅ **Laravel 8 compatible** - Works with your current version

---

## Troubleshooting

### Migration Error
```bash
php artisan migrate:rollback
php artisan migrate
```

### Clear Everything
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Check Stock Level
```php
$stock = Stock::where('ingredient_id', 1)->first();
echo $stock->quantity;
```

---

## Documentation Files

| File                        | Purpose                          |
|-----------------------------|----------------------------------|
| `POS_README.md`            | Complete technical docs          |
| `SETUP_GUIDE.md`           | Quick start guide                |
| `IMPLEMENTATION_SUMMARY.md`| What was built                   |
| `QUICK_REFERENCE.md`       | This file (quick reference)      |

---

## Architecture

```
Request → Controller → Service → Model → Database
                ↓
            Response
```

**Services:**
- `StockService` - Stock operations
- `ExpenseService` - Expense + auto stock add
- `SaleService` - Sale + auto stock deduct

**Models:**
- Ingredient, Item, ItemVariant
- Stock, Expense
- Sale, SaleItem, Payment

---

## Need Help?

1. Check `POS_README.md` for detailed docs
2. Run tests to verify setup
3. Check Laravel logs: `storage/logs/laravel.log`

---

**Quick Start:** `./setup-pos.sh` then start coding! 🚀
