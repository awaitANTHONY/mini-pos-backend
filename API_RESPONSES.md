# POS API Response Documentation

## Base URL
```
http://mini_pos_backend.test/api/v1
```

## Authentication
- **Method**: Bearer Token (Laravel Sanctum)
- **Header**: `Authorization: Bearer {access_token}`
- **Required Header**: `x-api-key: 2y10JIMbQjXaPQUFdsisud093BlC4hwejuBp9Iaqteytrtr5`

---

## Response Format

All API responses follow this standard format:

### Success Response
```json
{
    "success": true,
    "message": "Optional success message",
    "data": {}
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": "Error details or validation errors object"
}
```

---

## 1. Authentication Endpoints

### POST /signin
**Description**: Authenticate user and get access token

**Request Body**:
```json
{
    "email": "admin@example.com",
    "password": "password"
}
```

**Success Response (200)**:
```json
{
    "success": true,
    "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...",
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin"
    }
}
```

### GET /user
**Description**: Get authenticated user details

**Success Response (200)**:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "role": "admin",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### POST /signout
**Description**: Logout and revoke access token

**Success Response (200)**:
```json
{
    "success": true,
    "message": "Successfully signed out"
}
```

---

## 2. Expenses Endpoints

### GET /pos/expenses
**Description**: Get paginated list of expenses with filters

**Query Parameters**:
- `per_page` (optional): Number of items per page (default: 15)
- `start_date` (optional): Filter from date (format: YYYY-MM-DD)
- `end_date` (optional): Filter to date (format: YYYY-MM-DD)

**Success Response (200)**:
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "total_amount": "500.00",
                "due_amount": "0.00",
                "expense_date": "2024-12-10",
                "description": "Raw ingredients purchase",
                "quantity": "100.0000",
                "unit_price": "5.00",
                "supplier_name": "Supplier ABC",
                "supplier_contact": "+1234567890",
                "note": "Bulk order",
                "created_by": 1,
                "ingredient_id": 1,
                "ingredient": {
                    "id": 1,
                    "name": "Raw Momo",
                    "unit": "pcs",
                    "current_stock": "500.0000"
                },
                "creator": {
                    "id": 1,
                    "name": "Admin User",
                    "email": "admin@example.com"
                }
            }
        ],
        "first_page_url": "http://mini_pos_backend.test/api/v1/pos/expenses?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://mini_pos_backend.test/api/v1/pos/expenses?page=1",
        "next_page_url": null,
        "path": "http://mini_pos_backend.test/api/v1/pos/expenses",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

### GET /pos/expenses/{id}
**Description**: Get single expense details

**Success Response (200)**:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "total_amount": "500.00",
        "due_amount": "0.00",
        "expense_date": "2024-12-10",
        "description": "Raw ingredients purchase",
        "quantity": "100.0000",
        "unit_price": "5.00",
        "supplier_name": "Supplier ABC",
        "supplier_contact": "+1234567890",
        "note": "Bulk order",
        "created_by": 1,
        "ingredient_id": 1,
        "ingredient": {
            "id": 1,
            "name": "Raw Momo",
            "unit": "pcs",
            "current_stock": "500.0000"
        },
        "creator": {
            "id": 1,
            "name": "Admin User",
            "email": "admin@example.com"
        }
    }
}
```

**Error Response (404)**:
```json
{
    "success": false,
    "message": "Expense not found"
}
```

### POST /pos/expenses
**Description**: Create new expense

**Request Body**:
```json
{
    "expense_date": "2024-12-12",
    "total_amount": 500.00,
    "due_amount": 0,
    "ingredient_id": 1,
    "quantity": 100,
    "unit_price": 5.00,
    "supplier_name": "Supplier ABC",
    "supplier_contact": "+1234567890",
    "description": "Raw ingredients purchase",
    "note": "Bulk order"
}
```

**Validation Rules**:
- `expense_date`: required, date
- `total_amount`: required, numeric, min:0
- `ingredient_id`: nullable, exists:ingredients,id
- `quantity`: nullable, numeric, min:0
- `unit_price`: nullable, numeric, min:0
- `supplier_name`: nullable, string, max:191
- `supplier_contact`: nullable, string, max:191
- `description`: nullable, string
- `note`: nullable, string

**Success Response (201)**:
```json
{
    "success": true,
    "message": "Expense created successfully",
    "data": {
        "id": 2,
        "total_amount": "500.00",
        "due_amount": "0.00",
        "expense_date": "2024-12-12",
        "description": "Raw ingredients purchase",
        "quantity": "100.0000",
        "unit_price": "5.00",
        "supplier_name": "Supplier ABC",
        "supplier_contact": "+1234567890",
        "note": "Bulk order",
        "created_by": 1,
        "ingredient_id": 1,
        "ingredient": {...},
        "creator": {...}
    }
}
```

**Validation Error Response (422)**:
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "expense_date": ["The expense date field is required."],
        "total_amount": ["The total amount must be at least 0."]
    }
}
```

