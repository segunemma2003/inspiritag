# Inspirtag Admin API Specification

_Last updated: {{DATE}}_

## 1. Overview

-   **Audience**: Internal admin dashboard.
-   **Base URL**: `/api/admin`
-   **Authentication**: Bearer tokens issued to admin users (`users.is_admin = true`). All endpoints require the `admin` middleware.
-   **Versioning**: Start with `v1` namespace to allow future evolution (`/api/admin/v1`).
-   **Response format**: JSON with the standard wrapper:
    ```json
    {
      "success": true,
      "data": { ... },
      "meta": { ... },
      "message": "Optional human readable string"
    }
    ```
-   **Pagination**: Cursor-based or page-based (default page-based per existing APIs). Include `meta` with `current_page`, `per_page`, `total`, `last_page`.
-   **Filtering convention**: Query parameters such as `?filter[month]=2025-10`, `?filter[start_date]=2025-10-01&filter[end_date]=2025-10-31`, `?filter[category_id]=...`.
-   **Sorting convention**: `?sort=-created_at` (leading minus for descending).
-   **Time ranges**: Provide a reusable enum `range` query param (`today`, `7d`, `30d`, `90d`, `month`, `quarter`, `year`, `custom`) paired with date filters when `custom`.

### 1.1 Response Pattern (Applies to Every Endpoint)

#### Success

```json
{
    "success": true,
    "message": "Optional human-readable note",
    "data": {
        "...": "resource specific payload"
    },
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 200,
        "last_page": 10
    }
}
```

-   `meta` appears only for list/paginated endpoints.
-   `message` is optional (included for destructive/side-effect operations).

