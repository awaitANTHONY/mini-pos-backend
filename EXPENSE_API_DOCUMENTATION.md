# Expense API Documentation

Complete documentation for all expense-related API endpoints.

---

## Table of Contents
1. [Get Expenses List](#1-get-expenses-list)
2. [Get Expense Details](#2-get-expense-details)
3. [Create Expense](#3-create-expense)
4. [Update Expense](#4-update-expense)

---

## 1. Get Expenses List

Retrieve a paginated list of expenses with optional date range filtering.

### Endpoint
```
GET /api/v1/pos/expenses
```

### Headers
```
Accept: application/json
Authorization: Bearer {access_token}
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | string | No | Filter from date (YYYY-MM-DD) |
| `end_date` | string | No | Filter to date (YYYY-MM-DD) |
| `per_page` | integer | No | Items per page (default: 15) |
| `page` | integer | No | Page number |

### Example Request
```bash
GET /api/v1/pos/expenses?start_date=2025-12-01&end_date=2025-12-31&per_page=20
```

### Success Response (200 OK)
```json
{
    "success": true,
    "expenses": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "expense_date": "2025-12-14",
                "total_amount": "5000.00",
                "due_amount": "0.00",
                "ingredient_id": 1,
                "quantity": "100.00",
                "unit_price": "50.00",
                "supplier_name": "ABC Supplier",
                "supplier_contact": "9876543210",
                "description": "Chicken meat purchase",
                "note": "Paid in full",
                "created_by": 1,
                "created_at": "2025-12-14T10:30:00.000000Z",
                "updated_at": "2025-12-14T10:30:00.000000Z",
                "ingredient": {
                    "id": 1,
                    "name": "Chicken",
                    "unit": "kg"
                },
                "creator": {
                    "id": 1,
                    "name": "Admin User",
                    "email": "admin@example.com"
                }
            }
        ],
        "first_page_url": "http://domain.com/api/v1/pos/expenses?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://domain.com/api/v1/pos/expenses?page=1",
        "next_page_url": null,
        "path": "http://domain.com/api/v1/pos/expenses",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

---

## 2. Get Expense Details

Retrieve detailed information about a specific expense.

### Endpoint
```
GET /api/v1/pos/expenses/{id}
```

### Headers
```
Accept: application/json
Authorization: Bearer {access_token}
```

### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Expense ID |

### Example Request
```bash
GET /api/v1/pos/expenses/1
```

### Success Response (200 OK)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "expense_date": "2025-12-14",
        "total_amount": "5000.00",
        "due_amount": "0.00",
        "ingredient_id": 1,
        "quantity": "100.00",
        "unit_price": "50.00",
        "supplier_name": "ABC Supplier",
        "supplier_contact": "9876543210",
        "description": "Chicken meat purchase",
        "note": "Paid in full",
        "created_by": 1,
        "created_at": "2025-12-14T10:30:00.000000Z",
        "updated_at": "2025-12-14T10:30:00.000000Z",
        "ingredient": {
            "id": 1,
            "name": "Chicken",
            "unit": "kg",
            "quantity": "100.00"
        },
        "creator": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        }
    }
}
```

### Error Response (404 Not Found)
```json
{
    "success": false,
    "message": "Expense not found"
}
```

---

## 3. Create Expense

Create a new expense record.

### Endpoint
```
POST /api/v1/pos/expenses
```

### Headers
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {access_token}
```

### Request Body
```json
{
    "expense_date": "2025-12-14",
    "total_amount": 5000.00,
    "due_amount": 0,
    "ingredient_id": 1,
    "quantity": 100,
    "unit_price": 50.00,
    "supplier_name": "ABC Supplier",
    "supplier_contact": "9876543210",
    "description": "Chicken meat purchase",
    "note": "Paid in full"
}
```

### Field Details
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `expense_date` | string | **Yes** | Valid date (YYYY-MM-DD) | Date of expense |
| `total_amount` | number | **Yes** | Numeric, min: 0 | Total expense amount |
| `due_amount` | number | No | Numeric, min: 0 | Outstanding amount (default: 0) |
| `ingredient_id` | integer | No | Must exist in ingredients table | Link to ingredient if buying raw materials |
| `quantity` | number | No | Numeric, min: 0 | Quantity purchased |
| `unit_price` | number | No | Numeric, min: 0 | Price per unit |
| `supplier_name` | string | No | Max: 191 characters | Supplier name |
| `supplier_contact` | string | No | Max: 191 characters | Supplier contact number |
| `description` | string | No | - | Expense description |
| `note` | string | No | - | Additional notes |

**Note:** `created_by` is automatically set to the authenticated user's ID.

### Success Response (201 Created)
```json
{
    "success": true,
    "message": "Expense created successfully",
    "data": {
        "id": 1,
        "expense_date": "2025-12-14",
        "total_amount": "5000.00",
        "due_amount": "0.00",
        "ingredient_id": 1,
        "quantity": "100.00",
        "unit_price": "50.00",
        "supplier_name": "ABC Supplier",
        "supplier_contact": "9876543210",
        "description": "Chicken meat purchase",
        "note": "Paid in full",
        "created_by": 1,
        "created_at": "2025-12-14T10:30:00.000000Z",
        "updated_at": "2025-12-14T10:30:00.000000Z",
        "ingredient": {
            "id": 1,
            "name": "Chicken",
            "unit": "kg"
        },
        "creator": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        }
    }
}
```

