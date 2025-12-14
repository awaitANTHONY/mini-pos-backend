# Mobile App Development Guide - POS System

## Project Overview

Build a **Flutter/React Native** Point of Sale (POS) mobile application with **offline-first architecture** that syncs with Laravel backend API.

---

## 🎯 App Requirements

### Core Features
1. **Authentication** - Login/Logout with token management
2. **Categories** - View menu categories
3. **Items/Products** - Browse items with variants and prices
4. **Sales Management** - Create and edit sales (with time restriction)
5. **Expense Management** - Create and edit expenses (with time restriction)
6. **Offline Mode** - Full functionality without internet
7. **Auto Sync** - Background synchronization when online

### Technical Requirements
- Offline-first architecture with local database (SQLite)
- RESTful API integration
- Token-based authentication (Sanctum)
- Background sync service
- Conflict resolution
- Image caching
- State management (GetX/Provider/BLoC)

---

## 📱 App Architecture

```
┌─────────────────────────────────────────┐
│           Mobile Application            │
├─────────────────────────────────────────┤
│  UI Layer (Screens & Widgets)          │
│  - Login Screen                         │
│  - Categories Grid                      │
│  - Items List/Grid                      │
│  - POS/Cart Screen                      │
│  - Sales List                           │
│  - Expense Form/List                    │
│  - Sync Status Screen                   │
├─────────────────────────────────────────┤
│  Controllers (Business Logic)           │
│  - AuthController                       │
│  - CategoryController                   │
│  - ItemController                       │
│  - SaleController                       │
│  - ExpenseController                    │
│  - SyncController                       │
├─────────────────────────────────────────┤
│  Services Layer                         │
│  - ApiService (HTTP Calls)             │
│  - SyncService (Background Sync)       │
│  - DatabaseService (Local Storage)     │
│  - ConnectivityService (Online Check)  │
├─────────────────────────────────────────┤
│  Data Layer                             │
│  - Models (Category, Item, Sale, etc)  │
│  - Repositories (Data Sources)         │
│  - Local Database (SQLite)             │
└─────────────────────────────────────────┘
```

---

## 🗄️ Local Database Schema

### Tables Needed

```sql
-- Categories Table
CREATE TABLE categories (
    id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    image TEXT,
    status INTEGER DEFAULT 1,
    updated_at TEXT,
    synced_at TEXT
);

-- Items Table
CREATE TABLE items (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    image_url TEXT,
    category_id INTEGER,
    ingredient_id INTEGER,
    ingredient_quantity REAL,
    had_variants INTEGER DEFAULT 0,
    price REAL,
    cost REAL,
    status INTEGER DEFAULT 1,
    updated_at TEXT,
    synced_at TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Item Variants Table
CREATE TABLE item_variants (
    id INTEGER PRIMARY KEY,
    item_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    cost REAL,
    ingredient_quantity REAL,
    is_default INTEGER DEFAULT 0,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Sales Table
CREATE TABLE sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    temp_id TEXT UNIQUE NOT NULL,
    server_id INTEGER,
    invoice_no TEXT,
    subtotal REAL NOT NULL,
    tax_amount REAL DEFAULT 0,
    discount_amount REAL DEFAULT 0,
    total_amount REAL NOT NULL,
    payment_status TEXT DEFAULT 'paid',
    note TEXT,
    user_id INTEGER,
    created_at_offline TEXT NOT NULL,
    updated_at TEXT,
    synced INTEGER DEFAULT 0,
    synced_at TEXT
);

-- Sale Items Table
CREATE TABLE sale_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_temp_id TEXT NOT NULL,
    item_id INTEGER NOT NULL,
    variant_id INTEGER,
    quantity REAL NOT NULL,
    unit_price REAL NOT NULL,
    total_price REAL NOT NULL,
    FOREIGN KEY (sale_temp_id) REFERENCES sales(temp_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (variant_id) REFERENCES item_variants(id)
);

-- Expenses Table
CREATE TABLE expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    temp_id TEXT UNIQUE NOT NULL,
    server_id INTEGER,
    expense_date TEXT NOT NULL,
    total_amount REAL NOT NULL,
    due_amount REAL DEFAULT 0,
    ingredient_id INTEGER,
    quantity REAL,
    unit_price REAL,
    supplier_name TEXT,
    supplier_contact TEXT,
    description TEXT,
    note TEXT,
    created_by INTEGER,
    created_at_offline TEXT NOT NULL,
    updated_at TEXT,
    synced INTEGER DEFAULT 0,
    synced_at TEXT
);

-- Sync Queue Table (tracks pending operations)
CREATE TABLE sync_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type TEXT NOT NULL, -- 'sale' or 'expense'
    entity_temp_id TEXT NOT NULL,
    operation TEXT NOT NULL, -- 'create' or 'update'
    retry_count INTEGER DEFAULT 0,
    last_error TEXT,
    created_at TEXT NOT NULL
);

-- App Settings Table
CREATE TABLE app_settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    updated_at TEXT
);
```

---

## 📦 Data Models Structure

### 1. Category Model

**Purpose**: Store menu categories from API

**Fields**:
- `id` (int) - Primary key from server
- `title` (String) - Category name (e.g., "Momo", "Drinks")
- `description` (String?) - Optional description
- `image` (String?) - Image URL
- `status` (int) - Active status (1 = active, 0 = inactive)
- `updated_at` (DateTime?) - Last update timestamp

