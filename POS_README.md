# Cafe POS System - Laravel Backend

A complete Laravel 10 backend API for a cafe Point of Sale (POS) system with ingredient-based stock tracking. Built with PHP 8.2+ and MySQL 8.

## Features

- **Ingredient Management**: Track raw ingredients with units (pcs, kg, ml, etc.)
- **Stock Tracking**: Real-time stock updates, low stock alerts, direct updates to stocks table
- **Menu Items**: Create items with optional variants (sizes, flavors)
- **Sales Processing**: Complete sales workflow with automatic stock deduction
- **Payment Management**: Multiple payment methods, partial payments support
- **Expense Tracking**: Record expenses with optional stock replenishment
- **Comprehensive Reports**: Sales, stock status, ingredient consumption, expenses
- **Transaction Safety**: DB transactions with row-level locking for stock operations

## Architecture

### Clean Laravel Structure

```
app/
├── Http/
│   └── Controllers/
│       ├── IngredientController.php
│       ├── ItemController.php
│       ├── ItemVariantController.php
│       ├── StockController.php
│       ├── ExpenseController.php
│       ├── SaleController.php
│       ├── PaymentController.php
│       └── ReportController.php
├── Models/
│   ├── Ingredient.php
│   ├── Item.php
│   ├── ItemVariant.php
│   ├── Stock.php
│   ├── Expense.php
│   ├── Sale.php
│   ├── SaleItem.php
│   └── Payment.php
└── Services/
    ├── StockService.php
    ├── ExpenseService.php
    └── SaleService.php
```

## Database Schema

### Tables

- **categories**: Menu item categories
- **ingredients**: Raw materials with units
- **items**: Menu items with optional ingredient linkage
- **item_variants**: Size/variant options for items
- **stocks**: Current stock levels (one record per ingredient)
- **expenses**: Business expenses with optional stock addition
- **sales**: Sales transactions
- **sale_items**: Line items for each sale
- **payments**: Payment records for sales

**Important**: No SKU fields, no stock_movements table. All stock changes directly update the `stocks` table.

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Laravel 10

### Setup Steps

1. **Clone or navigate to your project directory**

```bash
cd /Users/anthony/Desktop/web/mini_pos_backend
```

2. **Install dependencies**

```bash
composer install
```

3. **Environment configuration**

Create `.env` file if not exists and configure database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_pos
DB_USERNAME=root
DB_PASSWORD=

