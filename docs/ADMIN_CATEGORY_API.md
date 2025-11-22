# Admin Category API Documentation

_Last updated: {{DATE}}_

## Overview

This document describes the Admin API endpoints for managing categories. All endpoints are available under the `/api/admin/v1/categories` prefix and require admin authentication.

**Base URL**: `/api/admin/v1/categories`  
**Authentication**: Bearer token (admin users only)  
**Response Format**: JSON with standard wrapper structure

---

## Table of Contents

1. [Category CRUD Operations](#1-category-crud-operations)
2. [Category Statistics](#2-category-statistics)

---

## 1. Category CRUD Operations

### 1.1 Get All Categories

Retrieve a list of all categories with their post counts and associated tags.

**Endpoint**: `GET /api/admin/v1/categories`

**Authentication**: Required (Admin)

**Query Parameters**:
- `search` (optional, string): Search categories by name or description

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Haircare",
      "description": "Hair care and styling posts",
      "is_active": true,
      "posts_count": 150,
      "tags": [
        {
          "id": 5,
          "name": "haircut",
          "slug": "haircut",
          "usage_count": 45
        },
        {
          "id": 8,
          "name": "coloring",
          "slug": "coloring",
          "usage_count": 32
        }
      ]
    },
    {
      "id": 2,
      "name": "Skincare",
      "description": "Skin care and beauty tips",
      "is_active": true,
      "posts_count": 120,
      "tags": []
    }
  ]
}
```

**Response Fields Description**:
- `id`: Category ID
- `name`: Category name
- `description`: Category description
- `is_active`: Whether the category is active
- `posts_count`: Number of posts in this category
- `tags`: Array of top tags used in posts of this category, ordered by usage count
  - `id`: Tag ID
  - `name`: Tag name
  - `slug`: Tag slug
  - `usage_count`: Number of times this tag is used in posts of this category

**Example Requests**:
- Get all categories: `GET /api/admin/v1/categories`
- Search categories: `GET /api/admin/v1/categories?search=hair`

---

### 1.2 Get Category Details

Retrieve detailed information about a specific category including post count and associated tags.

**Endpoint**: `GET /api/admin/v1/categories/{category}`

**Authentication**: Required (Admin)

**URL Parameters**:
- `category` (required, integer): Category ID

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Haircare",
    "slug": "haircare",
    "description": "Hair care and styling posts",
    "color": "#FF5733",
    "icon": "scissors",
    "is_active": true,
    "created_at": "2025-01-15T10:00:00.000000Z",
    "updated_at": "2025-01-15T10:00:00.000000Z",
    "posts_count": 150,
    "tags": [
      {
        "id": 5,
        "name": "haircut",
        "slug": "haircut",
        "usage_count": 45
      },
      {
        "id": 8,
        "name": "coloring",
        "slug": "coloring",
        "usage_count": 32
      },
      {
        "id": 12,
        "name": "styling",
        "slug": "styling",
        "usage_count": 28
      }
    ]
  }
}
```

**Response Fields Description**:
- `id`: Category ID
- `name`: Category name
- `slug`: URL-friendly category identifier (auto-generated from name)
- `description`: Category description
- `color`: Category color code (hex format, optional)
- `icon`: Category icon identifier (optional)
- `is_active`: Whether the category is active
- `posts_count`: Total number of posts in this category
- `tags`: Array of all tags used in posts of this category, ordered by usage count (descending)
- `created_at`: Category creation timestamp
- `updated_at`: Category last update timestamp

**Error Response** (404 Not Found):
```json
{
  "success": false,
  "message": "Resource not found."
}
```

---

### 1.3 Create Category

Create a new category.

**Endpoint**: `POST /api/admin/v1/categories`

**Authentication**: Required (Admin)

**Request Body**:
```json
{
  "name": "Makeup",
  "description": "Makeup tutorials and tips",
  "is_active": true
}
```

**Request Parameters**:
- `name` (required, string, max:100, unique): Category name
- `description` (optional, string): Category description
- `is_active` (optional, boolean, default: true): Whether the category is active

**Response** (201 Created):
```json
{
  "success": true,
  "message": "Category created successfully",
  "data": {
    "id": 3,
    "name": "Makeup",
    "slug": "makeup",
    "description": "Makeup tutorials and tips",
    "color": null,
    "icon": null,
    "is_active": true,
    "created_at": "2025-01-20T14:00:00.000000Z",
    "updated_at": "2025-01-20T14:00:00.000000Z"
  }
}
```

**Error Response** (422 Unprocessable Entity):
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```

**Error Response** (422 Unprocessable Entity) - Duplicate slug:
```json
{
  "success": false,
  "message": "Category slug already exists. Provide a different name."
}
```

**Notes**:
- The `slug` is automatically generated from the `name` field using Laravel's `Str::slug()` helper
- If a slug conflict occurs (even with a different name that generates the same slug), the request will be rejected
- Category names must be unique

---

### 1.4 Update Category

Update an existing category.

**Endpoint**: `PUT /api/admin/v1/categories/{category}`

**Authentication**: Required (Admin)

**URL Parameters**:
- `category` (required, integer): Category ID

**Request Body**:
```json
{
  "name": "Makeup & Beauty",
  "description": "Makeup tutorials, beauty tips, and product reviews",
  "is_active": true
}
```

**Request Parameters** (all optional):
- `name` (string, max:100, unique): Category name
- `description` (string): Category description
- `is_active` (boolean): Whether the category is active

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Category updated successfully",
  "data": {
    "id": 3,
    "name": "Makeup & Beauty",
    "slug": "makeup-beauty",
    "description": "Makeup tutorials, beauty tips, and product reviews",
    "color": null,
    "icon": null,
    "is_active": true,
    "created_at": "2025-01-20T14:00:00.000000Z",
    "updated_at": "2025-01-20T15:30:00.000000Z"
  }
}
```