**Methods Required**:
- `fromJson()` - Parse API response to model
- `toMap()` - Convert to SQLite map for storage
- `fromMap()` - Read from SQLite to model

**API Response Example**:
```json
{
  "id": 1,
  "title": "Momo",
  "description": null,
  "image": "http://domain.com/uploads/momo.jpg",
  "status": 1,
  "updated_at": "2025-12-14T10:00:00.000000Z"
}
```

### 2. Item Model with Variants

**Purpose**: Store menu items with different size/variant options

**ItemVariant Fields**:
- `id` (int) - Variant ID
- `item_id` (int) - Parent item reference
- `name` (String) - Variant name (e.g., "Small (4pcs)", "Large (8pcs)")
- `price` (double) - Variant price
- `cost` (double?) - Cost price
- `ingredient_quantity` (double?) - Raw material quantity used
- `is_default` (bool) - Default selection

**Item Fields**:
- `id` (int) - Item ID
- `name` (String) - Item name (e.g., "Chicken Fried Momo")
- `image_url` (String?) - Product image
- `category_id` (int) - Category reference
- `category` (Category?) - Nested category object
- `ingredient_id` (int?) - Ingredient reference
- `had_variants` (bool) - Has size options or not
- `price` (double?) - Base price (if no variants)
- `cost` (double?) - Cost price
- `status` (int) - Active/inactive
- `variants` (List<ItemVariant>) - Array of variants
- `updated_at` (DateTime?) - Last update

**Key Methods**:
- `fromJson()` - Parse API with nested variants array
- `toMap()` - Save to SQLite (variants in separate table)
- `getPrice(variantId)` - Calculate price based on variant selection

**API Response Example**:
```json
{
  "id": 5,
  "name": "Chicken Fried Momo",
  "category_id": 1,
  "had_variants": true,
  "variants": [
    {
      "id": 1,
      "name": "Small (4pcs)",
      "price": "140.00",
      "is_default": true
    },
    {
      "id": 2,
      "name": "Large (8pcs)",
      "price": "250.00",
      "is_default": false
    }
  ]
}
```

### 3. Sale Model (Most Important for Offline)

**Purpose**: Track sales transactions with offline support

**SaleItem Fields** (Individual line items):
- `id` (int?) - Local database ID
- `sale_temp_id` (String) - Links to parent sale
- `item_id` (int) - Product reference
- `variant_id` (int?) - Size/variant selection
- `quantity` (double) - Quantity sold
- `unit_price` (double) - Price per unit
- `total_price` (double) - Line total (quantity × unit_price)
- `item` (Item?) - Nested item object for display
- `variant` (ItemVariant?) - Nested variant for display

**Sale Fields**:
- `id` (int?) - Local SQLite auto-increment ID
- `temp_id` (String) **CRITICAL** - Client-generated unique ID (e.g., "sale-1702567890123")
- `server_id` (int?) - Server database ID (received after sync)
- `invoice_no` (String?) - Invoice number from server (e.g., "INV-000015")
- `subtotal` (double) - Sum of all items
- `tax_amount` (double) - Tax if applicable
- `discount_amount` (double) - Discount if applicable
- `total_amount` (double) - Final amount
- `payment_status` (String) - "paid", "pending", "partial"
- `note` (String?) - Additional notes
- `user_id` (int?) - Cashier ID
- `created_at_offline` (DateTime) **CRITICAL** - Original creation timestamp
- `updated_at` (DateTime?) - Last modification
- `synced` (bool) - Sync status flag
- `synced_at` (DateTime?) - When synced to server
- `items` (List<SaleItem>) - Array of sale items

**Key Methods**:
- `fromJson()` - Parse API response after sync
- `toMap()` - Save to local SQLite
- `toApiJson()` - Format for sync upload
- `isEditable(updateTimeHours)` - Check if within edit window

**Offline Workflow**:
1. **Create Offline**: Generate `temp_id = "sale-{timestamp}"`, store locally with `synced = false`
2. **Sync to Server**: Upload via `/api/v1/sync/upload`, receive `server_id` and `invoice_no`
3. **Update Local**: Map `temp_id` → `server_id`, set `synced = true`
4. **Edit**: Use `temp_id` to find record, check `isEditable()`, sync changes

**API Upload Format**:
```json
{
  "temp_id": "sale-1702567890123",
  "items": [
    {
      "item_id": 5,
      "variant_id": 1,
      "quantity": 2,
      "unit_price": 140.00,
      "total_price": 280.00
    }
  ],
  "payment_amount": 280.00,
  "payment_method": "cash",
  "note": "Table 3",
  "created_at_offline": "2025-12-14T10:30:00Z"
}
```

**API Response After Sync**:
```json
{
  "temp_id": "sale-1702567890123",
  "server_id": 15,
  "invoice_no": "INV-000015"
}
```

### 4. Expense Model

**Purpose**: Track business expenses offline (inventory purchases, bills, etc.)

