# POS System Architecture Diagram

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        WEB APPLICATION                           │
│                     (Laravel 8 Backend)                          │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 │ HTTP Request
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                         CONTROLLERS                              │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐               │
│  │ Ingredient │  │    Item    │  │   Stock    │               │
│  │ Controller │  │ Controller │  │ Controller │  ... etc      │
│  └────────────┘  └────────────┘  └────────────┘               │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Business Logic
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                          SERVICES                                │
│  ┌────────────────┐  ┌────────────────┐  ┌─────────────────┐  │
│  │ StockService   │  │ ExpenseService │  │  SaleService    │  │
│  │                │  │                │  │                 │  │
│  │ • addStock     │  │ • createExpense│  │ • createSale    │  │
│  │ • reduceStock  │  │ • autoAddStock │  │ • deductStock   │  │
│  │ • getLowStock  │  └────────────────┘  │ • calculateUse  │  │
│  └────────────────┘                       └─────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Database Operations
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                         ELOQUENT MODELS                          │
│  ┌──────────┐  ┌──────┐  ┌─────────┐  ┌───────┐  ┌────────┐  │
│  │Ingredient│  │ Item │  │  Stock  │  │Expense│  │  Sale  │  │
│  └──────────┘  └──────┘  └─────────┘  └───────┘  └────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                       MySQL DATABASE                             │
│  ┌───────────────┐  ┌───────────────┐  ┌─────────────┐        │
│  │  ingredients  │  │     items     │  │   stocks    │        │
│  │  expenses     │  │     sales     │  │  payments   │        │
│  └───────────────┘  └───────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema Relationships

```
┌────────────┐
│ categories │
└────────────┘
       │
       │ 1:N
       ▼
┌────────────┐         ┌──────────────┐
│   items    │◄────1:N─┤item_variants │
└────────────┘         └──────────────┘
       │
       │ N:1
       ▼
┌────────────┐         ┌──────────┐
│ingredients │◄───1:1──┤  stocks  │
└────────────┘         └──────────┘
       │
       │ 1:N
       ▼
┌────────────┐
│  expenses  │
└────────────┘

┌────────────┐         ┌────────────┐         ┌──────────┐
│   sales    │◄───1:N──┤ sale_items │───N:1──►│  items   │
└────────────┘         └────────────┘         └──────────┘
       │
       │ 1:N
       ▼
┌────────────┐
│  payments  │
└────────────┘
```

---

## Stock Flow Diagram

### Stock Addition (via Expense)

```
┌──────────────┐
│  User Action │
│ Create Expense│
└───────┬──────┘
        │
        ▼
┌───────────────────┐
│ ExpenseController │
│   store()         │
└────────┬──────────┘
         │
         ▼
┌───────────────────┐
│  ExpenseService   │
│  createExpense()  │
└────────┬──────────┘
         │
         ▼
┌───────────────────┐      ┌────────────────┐
│  StockService     │─────►│ DB Transaction │
│  addStock()       │      │ + lockForUpdate│
└────────┬──────────┘      └────────────────┘
         │
         ▼
┌───────────────────┐
│  stocks table     │
│ quantity += X     │
└───────────────────┘
```

### Stock Deduction (via Sale)

```
┌──────────────┐
│  User Action │
│  Create Sale  │
└───────┬──────┘
        │
        ▼
┌───────────────────┐
│  SaleController   │
│   store()         │
└────────┬──────────┘
         │
         ▼
┌───────────────────┐
│   SaleService     │
│   createSale()    │
└────────┬──────────┘
         │
         ├─► Calculate ingredient usage
         │   (qty × ingredient_quantity)
         │
         ├─► Check stock availability
         │   (if allow_negative = false)
         │
         ├─► Lock stock rows
         │   (SELECT ... FOR UPDATE)
         │
         └─► Deduct stock
             │
             ▼
┌───────────────────┐
│  stocks table     │
│ quantity -= X     │
└───────────────────┘
```

---

## Sale Transaction Flow