# POS Configuration (optional)
POS_ALLOW_NEGATIVE_STOCK=false
POS_LOW_STOCK_THRESHOLD=10
POS_INVOICE_PREFIX=INV-
```

4. **Generate application key**

```bash
php artisan key:generate
```

5. **Run migrations**

```bash
php artisan migrate
```

6. **Seed sample data** (Optional)

```bash
php artisan db:seed --class=PosSeeder
```

This will create:
- 3 categories (Momo, Wings, Drinks)
- 5 ingredients (Raw Momo, Raw Wings, BBQ Sauce, Cheese, Cola)
- 5 stock records with initial quantities
- 5 items (3 with variants)
- 6 item variants

7. **Start the development server**

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Business Logic

### Stock Management

#### Stock Addition (via Expenses)
When creating an expense with an ingredient and quantity:
1. Expense record is created
2. Stock is automatically increased: `stocks.quantity += expense.quantity`
3. If stock record doesn't exist, it's created

#### Stock Deduction (via Sales)
When creating a sale:
1. Calculate ingredient usage per item/variant
2. Lock stock rows using `SELECT ... FOR UPDATE`
3. Check stock availability (if `allow_negative_stock = false`)
4. Deduct stock: `stocks.quantity -= used_quantity`
5. Return 409 error if insufficient stock

### Pricing Logic

```php
unit_price = COALESCE(variant.price, item.price)
unit_cost = COALESCE(variant.cost, item.cost)
ingredient_quantity = COALESCE(variant.ingredient_quantity, item.ingredient_quantity)
```

### Payment Status

- **paid**: `paid_amount >= total_amount`
- **partial**: `0 < paid_amount < total_amount`
- **due**: `paid_amount = 0`

## API Endpoints

### Categories
```
GET    /categories              - List all categories
POST   /categories              - Create category
GET    /categories/{id}         - View category
PUT    /categories/{id}         - Update category
DELETE /categories/{id}         - Delete category
```

### Ingredients
```
GET    /ingredients             - List all ingredients
POST   /ingredients             - Create ingredient (auto-creates stock record)
GET    /ingredients/{id}        - View ingredient
PUT    /ingredients/{id}        - Update ingredient
DELETE /ingredients/{id}        - Delete ingredient
```

### Items
```
GET    /items                   - List all items
POST   /items                   - Create item
GET    /items/{id}              - View item
PUT    /items/{id}              - Update item
DELETE /items/{id}              - Delete item
```

### Item Variants
```
GET    /item-variants           - List all variants
POST   /item-variants           - Create variant
GET    /item-variants/{id}      - View variant
PUT    /item-variants/{id}      - Update variant
DELETE /item-variants/{id}      - Delete variant
GET    /items/{item_id}/variants - Get variants for specific item
```

### Stocks
```
GET    /stocks                  - List all stocks
GET    /stocks/{id}             - View stock details
GET    /stocks/low-stock?threshold=10 - Get low stock items
POST   /stocks/{id}/adjust      - Manual stock adjustment (admin)
```

### Expenses
```
GET    /expenses                - List all expenses
POST   /expenses                - Create expense (auto-updates stock)
GET    /expenses/{id}           - View expense
PUT    /expenses/{id}           - Update expense
DELETE /expenses/{id}           - Delete expense (reverses stock)
```

### Sales
```
GET    /sales                   - List all sales
POST   /sales                   - Create sale (auto-deducts stock)
GET    /sales/{id}              - View sale details
GET    /sales/items/{item_id}/variants - Get variants for item (AJAX)
```

### Payments
```
GET    /payments                - List all payments
POST   /payments                - Add payment to sale
GET    /payments/{id}           - View payment
```

### Reports
```
GET    /reports                           - Reports dashboard
GET    /reports/ingredients-consumed      - Ingredient consumption report
GET    /reports/sales-summary             - Sales summary
GET    /reports/stock-status              - Stock status report
GET    /reports/expenses                  - Expense report
```

## Sample API Requests

### Create an Expense with Stock Addition

```json
POST /expenses
{
  "amount": 5000,
  "due_amount": 0,
  "expense_date": "2024-12-12",
  "description": "Purchased raw momo",
  "ingredient_id": 1,
  "quantity": 500
}
```
Result: Expense created + `stocks.quantity` increased by 500

### Create a Sale

```json
POST /sales
{
  "items": [
    {
      "item_id": 1,
      "variant_id": 2,
      "quantity": 3
    },
    {
      "item_id": 4,
      "quantity": 2
    }
  ],
  "payments": [
    {
      "amount": 500,
      "method": "cash"
    }
  ],
  "note": "Table 5"
}
```
Result: 
- Sale created with invoice number
- Stock automatically deducted based on ingredient consumption
- Payment recorded
- Returns 409 if insufficient stock

### Add Payment to Existing Sale

```json
POST /payments
{
  "sale_id": 1,
  "amount": 200,
  "method": "card",
  "note": "Partial payment"
}
```

### Get Ingredient Consumption Report

```
GET /reports/ingredients-consumed?date_from=2024-12-01&date_to=2024-12-31
```

### Get Low Stock Items

```
GET /stocks/low-stock?threshold=20
```

## Configuration

Edit `config/pos.php`:

```php
return [
    'allow_negative_stock' => false,  // Reject sales if insufficient stock
    'low_stock_threshold' => 10,      // Threshold for low stock alerts
    'invoice_prefix' => 'INV-',       // Invoice number prefix
    'stock_lock_timeout' => 5,        // Stock lock timeout in seconds
];
```

## Services

### StockService

```php
addStock(int $ingredientId, float $quantity): Stock
reduceStock(int $ingredientId, float $quantity, bool $allowNegative): Stock
getStockLevel(int $ingredientId): float
getLowStockItems(float $threshold): Collection
hasSufficientStock(int $ingredientId, float $requiredQuantity): bool
```

### ExpenseService

```php
createExpense(array $data): Expense
getExpenses(array $filters): Collection
updateExpense(int $id, array $data): Expense
deleteExpense(int $id): bool
```

### SaleService

```php
createSale(array $data): Sale
getSale(int $id): Sale
getSales(array $filters): Collection
addPayment(int $saleId, array $data): Payment
```

## Error Handling

- **409 Conflict**: Insufficient stock for sale
- **422 Unprocessable Entity**: Validation errors
- **404 Not Found**: Resource not found
- **500 Internal Server Error**: Server errors

All errors return JSON:
```json
{
  "result": "error",
  "message": "Error description"
}
```

## Transaction Safety

All stock operations use database transactions with row-level locking:

```php
DB::transaction(function () {
    $stock = Stock::where('ingredient_id', $id)
                  ->lockForUpdate()
                  ->first();
    // ... perform operations
});
```

## Testing

Run tests:

```bash
php artisan test
```

Key test scenarios:
- Sale creation with stock deduction
- Expense creation with stock addition
- Insufficient stock protection
- Concurrent stock updates

## PSR-12 Compliance

Code follows PSR-12 coding standards:
- 4 spaces indentation
- Opening braces on same line
- Proper spacing and formatting

## Maintenance

### Clear cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Backup database

```bash
mysqldump -u root -p mini_pos > backup_$(date +%Y%m%d).sql
```

## License

This project is open-sourced software.

## Support

For issues or questions, please contact the development team.

---

**Built with Laravel 10 | PHP 8.2+ | MySQL 8**