### PUT /pos/expenses/{id}
**Description**: Update expense (only within 24 hours of creation)

**Request Body**: Same as POST /pos/expenses

**Success Response (200)**:
```json
{
    "success": true,
    "message": "Expense updated successfully",
    "data": {...}
}
```

**Error Response - Not Found (404)**:
```json
{
    "success": false,
    "message": "Expense not found"
}
```

**Error Response - Time Restriction (403)**:
```json
{
    "success": false,
    "message": "Cannot edit expense after 24 hours"
}
```

---

## 3. Categories Endpoints

### GET /pos/categories
**Description**: Get list of active categories

**Success Response (200)**:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Jhalmuri",
            "slug": "jhalmuri",
            "description": null,
            "image_url": null,
            "status": 1,
            "created_at": "2024-12-10T00:00:00.000000Z",
            "updated_at": "2024-12-10T00:00:00.000000Z"
        },
        {
            "id": 2,
            "title": "Momo",
            "slug": "momo",
            "description": null,
            "image_url": null,
            "status": 1,
            "created_at": "2024-12-10T00:00:00.000000Z",
            "updated_at": "2024-12-10T00:00:00.000000Z"
        }
    ]
}
```

---

## 4. Sales Endpoints

### GET /pos/sales
**Description**: Get paginated list of sales with filters

**Query Parameters**:
- `per_page` (optional): Number of items per page (default: 15)
- `start_date` (optional): Filter from date (format: YYYY-MM-DD)
- `end_date` (optional): Filter to date (format: YYYY-MM-DD)
- `payment_status` (optional): Filter by payment status (paid, partial, unpaid)

**Success Response (200)**:
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "invoice_no": "INV-0001",
                "user_id": 1,
                "total_amount": "180.00",
                "total_cost": "100.00",
                "paid_amount": "180.00",
                "due_amount": "0.00",
                "payment_status": "paid",
                "note": "Table 5",
                "created_at": "2024-12-12T10:30:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "Admin User",
                    "email": "admin@example.com"
                },
                "items": [
                    {
                        "id": 1,
                        "sale_id": 1,
                        "item_id": 5,
                        "variant_id": 1,
                        "quantity": 1,
                        "unit_price": "180.00",
                        "unit_cost": "100.00",
                        "total_price": "180.00",
                        "total_cost": "100.00",
                        "item": {
                            "id": 5,
                            "name": "Chicken Momo",
                            "image_url": null
                        },
                        "variant": {
                            "id": 1,
                            "item_id": 5,
                            "name": "Large (8 pcs)",
                            "price": "180.00"
                        }
                    }
                ],
                "payments": [
                    {
                        "id": 1,
                        "sale_id": 1,
                        "amount": "180.00",
                        "payment_method": "cash",
                        "payment_date": "2024-12-12T10:30:00.000000Z"
                    }
                ]
            }
        ],
        "first_page_url": "http://mini_pos_backend.test/api/v1/pos/sales?page=1",
        "from": 1,
        "last_page": 1,
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

### POST /pos/sales
**Description**: Create new sale

**Request Body**:
```json
{
    "items": [
        {
            "item_id": 5,
            "variant_id": 1,
            "quantity": 2,
            "unit_price": 180.00,
            "total_price": 360.00
        },
        {
            "item_id": 8,
            "variant_id": null,
            "quantity": 1,
            "unit_price": 120.00,
            "total_price": 120.00
        }
    ],
    "payment_amount": 480.00,
    "payment_method": "cash",
    "note": "Table 3"
}
```

**Validation Rules**:
- `items`: required, array, min:1
- `items.*.item_id`: required, exists:items,id
- `items.*.variant_id`: nullable, exists:item_variants,id
- `items.*.quantity`: required, numeric, min:1
- `items.*.unit_price`: required, numeric, min:0
- `items.*.total_price`: required, numeric, min:0
- `payment_amount`: nullable, numeric, min:0
- `payment_method`: nullable, string, in:cash,card,mobile
- `note`: nullable, string

**Success Response (201)**:
```json
{
    "success": true,
    "message": "Sale created successfully",
    "data": {
        "id": 2,
        "invoice_no": "INV-0002",
        "user_id": 1,
        "total_amount": "480.00",
        "total_cost": "266.00",
        "paid_amount": "480.00",
        "due_amount": "0.00",
        "payment_status": "paid",
        "note": "Table 3",
        "created_at": "2024-12-12T11:00:00.000000Z",
        "user": {...},
        "items": [...],
        "payments": [...]
    }
}
```

**Error Response - Server Error (500)**:
```json
{
    "success": false,
    "message": "Failed to create sale",
    "errors": "Insufficient stock for item: Chicken Momo (Large)"
}
```

### PUT /pos/sales/{id}
**Description**: Update sale (only within 24 hours of creation)

**Request Body**:
```json
{
    "items": [
        {
            "item_id": 5,
            "variant_id": 1,
            "quantity": 3,
            "unit_price": 180.00,
            "total_price": 540.00
        }
    ],
    "note": "Updated - Table 5"
}
```

**Validation Rules**: Same as POST (except payment fields are not included)

**Success Response (200)**:
```json
{
    "success": true,
    "message": "Sale updated successfully",
    "data": {...}
}
```

**Error Response - Not Found (404)**:
```json
{
    "success": false,
    "message": "Sale not found"
}
```

**Error Response - Time Restriction (403)**:
```json
{
    "success": false,
    "message": "Cannot edit sale after 24 hours"
}
```

---

## 5. Items Endpoints

### GET /pos/items
**Description**: Get list of items with variants, category, and ingredient details

**Query Parameters**:
- `category_id` (optional): Filter by category ID
- `search` (optional): Search by item name

**Success Response (200)**:
```json
{
    "success": true,
    "data": [
        {
            "id": 5,
            "name": "Chicken Momo",
            "image_url": null,
            "category_id": 2,
            "category": {
                "id": 2,
                "title": "Momo"
            },
            "ingredient_id": 1,
            "ingredient": {
                "id": 1,
                "name": "Raw Momo",
                "unit": "pcs"
            },
            "ingredient_quantity": null,
            "had_variants": 1,
            "price": "120.00",
            "cost": "66.00",
            "status": 1,
            "variants": [
                {
                    "id": 1,
                    "name": "Small (4 pcs)",
                    "price": "120.00",
                    "cost": "66.00",
                    "ingredient_quantity": "4.0000",
                    "is_default": 1
                },
                {
                    "id": 2,
                    "name": "Medium (6 pcs)",
                    "price": "150.00",
                    "cost": "100.00",
                    "ingredient_quantity": "6.0000",
                    "is_default": 0
                },
                {
                    "id": 3,
                    "name": "Large (8 pcs)",
                    "price": "180.00",
                    "cost": "133.00",
                    "ingredient_quantity": "8.0000",
                    "is_default": 0
                }
            ],
            "created_at": "2024-12-10T00:00:00.000000Z",
            "updated_at": "2024-12-10T00:00:00.000000Z"
        },
        {
            "id": 1,
            "name": "Classic Jhalmuri",
            "image_url": null,
            "category_id": 1,
            "category": {
                "id": 1,
                "title": "Jhalmuri"
            },
            "ingredient_id": null,
            "ingredient": null,
            "ingredient_quantity": null,
            "had_variants": 0,
            "price": "50.00",
            "cost": "20.00",
            "status": 1,
            "variants": [],
            "created_at": "2024-12-10T00:00:00.000000Z",
            "updated_at": "2024-12-10T00:00:00.000000Z"
        }
    ]
}
```

---

## HTTP Status Codes

- **200 OK**: Successful GET/PUT request
- **201 Created**: Successful POST request
- **403 Forbidden**: Time restriction (24-hour edit window)
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation error
- **500 Internal Server Error**: Server error

---

## Notes

1. **24-Hour Edit Restriction**: Sales and Expenses can only be edited within 24 hours of their creation. After that, attempts to edit will return a 403 Forbidden response.

2. **Authentication**: All POS endpoints require authentication via Bearer token. Obtain the token from the `/signin` endpoint.

3. **Stock Management**: Creating or updating sales will automatically adjust ingredient stock levels based on item ingredient quantities.

4. **Variants**: Items can have variants (e.g., Small, Medium, Large). When `had_variants = 1`, the variants array will contain available options.

5. **Pagination**: List endpoints return paginated results. Use `per_page` query parameter to control page size.

6. **Date Filters**: Use `start_date` and `end_date` query parameters in format YYYY-MM-DD to filter expenses and sales by date range.

7. **Error Responses**: All error responses include a `success: false` flag and descriptive `message`. Validation errors also include an `errors` object with field-specific error messages.