**Error Response** (422 Unprocessable Entity) - Duplicate slug:
```json
{
  "success": false,
  "message": "Category slug already exists. Provide a different name."
}
```

**Notes**:
- If the `name` is updated, a new `slug` will be automatically generated from the new name
- The slug uniqueness is checked against other categories (excluding the current one being updated)
- Only provided fields will be updated (partial updates are supported)

---

### 1.5 Delete Category

Delete a category.

**Endpoint**: `DELETE /api/admin/v1/categories/{category}`

**Authentication**: Required (Admin)

**URL Parameters**:
- `category` (required, integer): Category ID

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

**Error Response** (404 Not Found):
```json
{
  "success": false,
  "message": "Resource not found."
}
```

**Note**: Deleting a category will also delete all associated posts (if cascade delete is configured) or set their `category_id` to null (depending on database constraints). Exercise caution when deleting categories.

---

## 2. Category Statistics

### 2.1 Get Category Statistics

Retrieve statistics for a specific category including post counts, engagement metrics, and trends over time.

**Endpoint**: `GET /api/admin/v1/categories/{category}/stats`

**Authentication**: Required (Admin)

**URL Parameters**:
- `category` (required, integer): Category ID

**Query Parameters**:
- `range` (optional, string): Time range filter (`today`, `7d`, `30d`, `90d`, `month`, `quarter`, `year`, `custom`)
- `start_date` (optional, string, format: YYYY-MM-DD): Start date for custom range
- `end_date` (optional, string, format: YYYY-MM-DD): End date for custom range

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "summary": {
      "posts": 150,
      "likes": 4500,
      "shares": 1200,
      "comments": 850
    },
    "trend": [
      {
        "date": "2025-01-15",
        "count": 5
      },
      {
        "date": "2025-01-16",
        "count": 8
      },
      {
        "date": "2025-01-17",
        "count": 12
      }
    ]
  }
}
```

**Response Fields Description**:
- `summary`: Aggregate statistics for the time period
  - `posts`: Total number of posts in this category within the time range
  - `likes`: Total number of likes on posts in this category
  - `shares`: Total number of shares on posts in this category
  - `comments`: Total number of comments on posts in this category
- `trend`: Array of daily post counts, ordered by date
  - `date`: Date in YYYY-MM-DD format
  - `count`: Number of posts created on that date

**Example Requests**:
- Last 30 days: `GET /api/admin/v1/categories/1/stats?range=30d`
- Custom range: `GET /api/admin/v1/categories/1/stats?range=custom&start_date=2025-01-01&end_date=2025-01-31`
- This month: `GET /api/admin/v1/categories/1/stats?range=month`

---

## Error Responses

All endpoints may return the following error responses:

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Unauthorized. Admin access required."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found."
}
```

### 422 Unprocessable Entity
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**Common Validation Errors**:
- `name`: Required, must be unique, max 100 characters
- `description`: Must be a string
- `is_active`: Must be a boolean

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Internal server error."
}
```

---

## Notes

1. **Authentication**: All endpoints require authentication via Bearer token and admin privileges (`is_admin = true`).

2. **Slug Generation**: 
   - Slugs are automatically generated from category names using Laravel's `Str::slug()` helper
   - Slugs are unique and URL-friendly (lowercase, hyphenated)
   - Example: "Makeup & Beauty" → "makeup-beauty"

3. **Category Status**:
   - `is_active`: Controls whether the category is visible to regular users
   - Inactive categories may still have posts, but won't appear in public category lists

4. **Tags Association**:
   - Tags are automatically associated with categories based on posts that belong to that category
   - The tag list shows tags used in posts within that category, ordered by usage frequency
   - Tags are not directly attached to categories; the relationship is through posts

5. **Statistics**:
   - Statistics endpoints support flexible date range filtering
   - Default range if not specified depends on the endpoint implementation
   - Trend data provides daily granularity for visualizing category activity over time

6. **Deletion Impact**:
   - Deleting a category may affect associated posts depending on database constraints
   - Consider deactivating categories (`is_active = false`) instead of deleting if you want to preserve historical data

7. **Post Counts**:
   - `posts_count` includes all posts in the category, regardless of their status
   - Statistics endpoints only count posts within the specified date range

---

## API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/categories` | Get all categories |
| GET | `/categories/{category}` | Get category details |
| POST | `/categories` | Create a new category |
| PUT | `/categories/{category}` | Update a category |
| DELETE | `/categories/{category}` | Delete a category |
| GET | `/categories/{category}/stats` | Get category statistics |

---

## Examples

### Complete Workflow Example

1. **Create a new category**:
```bash
POST /api/admin/v1/categories
{
  "name": "Fitness",
  "description": "Fitness and workout posts",
  "is_active": true
}
```

2. **List all categories**:
```bash
GET /api/admin/v1/categories?search=fitness
```

3. **Get category details**:
```bash
GET /api/admin/v1/categories/4
```

4. **Update category**:
```bash
PUT /api/admin/v1/categories/4
{
  "name": "Fitness & Wellness",
  "description": "Fitness, workout, and wellness posts",
  "is_active": true
}
```

5. **Get category statistics**:
```bash
GET /api/admin/v1/categories/4/stats?range=30d
```

6. **Deactivate category** (without deleting):
```bash
PUT /api/admin/v1/categories/4
{
  "is_active": false
}
```

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20

