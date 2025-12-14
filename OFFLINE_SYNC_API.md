# Offline Sync API Documentation

## Overview
The Sync API enables your mobile app to work offline by storing categories and items locally, then syncing sales and expenses when the device reconnects to the internet.

---

## Architecture

### Offline-First Flow
```
1. App Launch (Online)
   ↓
2. Download Categories & Items → Store Locally
   ↓
3. Work Offline
   ↓
4. Create Sales/Expenses → Store with temp_id
   ↓
5. Reconnect to Internet
   ↓
6. Upload Pending Data → Receive server IDs
   ↓
7. Map temp_id to server_id → Mark as synced
```

### Key Concepts

**Temporary IDs**: Client-generated unique IDs (UUID recommended)
```
temp_id: "550e8400-e29b-41d4-a716-446655440000"
```

**Server IDs**: Database primary keys assigned after sync
```
server_id: 123
```

**Offline Timestamps**: Preserve creation time from offline mode
```
created_at_offline: "2025-12-12T14:30:00Z"
```

---

## API Endpoints

### 1. Download Data for Offline Use

**GET** `/api/v1/sync/download`

Downloads all categories and items to local storage.

**Query Parameters:**
- `last_sync_at` (optional): ISO 8601 timestamp of last sync

**Response:**
```json
{
    "success": true,
    "data": {
        "categories": [
            {
                "id": 1,
                "title": "Momo",
                "description": null,
                "image": "http://...",
                "status": 1,
                "updated_at": "2025-12-12T10:00:00Z"
            }
        ],
        "items": [
            {
                "id": 5,
                "name": "Chicken Fried Momo",
                "category_id": 1,
                "category": {
                    "id": 1,
                    "title": "Momo"
                },
                "ingredient_id": 1,
                "ingredient": {
                    "id": 1,
                    "name": "Chicken Meat",
                    "unit": "kg"
                },
                "had_variants": true,
                "price": null,
                "variants": [
                    {
                        "id": 1,
                        "name": "Small (4pcs)",
                        "price": "140.00",
                        "cost": "70.00",
                        "ingredient_quantity": "0.2000"
                    }
                ]
            }
        ],
        "synced_at": "2025-12-12T15:30:00Z"
    }
}
```

**Mobile App Logic:**
```javascript
// Store in local database (SQLite/Realm/etc)
const syncData = async () => {
    const response = await fetch('/api/v1/sync/download');
    const { data } = await response.json();
    
    await localDB.categories.bulkPut(data.categories);
    await localDB.items.bulkPut(data.items);
    await localDB.settings.set('last_sync_at', data.synced_at);
};
```

---

### 2. Upload Offline Data

**POST** `/api/v1/sync/upload`

Batch upload all pending sales and expenses created offline.

**Request Body:**
```json
{
    "sales": [
        {
            "temp_id": "sale-550e8400-e29b-41d4",
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
            "created_at_offline": "2025-12-12T10:15:30Z"
        }
    ],
    "expenses": [
        {
            "temp_id": "expense-660e9511-f30c-52e5",
            "expense_date": "2025-12-12",
            "total_amount": 5000.00,
            "due_amount": 0,
            "ingredient_id": 1,
            "quantity": 100,
            "unit_price": 50.00,
            "supplier_name": "ABC Supplier",
            "description": "Raw materials",
            "created_at_offline": "2025-12-12T09:30:00Z"
        }
    ]
}
```

**Response:**
```json
{
    "success": true,
    "message": "Sync completed",
    "data": {
        "results": {
            "sales": {
                "success": [
                    {
                        "temp_id": "sale-550e8400-e29b-41d4",
                        "server_id": 15,
                        "invoice_no": "INV-000015"
                    }
                ],
                "failed": []
            },
            "expenses": {
                "success": [
                    {
                        "temp_id": "expense-660e9511-f30c-52e5",
                        "server_id": 8
                    }
                ],
                "failed": []
            }
        },
        "synced_at": "2025-12-12T15:45:00Z",
        "summary": {
            "sales_synced": 1,
            "sales_failed": 0,
            "expenses_synced": 1,
            "expenses_failed": 0
        }
    }
}
```

**Mobile App Logic:**
```javascript
const syncPendingData = async () => {
    // Get all unsynced records
    const pendingSales = await localDB.sales
        .where('synced').equals(false)
        .toArray();
    
    const pendingExpenses = await localDB.expenses
        .where('synced').equals(false)
        .toArray();
    
    // Upload to server
    const response = await fetch('/api/v1/sync/upload', {
        method: 'POST',
        body: JSON.stringify({
            sales: pendingSales,
            expenses: pendingExpenses
        })
    });
    
    const { data } = await response.json();
    
    // Update local records with server IDs
    for (const result of data.results.sales.success) {
        await localDB.sales
            .where('temp_id').equals(result.temp_id)
            .modify({
                server_id: result.server_id,
                invoice_no: result.invoice_no,
                synced: true
            });
    }
    
    for (const result of data.results.expenses.success) {
        await localDB.expenses
            .where('temp_id').equals(result.temp_id)
            .modify({
                server_id: result.server_id,
                synced: true
            });
    }
};
```

---

### 3. Check Sync Status

**GET** `/api/v1/sync/status?last_sync_at=2025-12-12T10:00:00Z`

Check if categories or items have been updated on server.

**Response:**
```json
{
    "success": true,
    "data": {
        "requires_sync": true,
        "categories_updated": false,
        "items_updated": true,
        "checked_at": "2025-12-12T16:00:00Z"
    }
}
```