#### Validation Error (HTTP 422)

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "field": ["Error message..."]
    }
}
```

#### Authorization Error (HTTP 401/403)

```json
{
    "success": false,
    "message": "Unauthorized. Admin access required."
}
```

> **Note:** All endpoints documented below follow this response contract. Only the `data` payload shape differs per resource.

## 2. Authentication & Authorization

-   Reuse existing JWT authentication (Laravel Passport/Sanctum). Admin check enforced via middleware.
-   Include endpoint to issue admin tokens if not already present (optional).

### 2.1 Obtaining an Admin Token

1. **Log in** via the public login endpoint:

    `POST /api/login`

    ```json
    {
        "email": "admin@inspirtag.com",
        "password": "admin123"
    }
    ```

    The seeder `AdminUserSeeder` (run automatically during deployment) ensures this admin user exists if it is missing.

2. **Response**

    ```json
    {
        "success": true,
        "message": "Login successful",
        "data": {
            "user": {
                "id": 1,
                "email": "admin@inspirtag.com",
                "is_admin": true,
                "token_type": "Bearer",
                "...": "..."
            },
            "token": "<SANCTUM_TOKEN>",
            "token_type": "Bearer"
        }
    }
    ```

3. **Use the token** in every admin request:

    ```http
    Authorization: Bearer <SANCTUM_TOKEN>
    ```

4. **Log out** (optional) by calling `POST /api/logout` with the same bearer token.

> Any admin user (where `is_admin = true`) can authenticate this way. Ensure email is verified; the seeded admin is already verified.

## 3. High-Level Architecture

-   **Controllers**: Introduce a dedicated namespace `App\Http\Controllers\Api\Admin\` to separate admin surface area.
-   **Services**:
    -   `Admin\DashboardService` – aggregates metrics from repositories.
    -   `Admin\EngagementService`
    -   `Admin\ContentModerationService`
    -   `Admin\UserManagementService`
    -   `Admin\CategoryTagService`
    -   `Admin\SubscriptionAnalyticsService`
-   **Repositories**: Use optimized queries (chunking, caching via Redis) for heavy aggregations.
-   **Jobs**: For costly metrics (monthly aggregation) create scheduled job to precompute and cache daily/weekly snapshots.
-   **Caching**: Keyed by time range and filters (e.g., `admin:stats:overview:2025-10`). TTL 10 min for near real-time, longer for historical values.
-   **Database Indexes**: Ensure indexes on `posts.created_at`, `posts.category_id`, `likes.created_at`, `shares.created_at`, `user_follows.created_at`, `subscriptions.created_at` to make time-based filtering efficient.

## 4. Dashboard Metrics Endpoints

### 4.1 Overview Metrics

`GET /api/admin/v1/stats/overview`

-   **Query params**:
    -   `range` (default `30d`)
    -   `filter[start_date]`, `filter[end_date]`
-   **Returns**:
    ```json
    {
        "success": true,
        "data": {
            "totals": {
                "users": 12345,
                "posts": 54321,
                "likes": 87654,
                "shares": 43210
            },
            "growth": {
                "users": {
                    "current": 500,
                    "previous": 400,
                    "change_pct": 25.0
                },
                "posts": {
                    "current": 3200,
                    "previous": 2800,
                    "change_pct": 14.3
                },
                "likes": {
                    "current": 15000,
                    "previous": 12000,
                    "change_pct": 25.0
                },
                "shares": {
                    "current": 4200,
                    "previous": 3800,
                    "change_pct": 10.5
                }
            }
        }
    }
    ```
-   **Notes**: "Previous" comparison uses same length period immediately preceding the current range.

### 4.2 User Registration Stats

`GET /api/admin/v1/stats/users`

-   **Filters**: `range`, `filter[start_date]`, `filter[end_date]`
-   **Data**:
    -   `total_users`, `active_users` (users with activity in last `range`), `new_users` during range
    -   `growth_rate`
    -   Time series for charts (`trend` array with `date`, `count`)
    -   `distribution` by user type (standard/business/professional)
    -   `retention` (optional) – returning vs new users

### 4.3 Post & Content Stats

`GET /api/admin/v1/stats/posts`

-   **Filters**: `range`, `filter[category_id]`, `filter[tag_id]`, `filter[media_type]` (`image`, `video`)
-   **Data**:
    -   `total_posts`
    -   `avg_posts_per_user`
    -   `category_distribution`: `[ {"category_id": 1, "name": "Hair", "percentage": 35.4, "posts": 1234}, ... ]`
    -   `media_distribution`: `{"image": {"count": 2000, "percentage": 65}, "video": {...}}`
    -   `top_categories`, `top_tags`
    -   `trend` timeseries for posts created per day/week.

### 4.4 Engagement Trend

`GET /api/admin/v1/stats/engagement`

-   **Purpose**: Provide series for graphs.
-   **Data**:
    ```json
    {
      "likes": [{"date": "2025-10-01", "count": 500}, ...],
      "shares": [{"date": "2025-10-01", "count": 120}, ...],
      "comments": [{...}],
      "saves": [{...}],
      "total_engagement": 12345,
      "growth": {"likes": {...}, "shares": {...}}
    }
    ```

### 4.5 Content Category Breakdown

`GET /api/admin/v1/stats/categories`

-   Summaries per category: total posts, likes, shares, comment counts, percentage share.

### 4.6 Active Users by Time

`GET /api/admin/v1/stats/active-users`

-   Returns aggregated data by hour/day-of-week/time-of-day to see when users are active.

### 4.7 Subscription Analytics

`GET /api/admin/v1/stats/subscriptions`

-   Data includes:
    -   `total_subscribers`
    -   `active_subscribers`
    -   `monthly_recurring_revenue` (price × active)
    -   `growth_rate`
    -   `plan_distribution`
    -   `trend` – monthly subscriber count for the last 12 months
    -   `top_creators` – highest earning or most referred subscribers

## 5. Admin Post Management

### 5.1 List/Search Posts

`GET /api/admin/v1/posts`

-   **Query**:
    -   `search` (caption, hashtags, user handle)
    -   `status` (`published`, `flagged`, `blocked`, `featured`)
    -   `category_id`, `tag_id`, `user_id`
    -   `range`, date filters
    -   Sorting by `created_at`, `likes_count`, `shares_count`
-   **Response**: Paginated list with aggregated stats per post (likes, shares, comments, saves, report_count).

### 5.2 Post Details

`GET /api/admin/v1/posts/{post}`

-   Includes full metadata, media, author summary, latest reports, engagement timeline (for post-level chart).

### 5.3 Feature/Unfeature Post

-   `POST /api/admin/v1/posts/{post}/feature`
-   `DELETE /api/admin/v1/posts/{post}/feature`

### 5.4 Block/Unblock Post

-   `POST /api/admin/v1/posts/{post}/block`
-   `DELETE /api/admin/v1/posts/{post}/block`
-   Accept reason and optional expiry.

### 5.5 Flag Post

-   `POST /api/admin/v1/posts/{post}/flag`
    ```json
    {
        "reason": "Manual review",
        "notes": "Contains prohibited content"
    }
    ```

### 5.6 Post Reports

`GET /api/admin/v1/reports/posts`

-   List of post reports with filters by status, reason, created_at.
-   `PATCH /api/admin/v1/reports/{report}` to update status (resolved, dismissed, escalated).

## 6. User Management

### 6.1 List/Search Users

`GET /api/admin/v1/users`

-   Filters: `search`, `status` (`active`, `blocked`, `pending_verification`), `subscription_status`, `range`, `role`.
-   Include stats per user: total posts, average engagement, followers/following, account age.

### 6.2 User Details

`GET /api/admin/v1/users/{user}`

-   Includes: profile info, follower/following counts, engagement summary, subscription status, business profile, recent posts, flags.

### 6.3 Followers/Following

-   `GET /api/admin/v1/users/{user}/followers`
-   `GET /api/admin/v1/users/{user}/following`

### 6.4 Block/Unblock User

-   `POST /api/admin/v1/users/{user}/block`
-   `DELETE /api/admin/v1/users/{user}/block`
-   Accept reason and audit trail.

### 6.5 Delete User

`DELETE /api/admin/v1/users/{user}`

-   Soft delete recommended with tombstone record and queue job to purge content asynchronously.

### 6.6 User Stats Snapshot

`GET /api/admin/v1/users/{user}/stats`

-   Returns per-user metrics: posts count, average reactions per post, total likes/shares received, follower trend, subscription history.

## 7. Category Management

### 7.1 List Categories

`GET /api/admin/v1/categories`

-   Includes aggregated stats: total posts, percentage share, growth.

### 7.2 Create Category

`POST /api/admin/v1/categories`

```json
{
    "name": "Hair Stylist",
    "slug": "hair-stylist",
    "description": "...",
    "is_active": true,
    "display_order": 1
}
```

### 7.3 Update Category

`PUT /api/admin/v1/categories/{category}`

### 7.4 Delete Category

`DELETE /api/admin/v1/categories/{category}`

-   Consider soft delete flag to preserve historical analytics.

### 7.5 Category Stats

`GET /api/admin/v1/categories/{category}/stats`

-   Total posts, active creators, engagement metrics, top posts, top tags.

## 8. Tag Management

Endpoints mirror categories:

-   `GET /api/admin/v1/tags`
-   `POST /api/admin/v1/tags`
-   `PUT /api/admin/v1/tags/{tag}`
-   `DELETE /api/admin/v1/tags/{tag}`
-   `GET /api/admin/v1/tags/{tag}/stats`
-   `GET /api/admin/v1/tags/trending`

## 9. Subscription Management

### 9.1 Plans CRUD

-   `GET /api/admin/v1/subscriptions/plans`
-   `POST /api/admin/v1/subscriptions/plans`
-   `PUT /api/admin/v1/subscriptions/plans/{plan}`
-   `DELETE /api/admin/v1/subscriptions/plans/{plan}` (soft delete recommended)
-   Attributes: `name`, `apple_product_id`, `price`, `currency`, `duration_days`, `features`, `is_active`.

### 9.2 Subscriber Listing

`GET /api/admin/v1/subscriptions/subscribers`

-   Filters: `status` (`active`, `expired`, `canceled`), `plan_id`, `range`.
-   Response includes user info, subscription start/end dates, lifetime value, renewal history.

### 9.3 Subscription Stats

`GET /api/admin/v1/subscriptions/stats`

-   Totals, monthly new/lost subscribers, churn rate, MRR/ARR, average revenue per user, growth percentages.

### 9.4 Subscription Trend Graph

`GET /api/admin/v1/subscriptions/trend`

-   Time series for visual charts.

### 9.5 Top Creators

`GET /api/admin/v1/subscriptions/top-creators`

-   Ranking by referred subscribers, engagement, or earnings.

## 10. Engagement Metrics

`GET /api/admin/v1/engagement/trend`

-   Combined endpoint for likes, shares, comments, saves per day/week/month.

`GET /api/admin/v1/engagement/distribution`

-   Breakdown by content type, category, time-of-day.

## 11. Reporting & Export

-   Provide endpoints to export CSV/Excel snapshots:
    -   `/api/admin/v1/export/users?range=30d`
    -   `/api/admin/v1/export/posts?status=flagged`
    -   `/api/admin/v1/export/subscriptions?month=2025-10`
-   Use queued jobs and signed URLs for download.

## 12. Audit & Activity Logging

-   Log admin actions (feature/block, user moderation, plan changes) with `AdminActivity` table.
-   Endpoint: `GET /api/admin/v1/audit-log` with filters by admin, action, date.

## 13. Implementation Roadmap

1. **Foundation**
    - Create `Admin` namespace controllers and routes
    - Implement middleware for admin auth
    - Establish base response macros and error handling
2. **Dashboard Metrics**
    - Build `Admin\DashboardService` with caching
    - Implement overview/user/post/engagement/subscription stats endpoints
3. **Content Moderation**
    - Implement post list/search, feature/block/flag flows
    - Build report management endpoints
4. **User Management**
    - Implement user list/view stats/followers/block/delete endpoints
5. **Categories & Tags**
    - CRUD + stats endpoints for categories and tags
6. **Subscriptions**
    - Plan CRUD, subscriber listings, analytics
    - Integrate with existing Apple Pay product IDs via `AppleInAppPurchaseService`
7. **Exports & Audit Trails**
    - Background jobs for CSV exports
    - Admin activity logs
8. **Documentation & SDK**
    - Generate OpenAPI spec (`docs/admin-api-openapi.json`)
    - Provide Postman collection.

## 14. Sample OpenAPI Snippet

```yaml
paths:
    /api/admin/v1/stats/overview:
        get:
            summary: "Get high-level overview metrics"
            parameters:
                - in: query
                  name: range
                  schema:
                      type: string
                      enum: [today, 7d, 30d, month, quarter, year, custom]
                - in: query
                  name: filter[start_date]
                  schema:
                      type: string
                      format: date
                - in: query
                  name: filter[end_date]
                  schema:
                      type: string
                      format: date
            responses:
                "200":
                    description: Successful response
                    content:
                        application/json:
                            schema:
                                $ref: "#/components/schemas/AdminOverviewResponse"