**Fields**:
- `id` (int?) - Local database ID
- `temp_id` (String) **CRITICAL** - Client-generated ID (e.g., "expense-1702567890123")
- `server_id` (int?) - Server ID after sync
- `expense_date` (DateTime) - Date of expense
- `total_amount` (double) - Total expense amount
- `due_amount` (double) - Remaining unpaid amount
- `ingredient_id` (int?) - If buying raw materials
- `quantity` (double?) - Quantity purchased
- `unit_price` (double?) - Price per unit
- `supplier_name` (String?) - Supplier name
- `supplier_contact` (String?) - Supplier phone/contact
- `description` (String?) - Expense description
- `note` (String?) - Additional notes
- `created_by` (int?) - User ID who created
- `created_at_offline` (DateTime) **CRITICAL** - Original creation time
- `updated_at` (DateTime?) - Last update
- `synced` (bool) - Sync status
- `synced_at` (DateTime?) - Sync timestamp

**Key Methods**:
- `fromJson()` - Parse API response
- `toMap()` - Save to SQLite
- `toApiJson()` - Format for upload
- `isEditable(updateTimeHours)` - Check edit eligibility

**Offline Workflow**: Same as Sale (temp_id → server_id mapping)

**API Upload Format**:
```json
{
  "temp_id": "expense-1702567890123",
  "expense_date": "2025-12-14",
  "total_amount": 5000.00,
  "due_amount": 0,
  "ingredient_id": 1,
  "quantity": 100,
  "unit_price": 50.00,
  "supplier_name": "ABC Supplier",
  "description": "Chicken meat purchase",
  "created_at_offline": "2025-12-14T09:30:00Z"
}
```

---

## 🔌 Services Layer

### 1. API Service

**Purpose**: Handle all HTTP requests to Laravel backend

**Responsibilities**:
- Store and inject Bearer token in headers
- Make GET, POST, PUT requests
- Handle API responses (success/error)
- Parse validation errors
- Throw custom exceptions

**Key Methods**:
- `setToken(token)` - Save access token after login
- `get(endpoint)` - GET requests (categories, items, etc.)
- `post(endpoint, data)` - POST requests (login, create sale)
- `put(endpoint, data)` - PUT requests (update sale/expense)
- `_handleResponse()` - Parse response, extract errors

**Error Handling**:
- Check `response.statusCode` (200-299 = success)
- Extract error from `data['message']` or `data['errors']`
- Support both Map and String error formats
- Throw `ApiException` with message and status code

