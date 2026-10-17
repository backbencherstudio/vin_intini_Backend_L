# Admin Partner (Pro Industry) Management API Documentation

## 1. Overview
This documentation details the Admin API endpoints for managing Partner Companies (`industries` table). It provides administrative control over partner profiles on MindUnite, including paginated listings with KPI metric summary cards, multi-field search and category filtering, and status toggling (Pause / Resume).

All endpoints require the user to be authenticated with the `admin` role (`auth:api` + `role:admin`).

---

## 2. API Endpoints Summary

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/admin/partners` | List paginated partner companies with KPI summary cards, search, and filters |
| `POST` | `/api/admin/partners/{id}/toggle-pause` | Pause or Resume a partner company |

---

## 3. Endpoints Details

### 3.1. List Partners (`GET /api/admin/partners`)

Fetches paginated partner industry records with global KPI summary cards and query filters.

- **Method:** `GET`
- **URL:** `/api/admin/partners`
- **Auth:** Required (`Bearer <admin_token>`)

#### Query Parameters:

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `search` | `string` | No | `null` | Keyword matching `name`, `slug`, `tagline`, `description`, `website`, or owner (`first_name`, `last_name`, `email`, `username`). |
| `status` | `string` | No | `'all'` | Filter by status: `'active'`, `'paused'`, or `'all'`. |
| `industry` | `string` | No | `'all'` | Filter by industry type (e.g. `'Biotechnology'`, `'Psychotropics'`, `'Publications'`). Alias: `industry_type`. |
| `per_page` | `integer` | No | `20` | Items per page (min: `1`, max: `100`). |
| `page` | `integer` | No | `1` | Pagination page number. |

#### Request:
```http
GET /api/admin/partners?status=active&industry=Biotechnology&page=1&per_page=20 HTTP/1.1
Host: localhost:8000
Authorization: Bearer <admin_token>
Accept: application/json
```

#### Response (`200 OK`):
```json
{
  "success": true,
  "message": "Partner industries retrieved successfully.",
  "summary": {
    "total_partners": 8,
    "active_partners": 7,
    "paused_partners": 1
  },
  "data": [
    {
      "sl_no": 1,
      "id": 8,
      "name": "CogniSense Therapeutics",
      "slug": "cognisense-therapeutics-7g2a9",
      "industry": "Biotechnology",
      "industry_type": "Biotechnology",
      "logo_url": "https://yourdomain.com/storage/industries/logos/cognisense.png",
      "website": "https://www.cognisense-therapeutics.com",
      "tagline": "Next-generation neuro-restorative therapeutics.",
      "description": "Novel pharmacotherapies for treatment-resistant mood disorders. Dedicated to peer-reviewed breakthrough research.",
      "company_size": "50-200",
      "views_count": 142,
      "status": "active",
      "is_paused": false,
      "paused_at": null,
      "created_at": "2026-10-13T10:29:24+00:00",
      "created_at_formatted": "13 Oct 2026",
      "owner": {
        "id": 8,
        "full_name": "Dr. Sarah Jenkins",
        "first_name": "Dr. Sarah",
        "last_name": "Jenkins",
        "username": "sarahj",
        "email": "sarah.jenkins@example.com",
        "mobile": "+1 555-0199",
        "profile_image_url": "https://yourdomain.com/storage/profile/sarah.jpg",
        "title": "Chief Scientific Officer",
        "location": "Boston, United States",
        "is_verified": true,
        "is_suspended": false
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 8,
    "last_page": 1,
    "from": 1,
    "to": 8,
    "has_more_pages": false
  }
}
```

---

### 3.2. Pause / Resume Partner Company (`POST /api/admin/partners/{id}/toggle-pause`)

Toggles the active state of an industry partner between `active` and `paused`. When paused, `paused_at` is set to the current timestamp; when resumed, `paused_at` is cleared to `null`.

- **Method:** `POST`
- **URL:** `/api/admin/partners/{id}/toggle-pause`
- **Auth:** Required (`Bearer <admin_token>`)
- **Headers:**
  - `Content-Type: application/json`
  - `Accept: application/json`

#### Request Body Options:

1. **Simple Toggle (No Body Required):**
   Toggles between `active` and `paused`.
   ```json
   {}
   ```

2. **Explicit Boolean Flag:**
   ```json
   {
     "is_paused": true
   }
   ```

3. **Explicit Status String:**
   ```json
   {
     "status": "paused"
   }
   ```

#### Response (`200 OK` - Paused):
```json
{
  "success": true,
  "message": "Industry 'CogniSense Therapeutics' has been paused successfully.",
  "data": {
    "id": 8,
    "name": "CogniSense Therapeutics",
    "status": "paused",
    "is_paused": true,
    "paused_at": "2026-10-17T10:15:00.000000Z",
    "updated_at": "2026-10-17T10:15:00.000000Z"
  }
}
```

#### Response (`200 OK` - Resumed):
```json
{
  "success": true,
  "message": "Industry 'CogniSense Therapeutics' has been resumed successfully.",
  "data": {
    "id": 8,
    "name": "CogniSense Therapeutics",
    "status": "active",
    "is_paused": false,
    "paused_at": null,
    "updated_at": "2026-10-17T10:16:00.000000Z"
  }
}
```

---

## 4. Error Responses

### 4.1. Unauthorized (`401 Unauthorized`)
Returned when the `Authorization: Bearer <token>` header is missing or expired:
```json
{
  "message": "Unauthenticated."
}
```

### 4.2. Forbidden (`403 Forbidden`)
Returned when an authenticated user does not have the `admin` role:
```json
{
  "message": "User does not have the right roles."
}
```

### 4.3. Not Found (`404 Not Found`)
Returned when the requested partner company ID or slug does not exist:
```json
{
  "message": "No query results for model [App\\Models\\Industry] 99999"
}
```