### Error Response (422 Validation Error)
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "expense_date": [
            "The expense date field is required."
        ],
        "total_amount": [
            "The total amount field is required."
        ]
    }
}
```

### Error Response (500 Server Error)
```json
{
    "success": false,
    "message": "Failed to create expense",
    "errors": "Error details..."
}
```

---

## 4. Update Expense

Update an existing expense. **Time restricted** - can only edit within configured hours (default: 24 hours).

### Endpoint
```
PUT /api/v1/pos/expenses/{id}
```

### Headers
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {access_token}
```

### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Expense ID to update |

### Request Body
```json
{
    "expense_date": "2025-12-14",
    "total_amount": 5500.00,
    "due_amount": 0,
    "ingredient_id": 1,
    "quantity": 110,
    "unit_price": 50.00,
    "supplier_name": "ABC Supplier",
    "supplier_contact": "9876543210",
    "description": "Chicken meat purchase - Updated quantity",
    "note": "Paid in full"
}
```

### Field Details
Same as [Create Expense](#field-details) - all fields have the same validation rules.

### Success Response (200 OK)
```json
{
    "success": true,
    "message": "Expense updated successfully",
    "data": {
        "id": 1,
        "expense_date": "2025-12-14",
        "total_amount": "5500.00",
        "due_amount": "0.00",
        "ingredient_id": 1,
        "quantity": "110.00",
        "unit_price": "50.00",
        "supplier_name": "ABC Supplier",
        "supplier_contact": "9876543210",
        "description": "Chicken meat purchase - Updated quantity",
        "note": "Paid in full",
        "created_by": 1,
        "created_at": "2025-12-14T10:30:00.000000Z",
        "updated_at": "2025-12-14T11:30:00.000000Z",
        "ingredient": {
            "id": 1,
            "name": "Chicken",
            "unit": "kg"
        },
        "creator": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        }
    }
}
```

### Error Response (403 Forbidden - Time Limit Exceeded)
```json
{
    "success": false,
    "message": "Cannot edit expense after 24 hours"
}
```

**Note:** The time limit is configurable via the `update_time` setting in the database. Default is 24 hours. The error message dynamically shows the configured limit.

### Error Response (404 Not Found)
```json
{
    "success": false,
    "message": "Expense not found"
}
```

### Error Response (422 Validation Error)
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "total_amount": [
            "The total amount field is required."
        ]
    }
}
```

### Error Response (500 Server Error)
```json
{
    "success": false,
    "message": "Failed to update expense",
    "errors": "Error details..."
}
```

---

## Important Notes

### Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Time Restriction for Updates
- Expenses can only be edited within a configurable time window after creation
- Default: 24 hours (configurable via `update_time` setting)
- To change the time limit, update the `update_time` value in the settings table
- Example: To allow 48-hour edits, set `update_time = 48`

### Response Keys
- **List endpoint** returns data in `"expenses"` key (with pagination)
- **Details/Create/Update endpoints** return data in `"data"` key (single object)

### Automatic Fields
- `created_by` is automatically set to the authenticated user's ID
- `created_at` and `updated_at` are automatically managed by Laravel

### Relationships
All responses include:
- `ingredient` - Full ingredient details if `ingredient_id` is set
- `creator` - User who created the expense

### Date Format
- All dates should be in `YYYY-MM-DD` format
- Timestamps are returned in ISO 8601 format

### Pagination
The list endpoint supports Laravel's standard pagination with:
- `current_page`
- `per_page`
- `total`
- `first_page_url`, `last_page_url`, `next_page_url`, `prev_page_url`

---

## Example Postman Collection

Import these examples into Postman:

### Environment Variables
```json
{
    "base_url": "http://your-domain.com",
    "access_token": "YOUR_ACCESS_TOKEN_HERE"
}
```

### Test Scenarios

**Scenario 1: Create and View Expense**
1. POST `/api/v1/pos/expenses` - Create expense
2. GET `/api/v1/pos/expenses/{id}` - View created expense
3. GET `/api/v1/pos/expenses` - List all expenses

**Scenario 2: Update Within Time Limit**
1. Create expense (note the timestamp)
2. Immediately update with PUT `/api/v1/pos/expenses/{id}` - Should succeed

**Scenario 3: Time Limit Exceeded**
1. Try to update an expense older than 24 hours - Should return 403

**Scenario 4: Filter by Date Range**
1. GET `/api/v1/pos/expenses?start_date=2025-12-01&end_date=2025-12-31`

---

## Status Codes Summary

| Code | Description |
|------|-------------|
| 200 | Success (GET, PUT) |
| 201 | Created (POST) |
| 403 | Forbidden (Time limit exceeded) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

**Last Updated:** December 15, 2025
