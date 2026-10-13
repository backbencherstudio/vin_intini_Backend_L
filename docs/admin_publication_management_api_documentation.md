# Admin Publication Management API Documentation

## 1. Overview
This documentation details the Admin API endpoints for managing the `publications` table. It provides full control over research papers and articles published on MindUnite, including listing with multi-field search and filters, KPI overview statistics, detailed inspection (including author, creator, industry, and engagement logs), status updates, toggling, editing, soft deletion, and restoration.

All endpoints require the user to be authenticated with the `admin` role (`auth:api` + `role:admin`).

---

## 2. API Endpoints Summary

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/admin/publications` | List paginated publications with filters, search, and KPI overview |
| `GET` | `/api/admin/publications/{id}` | Detailed view of a single publication, creator, industry, and engagement logs |
| `POST` | `/api/admin/publications/{id}/toggle-status` | Toggle status between `active` and `inactive` |
| `DELETE` | `/api/admin/publications/{id}` | Delete publication (soft-delete, or permanent purge via `?force=true`) |

---

## 3. Endpoints Details

### 3.1. List Publications (`GET /api/admin/publications`)

Fetches paginated publication records with global KPI cards and comprehensive query filters.

- **Method:** `GET`
- **URL:** `/api/admin/publications`
- **Auth:** Required (`Bearer <admin_token>`)

#### Query Parameters:

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `search` | `string` | No | `null` | Keyword matching `title`, `publication_id`, `slug`, `abstract`, `website_url`, creator (`first_name`, `last_name`, `email`, `username`), or industry name. |
| `status` | `string` | No | `'all'` | Filter by publication status: `'active'`, `'inactive'`, `'draft'`, or `'all'`. |
| `network_type` | `string` | No | `'all'` | Filter by network: `'psychology'`, `'neuroscience'`, or `'all'`. |
| `publication_type` | `string` | No | `'all'` | Filter by type: `'professional'`, `'university'`, `'freelance'`, `'other'`, or `'all'`. |
| `per_page` | `integer` | No | `15` | Items per page (min: `1`, max: `100`). |
| `page` | `integer` | No | `1` | Pagination page number. |

#### Request:
```http
GET /api/admin/publications?status=active&network_type=neuroscience&page=1&per_page=15 HTTP/1.1
Host: localhost:8000
Authorization: Bearer <admin_token>
Accept: application/json
```

#### Response (`200 OK`):
```json
{
  "success": true,
  "statistics": {
    "total_publications": 24,
    "active_publications": 18,
    "inactive_publications": 4,
    "draft_publications": 2
  },
  "data": [
    {
      "sl_no": 1,
      "id": 1,
      "publication_id": "PB1A7F2",
      "title": "Neural Dynamics in Decision Making",
      "slug": "neural-dynamics-decision-making-1",
      "network_type": "neuroscience",
      "publication_type": "professional",
      "status": "active",
      "authors": [
        "Dr. Albert Einstein",
        "Dr. Niels Bohr"
      ],
      "short_abstract": "In-depth study on computational neuroscience, synaptic plasticity, and decision-making architectures under varying neurological stimuli...",
      "website_url": "https://doi.org/10.1016/j.neuron.2026.01",
      "attachment_url": "https://your-domain.com/storage/publications/attachments/paper1.pdf",
      "information_confirmed": true,
      "views_count": 120,
      "likes_count": 35,
      "created_at": "12 Oct 2026",
      "created_at_iso": "2026-10-12T08:30:00.000000Z",
      "deleted_at": null,
      "is_deleted": false,
      "creator": {
        "id": 4,
        "name": "Albert Einstein",
        "email": "albert@quantum.org",
        "username": "einstein",
        "profile_image_url": "https://your-domain.com/storage/profiles/albert.jpg",
        "tier": "pro_industry"
      },
      "industry": {
        "id": 2,
        "name": "Quantum Neuro Labs",
        "slug": "quantum-neuro-labs",
        "logo_url": "https://your-domain.com/storage/companies/quantum.png"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 12,
    "last_page": 1,
    "from": 1,
    "to": 12
  }
}
```

---

### 3.2. View Publication Details (`GET /api/admin/publications/{id}`)

Returns complete details of a publication including full abstract, author metadata, creator profile, industry details, and recent viewer/like engagement logs. Can be looked up by primary `id` or scoped `publication_id` (e.g., `PB1A7F2`).

- **Method:** `GET`
- **URL:** `/api/admin/publications/{id}`
- **Auth:** Required (`Bearer <admin_token>`)

#### Request:
```http
GET /api/admin/publications/1 HTTP/1.1
Host: localhost:8000
Authorization: Bearer <admin_token>
Accept: application/json
```

#### Response (`200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "publication_id": "PB1A7F2",
    "title": "Neural Dynamics in Decision Making",
    "slug": "neural-dynamics-decision-making-1",
    "network_type": "neuroscience",
    "publication_type": "professional",
    "status": "active",
    "authors": [
      "Dr. Albert Einstein",
      "Dr. Niels Bohr"
    ],
    "abstract": "In-depth study on computational neuroscience, synaptic plasticity, and decision-making architectures under varying neurological stimuli. The experimental results indicate consistent neural activation patterns.",
    "website_url": "https://doi.org/10.1016/j.neuron.2026.01",
    "attachment": "publications/attachments/paper1.pdf",
    "attachment_url": "https://your-domain.com/storage/publications/attachments/paper1.pdf",
    "information_confirmed": true,
    "views_count": 120,
    "likes_count": 35,
    "created_at": "12 Oct 2026, 08:30 AM",
    "created_at_iso": "2026-10-12T08:30:00.000000Z",
    "updated_at": "13 Oct 2026, 10:15 AM",
    "updated_at_iso": "2026-10-13T10:15:00.000000Z",
    "deleted_at": null,
    "is_deleted": false,
    "creator": {
      "id": 4,
      "first_name": "Albert",
      "last_name": "Einstein",
      "full_name": "Albert Einstein",
      "username": "einstein",
      "email": "albert@quantum.org",
      "mobile": "+1555123456",
      "title": "Lead Physicist & Neurocomputational Fellow",
      "profile_image_url": "https://your-domain.com/storage/profiles/albert.jpg",
      "tier": "pro_industry",
      "is_verified": true,
      "is_suspended": false,
      "status": "active"
    },
    "industry": {
      "id": 2,
      "name": "Quantum Neuro Labs",
      "slug": "quantum-neuro-labs",
      "industry": "biotechnology",
      "logo_url": "https://your-domain.com/storage/companies/quantum.png",
      "website": "https://quantumneurolabs.org",
      "address": "100 Science Park, Princeton, NJ",
      "company_size": "50-100",
      "views_count": 540
    }
  }
}
```

---

### 3.3. Toggle Publication Status (`POST /api/admin/publications/{id}/toggle-status`)

Toggles publication status between `'active'` and `'inactive'` (matching the same pattern as user suspend toggle).

- **Method:** `POST`
- **URL:** `/api/admin/publications/{id}/toggle-status`
- **Auth:** Required (`Bearer <admin_token>`)
- **Body:** None / Empty `{}`

#### Response (`200 OK`):
```json
{
  "success": true,
  "message": "Publication status toggled to 'inactive' successfully.",
  "data": {
    "id": 1,
    "publication_id": "PB1A7F2",
    "status": "inactive",
    "updated_at": "2026-10-13T14:20:00.000000Z"
  }
}
```

---

### 3.4. Delete Publication (`DELETE /api/admin/publications/{id}`)

Deletes a publication. By default, performs a **soft delete** (marks `deleted_at`). Passing `?force=true` permanently deletes the record and removes any attached file from storage.

- **Method:** `DELETE`
- **URL:** `/api/admin/publications/{id}`
- **Auth:** Required (`Bearer <admin_token>`)

#### Mode A: Soft Delete (Default)
`DELETE /api/admin/publications/1`

```json
{
  "success": true,
  "message": "Publication soft-deleted successfully."
}
```

#### Mode B: Permanent Force Delete
`DELETE /api/admin/publications/1?force=true`

```json
{
  "success": true,
  "message": "Publication permanently deleted successfully."
}
```