**Headers Required**:
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {access_token}
```

---

### 2. Database Service

**Purpose**: Manage local SQLite database operations

**Responsibilities**:
- Create database and tables on first run
- CRUD operations for all entities
- Save/retrieve app settings
- Query with filters (synced/unsynced, date ranges)
- Batch operations for sync

**Key Methods**:
- `init()` - Initialize database, create tables
- `saveCategories(List<Category>)` - Bulk insert/update
- `getCategories()` - Retrieve all active categories
- `saveItems(List<Item>)` - Save items with variants
- `getItems({categoryId, search})` - Query items with filters
- `saveSale(Sale)` - Insert sale with items
- `getSales({syncedOnly, dateRange})` - Query sales
- `updateSaleServerId(tempId, serverId, invoiceNo)` - Map temp to server ID
- `saveExpense(Expense)` - Insert expense
- `getExpenses({syncedOnly})` - Query expenses
- `saveSetting(key, value)` - Store key-value settings
- `getSetting(key)` - Retrieve setting

**Important Queries**:
- Get unsynced records: `WHERE synced = 0`
- Get items by category: `WHERE category_id = ?`
- Search items: `WHERE name LIKE '%?%'`

### 3. Sync Service

**Purpose**: Background synchronization logic

**Responsibilities**:
- Detect online/offline status
- Manage sync queue
- Retry failed syncs with exponential backoff
- Track pending sync count
- Schedule periodic syncs

**Key Methods**:
- `addToQueue(entityType, tempId, operation)` - Add to sync queue
- `getPendingCount()` - Count unsynced records
- `syncNow()` - Trigger immediate sync
- `clearSyncedItems()` - Remove completed queue items
- `retryFailed()` - Retry failed syncs

**Sync Queue Logic**:
- Each unsynced sale/expense added to queue
- Track retry count per item
- Store last error message
- Auto-retry on next sync attempt

### 4. Connectivity Service

**Purpose**: Monitor internet connection

**Methods**:
- `isOnline()` - Check current connectivity
- `onConnectivityChanged()` - Stream of connection events
- `checkConnection()` - Ping server to verify

**Usage**: Trigger auto-sync when back online

---

## 🎮 Controller/State Management

### 1. Auth Controller ✅ COMPLETED

**Purpose**: Manage user authentication and session

**Status**: ✅ Already implemented and working

**Features Implemented**:
- Login/logout functionality
- Token management and persistence
- Session validation on app start
- Automatic token injection in API calls
- Secure token storage

**Available Methods**:
- `checkAuthStatus()` - On app start, check saved token
- `login(email, password)` - POST to `/api/v1/signin`
- `getCurrentUser()` - GET `/api/v1/user` to validate token
- `logout()` - Clear token, navigate to login

**Note**: Authentication is complete. Focus now shifts to offline data management and sync.

### 2. Category Controller

**Purpose**: Manage category data with offline-first approach

**State Variables**:
- `categories` - List of all categories
- `isLoading` - Loading state

**Methods**:
- `loadCategories()` - Load from local DB first (instant display), then sync
- `syncCategories()` - GET `/api/v1/pos/categories`, save to SQLite
- `refresh()` - Pull-to-refresh functionality

**Offline-First Strategy**:
1. **On Screen Open**: Load from SQLite immediately (no delay)
2. **Background Sync**: Call API silently, update if changes found
3. **On Error**: Continue showing cached data
4. **Never Block UI**: Always show local data first

**API Endpoint**: `GET /api/v1/pos/categories`

### 3. Item Controller

**Purpose**: Manage menu items with search and filter

**State Variables**:
- `items` - All items from database
- `filteredItems` - Displayed items after filter/search
- `selectedCategoryId` - Current category filter
- `searchQuery` - Search text
- `isLoading` - Loading state

**Methods**:
- `loadItems()` - Load from local DB, then sync
- `syncItems()` - GET `/api/v1/pos/items` (includes variants)
- `filterItems()` - Apply category filter and search
- `setCategory(categoryId)` - Filter by category
- `search(query)` - Search by item name
- `refresh()` - Pull-to-refresh

**Filter Logic**:
1. **Category Filter**: `WHERE category_id = ?`
2. **Search Filter**: `WHERE name LIKE '%?%'` (case-insensitive)
3. **Combined**: Apply both filters together
4. **Reactive**: Auto-update when filter changes

**API Endpoint**: `GET /api/v1/pos/items?category_id={id}&search={query}`

### 4. Sale Controller (Most Complex)

**Purpose**: Handle POS cart and sales with offline support

**State Variables**:
- `sales` - List of all sales (history)
- `cartItems` - Current cart items
- `cartTotal` - Cart total amount
- `isLoading` - Processing state

**Methods**:
- `loadSales()` - Load sales from local DB
- `addToCart(item, variant, quantity)` - Add item to cart
  - Check if item+variant already exists
  - If exists: update quantity
  - If new: add to cart
  - Recalculate total
- `removeFromCart(index)` - Remove cart item
- `updateQuantity(index, quantity)` - Change item quantity
- `calculateTotal()` - Sum all cart item totals
- `createSale({note})` - Create new sale
  - Generate `temp_id = "sale-{timestamp}"`
  - Save to local SQLite with `synced = false`
  - Add to sync queue
  - Try immediate sync if online
  - Clear cart on success
- `editSale(sale, newItems, {note})` - Edit existing sale
  - Check `isEditable()` first
  - Update items and totals
  - Mark as `synced = false`
  - Add to sync queue
- `clearCart()` - Empty cart

**Create Sale Flow**:
1. User adds items to cart
2. Clicks checkout
3. Generate unique `temp_id`
4. Save to local database:
   ```
   INSERT INTO sales (...) VALUES (...)
   INSERT INTO sale_items (...) VALUES (...) -- for each item
   ```
5. Add to sync_queue: `(entity_type='sale', entity_temp_id=temp_id, operation='create')`
6. If online: Trigger immediate sync
7. If offline: Queue for later sync
8. Show success message
9. Clear cart

**Edit Sale Flow**:
1. User selects sale from list
2. Check `isEditable(updateTimeHours)`:
   - Get `update_time` from server settings (default 24 hours)
   - Calculate: `hours_passed = now - created_at_offline`
   - If `hours_passed > update_time`: Show error "Cannot edit after X hours"
3. If editable: Load items into cart
4. User modifies items
5. Save changes: Same as create but with `operation='update'`

**Cart Logic**:
- Each cart item stores: `item_id`, `variant_id`, `quantity`, `unit_price`, `total_price`
- Duplicate check: Match `item_id` AND `variant_id`
- Price calculation: `total_price = unit_price × quantity`
- Cart total: Sum of all `total_price`

### 5. Expense Controller

**Purpose**: Manage business expenses offline

**State Variables**:
- `expenses` - List of all expenses
- `isLoading` - Processing state

**Methods**:
- `loadExpenses()` - Load from local DB
- `createExpense({date, amount, supplier, ...})` - Create new expense
  - Generate `temp_id = "expense-{timestamp}"`
  - Save to SQLite with `synced = false`
  - Add to sync queue
  - Try immediate sync
- `editExpense(expense, {...})` - Edit existing expense
  - Check `isEditable()` first
  - Update record, mark as `synced = false`
  - Add to sync queue

**Create Flow**: Similar to Sale but simpler (no items array)

### 6. Sync Controller (Critical Component)

**Purpose**: Orchestrate all synchronization

**State Variables**:
- `isSyncing` - Currently syncing flag
- `syncStatus` - "Idle", "Syncing...", "Completed", "Failed"
- `pendingCount` - Number of unsynced records
- `lastSyncTime` - Last successful sync timestamp

**Methods**:
- `syncNow()` - Manual sync trigger
  - Download categories & items
  - Upload pending sales & expenses
  - Update sync status
- `downloadData()` - GET `/api/v1/sync/download`
  - Receive categories array and items array
  - Save to local database
  - Update `last_sync_at` timestamp
- `uploadData()` - POST `/api/v1/sync/upload`
  - Get all unsynced sales and expenses
  - Format as arrays
  - Upload in single request
  - Process response (temp_id → server_id mapping)
  - Update local records with server IDs
- `startAutoSync()` - Background timer (every 5 minutes)
  - Check if online
  - If not already syncing: trigger sync
  - Silent sync (no UI blocking)
- `loadSyncStatus()` - Update pending count and last sync time

**Download Flow**:
1. Call GET `/api/v1/sync/download`
2. Receive JSON: `{categories: [...], items: [...], synced_at: "..."}`
3. Delete old categories from SQLite
4. Insert new categories
5. Delete old items and variants
6. Insert new items and variants
7. Save `synced_at` to settings

**Upload Flow**:
1. Query local database: `SELECT * FROM sales WHERE synced = 0`
2. Query local database: `SELECT * FROM expenses WHERE synced = 0`
3. Format as API request:
   ```json
   {
     "sales": [
       {
         "temp_id": "sale-123",
         "items": [...],
         "created_at_offline": "..."
       }
     ],
     "expenses": [
       {
         "temp_id": "expense-456",
         "total_amount": 5000,
         "created_at_offline": "..."
       }
     ]
   }
   ```
4. POST to `/api/v1/sync/upload`
5. Receive response:
   ```json
   {
     "results": {
       "sales": {
         "success": [
           {"temp_id": "sale-123", "server_id": 15, "invoice_no": "INV-000015"}
         ],
         "failed": []
       },
       "expenses": {
         "success": [
           {"temp_id": "expense-456", "server_id": 8}
         ],
         "failed": []
       }
     }
   }
   ```
6. For each success:
   - `UPDATE sales SET server_id = 15, invoice_no = 'INV-000015', synced = 1 WHERE temp_id = 'sale-123'`
   - `UPDATE expenses SET server_id = 8, synced = 1 WHERE temp_id = 'expense-456'`
7. For each failure:
   - Keep in sync queue
   - Store error message
   - Retry on next sync

**Auto-Sync Logic**:
```
Timer (every 5 minutes):
  if isOnline() AND !isSyncing:
    syncNow()