**Mobile App Logic:**
```javascript
// Periodic check (every 5 minutes when online)
const checkForUpdates = async () => {
    const lastSync = await localDB.settings.get('last_sync_at');
    
    const response = await fetch(
        `/api/v1/sync/status?last_sync_at=${lastSync}`
    );
    
    const { data } = await response.json();
    
    if (data.requires_sync) {
        // Download updated data
        await syncData();
    }
};
```

---

### 4. Update Offline Sale

**PUT** `/api/v1/sync/sales/{temp_id}`

Update a sale that was created offline (within 24 hours).

**Request Body:**
```json
{
    "server_id": 15,
    "items": [
        {
            "item_id": 5,
            "variant_id": 1,
            "quantity": 3,
            "unit_price": 140.00,
            "total_price": 420.00
        }
    ],
    "note": "Updated quantity"
}
```

---

### 5. Update Offline Expense

**PUT** `/api/v1/sync/expenses/{temp_id}`

Update an expense that was created offline (within 24 hours).

---

## Mobile App Implementation Guide

### 1. Local Database Schema

```javascript
// Using Dexie.js (IndexedDB wrapper)
const db = new Dexie('POSOffline');

db.version(1).stores({
    categories: 'id, title, status',
    items: 'id, name, category_id, status',
    sales: '++id, temp_id, server_id, synced, created_at_offline',
    expenses: '++id, temp_id, server_id, synced, created_at_offline',
    settings: 'key'
});
```

### 2. Offline Detection

```javascript
import NetInfo from '@react-native-community/netinfo';

const [isOnline, setIsOnline] = useState(true);

useEffect(() => {
    const unsubscribe = NetInfo.addEventListener(state => {
        setIsOnline(state.isConnected);
        
        if (state.isConnected) {
            // Auto-sync when back online
            syncPendingData();
        }
    });
    
    return () => unsubscribe();
}, []);
```

### 3. Creating Records Offline

```javascript
const createSale = async (saleData) => {
    const tempId = `sale-${uuid.v4()}`;
    
    const sale = {
        temp_id: tempId,
        items: saleData.items,
        payment_amount: saleData.payment_amount,
        payment_method: saleData.payment_method,
        note: saleData.note,
        created_at_offline: new Date().toISOString(),
        synced: false,
        server_id: null
    };
    
    await localDB.sales.add(sale);
    
    if (isOnline) {
        await syncPendingData();
    }
    
    return sale;
};
```

### 4. Sync Queue with Retry

```javascript
const syncWithRetry = async (maxRetries = 3) => {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            await syncPendingData();
            return true;
        } catch (error) {
            if (attempt === maxRetries) {
                // Show error to user
                Alert.alert('Sync Failed', 'Will retry when online');
                return false;
            }
            // Wait before retry (exponential backoff)
            await new Promise(resolve => 
                setTimeout(resolve, 1000 * Math.pow(2, attempt))
            );
        }
    }
};
```

### 5. Conflict Resolution

```javascript
// If server rejects due to stock issues
const handleSyncConflict = async (failedSale) => {
    // Option 1: Show dialog to user
    Alert.alert(
        'Sync Conflict',
        `Sale ${failedSale.temp_id} failed: Insufficient stock`,
        [
            { text: 'Retry', onPress: () => syncPendingData() },
            { text: 'Skip', onPress: () => markAsSkipped(failedSale.temp_id) }
        ]
    );
};
```

---

## Best Practices

### 1. Initial Setup
- Download categories & items on first app launch
- Store `last_sync_at` timestamp
- Set up background sync

### 2. Data Integrity
- Use UUIDs for temp_ids
- Preserve offline timestamps
- Handle failed syncs gracefully

### 3. Performance
- Batch uploads (max 50 records per request)
- Implement retry with exponential backoff
- Show sync progress to users

### 4. User Experience
- Show "Offline Mode" indicator
- Display pending sync count
- Allow manual sync trigger
- Cache images locally

### 5. Security
- Store access token securely
- Validate data before upload
- Handle authentication expiry

---

## Testing Scenarios

### Scenario 1: Create Sale Offline
1. Turn off internet
2. Create sale → Generates temp_id
3. Sale stored locally with `synced: false`
4. Turn on internet
5. Auto-sync uploads sale
6. Local record updated with server_id

### Scenario 2: Edit Before Sync
1. Create sale offline (temp_id: "A")
2. Edit sale (still temp_id: "A")
3. Sync → Server creates record
4. Local map: temp_id "A" → server_id 15

### Scenario 3: Conflict Resolution
1. Create sale with item X (10 pcs) offline
2. Another device sells item X (stock becomes 5)
3. Sync fails: "Insufficient stock"
4. Show error, allow user to adjust quantity

---

## Error Handling

### Common Errors

**Validation Error (422)**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "sales.0.items.0.item_id": ["Item not found"]
    }
}
```

**Stock Error (500)**
```json
{
    "success": false,
    "message": "Failed to create sale",
    "errors": "Insufficient stock for item: Chicken Momo"
}
```

**Authentication Error (401)**
```json
{
    "success": false,
    "message": "Unauthenticated"
}
```

---

## Postman Collection

See `postman_collection_sync.json` for complete API examples with:
- Auto access token management
- Sample offline data
- Batch sync examples
- Error scenarios

---

## Next Steps

1. ✅ Backend API implemented
2. 📱 Implement mobile app with local storage
3. 🔄 Add background sync service
4. 🧪 Test offline scenarios
5. 📊 Add sync monitoring dashboard