```

## 15. Data Validation & Performance Considerations

-   Leverage Laravel Form Requests for validation.
-   Use eager loading to avoid N+1 queries.
-   Precompute heavy metrics nightly; store in `admin_metric_snapshots` table.
-   Add queue workers for asynchronous tasks (export generation, trend calculations).
-   Apply rate limits for admin endpoints (lower priority but protect backend).
-   Ensure GDPR compliance for user deletions and data exports.

## 16. Testing Strategy

-   PHPUnit integration tests for each endpoint with seed data.
-   Use factories to create users/posts/likes/shares.
-   Performance tests for heavy aggregation endpoints.
-   API documentation tests (e.g., using Spectator or custom assertions).

## 17. Implemented Endpoint Summary

The following endpoints are available under the `/api/admin/v1` prefix (all require `auth:sanctum` + `admin` middleware):

-   `GET /stats/overview`
-   `GET /stats/users`
-   `GET /stats/posts`
-   `GET /stats/engagement`
-   `GET /stats/categories`
-   `GET /stats/active-users`
-   `GET /stats/subscriptions`
-   `GET /stats/subscriptions/trend`
-   `GET /stats/subscriptions/top-creators`
-   `GET /posts`
-   `GET /posts/{post}`
-   `POST /posts/{post}/feature`
-   `DELETE /posts/{post}/feature`
-   `POST /posts/{post}/block`
-   `DELETE /posts/{post}/block`
-   `POST /posts/{post}/flag`
-   `DELETE /posts/{post}/flag`
-   `GET /reports/posts`
-   `PATCH /reports/posts/{report}`
-   `GET /users`
-   `GET /users/{user}`
-   `GET /users/{user}/followers`
-   `GET /users/{user}/following`
-   `GET /users/{user}/stats`
-   `POST /users/{user}/block`
-   `DELETE /users/{user}/block`
-   `DELETE /users/{user}`
-   `GET /categories`
-   `POST /categories`
-   `GET /categories/{category}`
-   `PUT /categories/{category}`
-   `DELETE /categories/{category}`
-   `GET /categories/{category}/stats`
-   `GET /tags`
-   `POST /tags`
-   `PUT /tags/{tag}`
-   `DELETE /tags/{tag}`
-   `GET /tags/{tag}/stats`
-   `GET /tags/trending`
-   `GET /subscriptions/plans`
-   `POST /subscriptions/plans`
-   `PUT /subscriptions/plans/{plan}`
-   `DELETE /subscriptions/plans/{plan}`
-   `GET /subscriptions/subscribers`
-   `GET /subscriptions/stats`
-   `GET /subscriptions/trend`
-   `GET /subscriptions/top-creators`
-   `GET /exports/{type}`

All responses follow the `{ success: bool, data: ..., meta?: ..., message?: string }` shape described earlier.

---

This document provides the blueprint for implementing the requested admin dashboard APIs with comprehensive metrics, moderation tools, subscription management, and supporting documentation.