```

**Connectivity Detection**:
- Use connectivity_plus package
- Listen to connection changes
- When back online: Trigger immediate sync

---

## 🔄 Complete Offline Sync Workflow

### Initial App Launch (First Time)

1. **User Opens App**
2. **Login Screen** → Enter credentials
3. **POST `/api/v1/signin`** → Receive access_token
4. **Save token** to local storage
5. **Initial Data Download**:
   - GET `/api/v1/sync/download`
   - Save all categories (21 items)
   - Save all items with variants (50+ items)
   - Save `last_sync_at` timestamp
6. **Navigate to Home** → Show categories and items from local DB

### Working Offline

1. **User Goes Offline** (WiFi off / no data)
2. **App Continues Working** from local database
3. **User Creates Sale**:
   - Add items to cart
   - Click checkout
   - Generate `temp_id = "sale-1702567890123"`
   - Save to SQLite: `INSERT INTO sales (...) VALUES (...)`
   - Save items: `INSERT INTO sale_items (...) VALUES (...)`
   - Mark as `synced = 0`
   - Show success: "Sale created (pending sync)"
   - Badge shows: "1 pending"
4. **User Creates Another Sale**:
   - Same process
   - Badge shows: "2 pending"
5. **User Creates Expense**:
   - Generate `temp_id = "expense-1702567890456"`
   - Save to SQLite
   - Badge shows: "3 pending"

### Coming Back Online

1. **User Connects to WiFi**
2. **App Detects Connection**
3. **Auto-Sync Triggers**:
   - Show notification: "Syncing..."
   - Download latest categories & items (check for updates)
   - Upload pending records:
     ```json
     POST /api/v1/sync/upload
     {
       "sales": [
         {"temp_id": "sale-1702567890123", "items": [...], ...},
         {"temp_id": "sale-1702567891234", "items": [...], ...}
       ],
       "expenses": [
         {"temp_id": "expense-1702567890456", ...}
       ]
     }
     ```
4. **Server Processes**:
   - Creates database records
   - Returns server IDs
5. **App Updates Local Database**:
   - `UPDATE sales SET server_id = 15, synced = 1 WHERE temp_id = 'sale-...'`
   - `UPDATE sales SET server_id = 16, synced = 1 WHERE temp_id = 'sale-...'`
   - `UPDATE expenses SET server_id = 8, synced = 1 WHERE temp_id = 'expense-...'`
6. **Show Success**: "Sync completed. 3 records uploaded."
7. **Badge Clears**: "0 pending"

### Editing After Sync

1. **User Wants to Edit Sale**
2. **App Checks**: `isEditable(updateTimeHours)`
   - Get `update_time` from settings (default 24)
   - Calculate: `now - created_at_offline`
   - If `hours_passed > update_time`: Error "Cannot edit after 24 hours"
3. **If Editable**:
   - Load sale items into cart
   - User modifies
   - Save changes
   - Mark as `synced = 0` again
   - If online: Sync immediately using:
     ```
     PUT /api/v1/sync/sales/{temp_id}
     Body: {server_id: 15, items: [...]}
     ```
   - Server updates existing record

---

## 📊 Sync Status Indicators

### Visual Feedback for Users

**Pending Badge**:
- Show count of unsynced records
- Example: "⏳ 3 pending"
- Update in real-time

**Sync Status Icons**:
- ✅ Synced - Green checkmark
- ⏳ Pending - Orange clock
- ❌ Failed - Red X with retry button
- 🔄 Syncing - Animated spinner

**Last Sync Time**:
- "Just now"
- "5m ago"
- "2h ago"
- "1d ago"

**Network Status**:
- 🟢 Online - Green dot
- 🔴 Offline - Red dot with "Working offline" message

---

## 🧪 Testing Scenarios

### Test 1: Create Sale Offline
1. Turn off WiFi
2. Create sale with 2 items
3. Check local database: `synced = 0`
4. Turn on WiFi
5. Wait 5 seconds (auto-sync)
6. Check database: `synced = 1`, `server_id` populated

### Test 2: Edit Within Time Limit
1. Create sale now
2. Wait 1 hour
3. Edit sale (should work)
4. Check sync status

### Test 3: Edit After Time Limit
1. Create sale
2. Change system time forward 25 hours
3. Try to edit
4. Should show error: "Cannot edit after 24 hours"

### Test 4: Sync Conflict (Stock Issue)
1. Create sale with 100 items offline
2. Another device sells 90 items (stock = 10)
3. Your device syncs
4. Server returns error: "Insufficient stock"
5. App shows: "Sale sync failed: Insufficient stock. Retry?"

### Test 5: Multiple Device Sync
1. Device A creates sale offline
2. Device B creates sale offline
3. Both come online
4. Both sync successfully
5. Each gets unique server_id

---

## 📱 UI Screens Needed

### 1. Login Screen
- Email input
- Password input
- Login button
- Remember me checkbox

### 2. Home/Dashboard
- Category grid (horizontal scroll)
- Item list/grid
- Search bar
- Cart badge
- Sync status indicator

### 3. POS/Cart Screen
- Selected items list
- Quantity controls (+/-)
- Remove item button
- Subtotal display
- Note input
- Checkout button

### 4. Sales List
- Date filter
- Search
- Each sale shows:
  - Invoice number
  - Date & time
  - Total amount
  - Sync status icon
  - Edit button (if editable)

### 5. Sale Detail
- Items list
- Total breakdown
- Payment info
- Edit button
- Print button

### 6. Expense List & Form
- Add expense button
- Date picker
- Amount input
- Supplier fields
- Description
- Save button

### 7. Sync Status Screen
- Pending count
- Last sync time
- Sync now button
- Sync history list
- Failed items with retry

---

## 🚀 Implementation Priority

**✅ COMPLETED**: Authentication
- ✅ Login/logout functionality
- ✅ Token management
- ✅ API service with auth headers
- ✅ Session persistence

**Week 1**: Database Layer
- Setup SQLite database
- Create all tables (categories, items, sales, expenses, sync_queue)
- Implement DatabaseService with CRUD operations
- Test database operations

**Week 2**: Data Models & Download
- Create all models (Category, Item, Sale, Expense)
- Implement fromJson/toMap/fromMap methods
- Build initial data download flow
- Test sync/download endpoint

**Week 3**: Categories & Items UI
- Categories grid display
- Items list/grid with variants
- Search and filter functionality
- Offline-first loading strategy

**Week 4**: POS Module
- Cart functionality (add/remove/update)
- Create sale offline with temp_id
- Sales list screen
- Sale detail screen

**Week 5**: Sync System (Critical)
- Implement SyncService
- Build upload/download logic
- Temp_id → server_id mapping
- Auto-sync timer (5 minutes)
- Connectivity detection

**Week 6**: Expenses & Edit Features
- Expense create/edit forms
- Time restriction validation (update_time setting)
- Edit sale/expense flows
- Sync status indicators

**Week 7**: Testing & Polish
- Test all offline scenarios
- Handle sync conflicts
- Error handling improvements
- UI polish and optimization

---

## 📞 API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/v1/signin` | POST | Login, get token |
| `/api/v1/user` | GET | Get current user |
| `/api/v1/pos/categories` | GET | Get all categories |
| `/api/v1/pos/items` | GET | Get all items with variants |
| `/api/v1/sync/download` | GET | Download categories + items for offline |
| `/api/v1/sync/upload` | POST | Batch upload sales + expenses |
| `/api/v1/sync/status` | GET | Check if sync needed |
| `/api/v1/sync/sales/{temp_id}` | PUT | Update offline sale |
| `/api/v1/sync/expenses/{temp_id}` | PUT | Update offline expense |