```
POST /sales
    │
    ▼
┌─────────────────────────────────────────────┐
│        SaleService::createSale()            │
│                                             │
│  1. Validate items                          │
│  2. Calculate ingredient usage              │
│  3. Check stock availability                │
│     ├─ If insufficient & !allow_negative    │
│     │  └─► Throw Exception (409)            │
│     └─ Continue                             │
│                                             │
│  4. Start DB Transaction                    │
│     ├─► Create Sale record                  │
│     ├─► Create SaleItem records             │
│     ├─► Create Payment records              │
│     ├─► Lock Stock rows                     │
│     ├─► Deduct Stock                        │
│     ├─► Update Sale totals                  │
│     └─► Commit Transaction                  │
│                                             │
│  5. Return Sale with relationships          │
└─────────────────────────────────────────────┘
```

---

## Pricing & Quantity Resolution

```
Sale Item
    │
    ├─ Has variant?
    │      ├─ YES → Use variant.price
    │      └─ NO  → Use item.price
    │
    ├─ Has variant?
    │      ├─ YES → Use variant.cost
    │      └─ NO  → Use item.cost
    │
    └─ Has variant?
           ├─ YES → Use variant.ingredient_quantity ?? item.ingredient_quantity
           └─ NO  → Use item.ingredient_quantity
```

---

## Data Flow: Create Sale with Stock Deduction

```
Client Request (JSON)
{
  "items": [{"item_id": 1, "variant_id": 2, "quantity": 3}],
  "payments": [{"amount": 450, "method": "cash"}]
}
    │
    ▼
SaleController
    │
    ▼
SaleService
    │
    ├─► Load Item (id: 1)
    │   └─► ingredient_id: 5, ingredient_quantity: null
    │
    ├─► Load Variant (id: 2)
    │   └─► ingredient_quantity: 8, price: 150, cost: 80
    │
    ├─► Calculate Usage
    │   └─► 8 (from variant) × 3 (quantity) = 24 pcs needed
    │
    ├─► Check Stock
    │   └─► Stock.ingredient_id(5).quantity = 100 ✓
    │
    ├─► DB Transaction Start
    │
    ├─► Create Sale
    │   └─► invoice_no: INV-000001, total_amount: 450
    │
    ├─► Create SaleItem
    │   └─► quantity: 3, unit_price: 150, total: 450
    │
    ├─► Create Payment
    │   └─► amount: 450, method: cash
    │
    ├─► Deduct Stock (LOCKED)
    │   └─► Stock.quantity: 100 → 76 (100 - 24)
    │
    └─► Commit & Return Sale
```

---

## Transaction Safety Pattern

```
┌──────────────────────────────────────────┐
│      DB::transaction(function() {        │
│                                          │
│   ┌────────────────────────────────┐    │
│   │ SELECT ... FOR UPDATE           │    │
│   │ (Row-level lock acquired)      │    │
│   └────────────────────────────────┘    │
│           │                              │
│           ▼                              │
│   ┌────────────────────────────────┐    │
│   │ Perform calculations            │    │
│   │ Validate business rules         │    │
│   └────────────────────────────────┘    │
│           │                              │
│           ▼                              │
│   ┌────────────────────────────────┐    │
│   │ Update stock                    │    │
│   │ Create/update records           │    │
│   └────────────────────────────────┘    │
│           │                              │
│           ▼                              │
│   ┌────────────────────────────────┐    │
│   │ COMMIT                          │    │
│   │ (Lock released)                 │    │
│   └────────────────────────────────┘    │
│                                          │
└──────────────────────────────────────────┘

Benefits:
✓ Prevents race conditions
✓ Ensures data consistency
✓ Atomic operations
✓ Rollback on error
```

---

## Key Design Decisions

### 1. NO SKU Field
```
❌ items.sku
❌ item_variants.sku
✓  Direct item/variant identification by ID
```

### 2. NO stock_movements Table
```
❌ stock_movements (audit trail)
✓  Direct updates to stocks table
✓  Simpler, faster operations
✓  Less storage overhead
```

### 3. Service Layer
```
✓ Business logic separated from controllers
✓ Reusable across different controllers
✓ Easier to test
✓ Transaction management centralized
```

### 4. Variant Override Pattern
```
Item:
  price: 100
  ingredient_quantity: 10

Variant:
  price: 150 (overrides)
  ingredient_quantity: 8 (overrides)

Result: Use variant values when present
```

---

This architecture ensures:
- ✅ Transaction safety
- ✅ Clean separation of concerns
- ✅ Easy maintenance
- ✅ Scalability
- ✅ Data integrity