---

## ✅ Key Success Factors

1. **Always Load Local Data First** - Never block UI waiting for API
2. **Generate Unique temp_id** - Use timestamp or UUID
3. **Preserve created_at_offline** - Don't lose original timestamps
4. **Handle Sync Failures Gracefully** - Retry with exponential backoff
5. **Show Sync Status Everywhere** - Users need to know what's syncing
6. **Test Offline Scenarios** - Most bugs happen during sync
7. **Validate Time Restrictions** - Check `update_time` setting
8. **Map temp_id → server_id** - Critical for post-sync operations

---

**This guide provides everything needed to build a production-ready offline-first POS mobile app!** 🎉
class ExpenseController extends GetxController {
  final ApiService _apiService = ApiService();
  final DatabaseService _db = DatabaseService();
  final SyncService _syncService = SyncService();
  
  final RxList<Expense> expenses = <Expense>[].obs;
  final RxBool isLoading = false.obs;

  @override
  void onInit() {
    super.onInit();
    loadExpenses();
  }

  Future<void> loadExpenses() async {
    try {
      final localExpenses = await _db.getExpenses();
      expenses.value = localExpenses;
    } catch (e) {
      print('Error loading expenses: $e');
    }
  }

  Future<bool> createExpense({
    required DateTime expenseDate,
    required double totalAmount,
    double dueAmount = 0,
    int? ingredientId,
    double? quantity,
    double? unitPrice,
    String? supplierName,
    String? supplierContact,
    String? description,
    String? note,
  }) async {
    try {
      isLoading.value = true;
      
      // Generate temp ID
      final tempId = 'expense-${DateTime.now().millisecondsSinceEpoch}';
      
      final expense = Expense(
        tempId: tempId,
        expenseDate: expenseDate,
        totalAmount: totalAmount,
        dueAmount: dueAmount,
        ingredientId: ingredientId,
        quantity: quantity,
        unitPrice: unitPrice,
        supplierName: supplierName,
        supplierContact: supplierContact,
        description: description,
        note: note,
        createdAtOffline: DateTime.now(),
      );
      
      // Save to local database
      await _db.saveExpense(expense);
      
      // Add to sync queue
      await _syncService.addToQueue('expense', tempId, 'create');
      
      // Try immediate sync
      await _syncService.syncNow();
      
      await loadExpenses();
      
      Get.snackbar('Success', 'Expense created successfully');
      return true;
    } catch (e) {
      Get.snackbar('Error', e.toString());
      return false;
    } finally {
      isLoading.value = false;
    }
  }

  Future<bool> editExpense(Expense expense, {
    required DateTime expenseDate,
    required double totalAmount,
    double? dueAmount,
    int? ingredientId,
    double? quantity,
    double? unitPrice,
    String? supplierName,
    String? supplierContact,
    String? description,
    String? note,
  }) async {
    try {
      isLoading.value = true;
      
      final updatedExpense = Expense(
        id: expense.id,
        tempId: expense.tempId,
        serverId: expense.serverId,
        expenseDate: expenseDate,
        totalAmount: totalAmount,
        dueAmount: dueAmount ?? expense.dueAmount,
        ingredientId: ingredientId,
        quantity: quantity,
        unitPrice: unitPrice,
        supplierName: supplierName,
        supplierContact: supplierContact,
        description: description,
        note: note,
        createdAtOffline: expense.createdAtOffline,
        updatedAt: DateTime.now(),
        synced: false,
      );
      
      await _db.updateExpense(updatedExpense);
      await _syncService.addToQueue('expense', expense.tempId, 'update');
      await _syncService.syncNow();
      await loadExpenses();
      
      Get.snackbar('Success', 'Expense updated successfully');
      return true;
    } catch (e) {
      Get.snackbar('Error', e.toString());
      return false;
    } finally {
      isLoading.value = false;
    }
  }
}
```

### 6. Sync Controller

```dart
class SyncController extends GetxController {
  final ApiService _apiService = ApiService();
  final DatabaseService _db = DatabaseService();
  final SyncService _syncService = SyncService();
  
  final RxBool isSyncing = false.obs;
  final RxString syncStatus = 'Idle'.obs;
  final RxInt pendingCount = 0.obs;
  final RxString lastSyncTime = ''.obs;

  @override
  void onInit() {
    super.onInit();
    loadSyncStatus();
    startAutoSync();
  }

  Future<void> loadSyncStatus() async {
    pendingCount.value = await _syncService.getPendingCount();
    final lastSync = await _db.getSetting('last_sync_at');
    if (lastSync != null) {
      lastSyncTime.value = _formatSyncTime(DateTime.parse(lastSync));
    }
  }

  // Manual sync trigger
  Future<void> syncNow() async {
    if (isSyncing.value) return;
    
    try {
      isSyncing.value = true;
      syncStatus.value = 'Syncing...';
      
      // Download latest data
      await downloadData();
      
      // Upload pending data
      await uploadData();
      
      syncStatus.value = 'Completed';
      await loadSyncStatus();
      
      Get.snackbar('Success', 'Sync completed successfully');
    } catch (e) {
      syncStatus.value = 'Failed';
      Get.snackbar('Error', 'Sync failed: $e');
    } finally {
      isSyncing.value = false;
    }
  }

  // Download categories and items
  Future<void> downloadData() async {
    try {
      final response = await _apiService.get('sync/download');
      
      if (response['success'] == true) {
        final data = response['data'];
        
        // Save categories
        final categories = (data['categories'] as List)
            .map((json) => Category.fromJson(json))
            .toList();
        await _db.saveCategories(categories);
        
        // Save items with variants
        final items = (data['items'] as List)
            .map((json) => Item.fromJson(json))
            .toList();
        await _db.saveItems(items);
        
        // Save sync timestamp
        await _db.saveSetting('last_sync_at', data['synced_at']);
      }
    } catch (e) {
      print('Download error: $e');
      rethrow;
    }
  }

  // Upload pending sales and expenses
  Future<void> uploadData() async {
    try {
      // Get pending sales
      final pendingSales = await _db.getSales(syncedOnly: false);
      final unsyncedSales = pendingSales.where((s) => !s.synced).toList();
      
      // Get pending expenses
      final pendingExpenses = await _db.getExpenses(syncedOnly: false);
      final unsyncedExpenses = pendingExpenses.where((e) => !e.synced).toList();
      
      if (unsyncedSales.isEmpty && unsyncedExpenses.isEmpty) {
        return;
      }
      
      // Prepare upload data
      final uploadData = {
        'sales': unsyncedSales.map((s) => s.toApiJson()).toList(),
        'expenses': unsyncedExpenses.map((e) => e.toApiJson()).toList(),
      };
      
      final response = await _apiService.post('sync/upload', uploadData);
      
      if (response['success'] == true) {
        final results = response['data']['results'];
        
        // Update sales with server IDs
        for (var result in results['sales']['success']) {
          await _db.updateSaleServerId(
            result['temp_id'],
            result['server_id'],
            result['invoice_no'],
          );
        }
        
        // Update expenses with server IDs
        for (var result in results['expenses']['success']) {
          await _db.updateExpenseServerId(
            result['temp_id'],
            result['server_id'],
          );
        }
        
        // Clear sync queue for successful items
        await _syncService.clearSyncedItems();
      }
    } catch (e) {
      print('Upload error: $e');
      rethrow;
    }
  }

  // Auto sync every 5 minutes when online
  void startAutoSync() {
    Timer.periodic(Duration(minutes: 5), (timer) async {
      if (!isSyncing.value) {
        try {
          await syncNow();
        } catch (e) {
          print('Auto sync failed: $e');
        }
      }
    });
  }

  String _formatSyncTime(DateTime time) {
    final now = DateTime.now();
    final diff = now.difference(time);
    
    if (diff.inMinutes < 1) return 'Just now';
    if (diff.inHours < 1) return '${diff.inMinutes}m ago';
    if (diff.inDays < 1) return '${diff.inHours}h ago';
    return '${diff.inDays}d ago';
  }
}
```

---

## 📋 Implementation Checklist

### Phase 1: ✅ COMPLETED - Authentication
- [x] Create Flutter/React Native project
- [x] Setup folder structure (models, controllers, services, screens)
- [x] Install dependencies (http, sqflite, get/provider, connectivity_plus)
- [x] Create login screen
- [x] Implement AuthController with token management
- [x] Add token persistence
- [x] Create splash screen with auth check
- [x] Add logout functionality
- [x] Create base API service with authentication

### Phase 2: Offline Data Layer (Week 1-2)
- [ ] Create all data models with fromJson/toMap methods
- [ ] Implement DatabaseService for local storage
- [ ] Setup local database with SQLite
- [ ] Create all tables (8 tables total)
- [ ] Create all data models with fromJson/toMap methods
- [ ] Implement DatabaseService for local storage
- [ ] Create category list screen
- [ ] Create item grid/list screen
- [ ] Implement CategoryController
- [ ] Implement ItemController
- [ ] Add search and filter functionality
- [ ] Cache images locally

### Phase 4: Sales Module (Week 4)
### Phase 5: Sales Module (Week 4)
- [ ] Create POS screen with cart
- [ ] Implement add to cart functionality
- [ ] Create checkout screen
- [ ] Implement SaleController with offline support
- [ ] Create sales list screen
- [ ] Add sale edit functionality (with time restriction)
- [ ] Show sync status on each sale

### Phase 5: Expenses Module (Week 5)
- [ ] Create expense list screen
- [ ] Create add expense form
- [ ] Implement ExpenseController
- [ ] Add expense edit functionality
- [ ] Show sync status

- [ ] Implement SyncService
- [ ] Create background sync worker
- [ ] Add connectivity detection
- [ ] Implement retry logic with exponential backoff
- [ ] Create sync status screen
- [ ] Add manual sync button
- [ ] Show pending sync count badge

### Phase 7: Polish & Testing (Week 6-7)
- [ ] Create offline indicator
- [ ] Add pull-to-refresh
- [ ] Test all offline scenarios
- [ ] Test sync conflicts
- [ ] Add analytics/logging
- [ ] Create user guide

---

## 🧪 Testing Scenarios

### 1. Offline Mode
- [ ] Create sale offline → App stores locally
- [ ] Edit sale offline → Updates local record
- [ ] Create expense offline → Stores with temp_id
- [ ] Go online → Auto sync uploads data

### 2. Sync Conflicts
- [ ] Edit sale beyond time limit → Show error
- [ ] Stock insufficient → Show error, allow retry
- [ ] Network failure mid-sync → Retry automatically

### 3. Edge Cases
- [ ] App closed during sync → Resume on next launch
- [ ] Multiple devices same account → Last write wins
- [ ] Large data sync → Show progress indicator

---

## 📦 Dependencies

### Flutter
```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # State Management
  get: ^4.6.5 # or provider/bloc
  
  # Network
  http: ^1.1.0
  connectivity_plus: ^5.0.0
  
  # Local Storage
  sqflite: ^2.3.0
  path: ^1.8.3
  shared_preferences: ^2.2.2
  
  # Utils
  intl: ^0.18.1
  uuid: ^4.1.0
  cached_network_image: ^3.3.0
```

---

## 🚀 Next Steps

1. **Review this guide** with your development team
2. **Setup project structure** following the architecture
3. **Start with Phase 1** - Setup and database
4. **Test each module** before moving to next phase
5. **Deploy beta version** for testing
6. **Iterate based on feedback**

---

## 📞 API Endpoints Reference

All endpoints documented in `API_RESPONSES.md` and `OFFLINE_SYNC_API.md`

**Base URL**: `https://your-domain.com/api/v1`

**Authentication**: Bearer Token in header
```
Authorization: Bearer {access_token}
```

---

**Good luck with your mobile app development!** 🎉
