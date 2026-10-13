# Admin User Management API Documentation

> **Target Audience:** Frontend Developers (Admin Dashboard), QA Engineers, and Mobile/Web Client Integrations.  
> **Base URL:** `https://your-domain.com/api` (or local environment: `http://localhost:8000/api`)  
> **Authentication:** Required — JWT Bearer token with `role: admin`.  
> **Headers Required:**
> - `Accept: application/json`
> - `Authorization: Bearer <admin_access_token>`

---

## 1. Overview & Architecture

The **Admin User Management** module provides administrative oversight of the platform's user base. It categorizes users into three distinct tiers:

1. **`basic` (Basic Users):** Free/non-subscribed users with no company page.
2. **`premium` (Premium Users):** Users with an active paid personal subscription (`plan_type: premium`, e.g., *Pro User*).
3. **`pro_industry` (Pro Industry Users):** Users who own a company page (`industry`) or hold an active industry subscription (`plan_type: industry`, e.g., *Pro Industries*).

### Key Architectural Behaviors
- **Non-Admin Scope:** The admin account (`admin@gmail.com` / `role: admin`) is automatically excluded from all regular user management tables.
- **View-Only Mode:** User information is strictly read-only for administrators.
- **Account Suspension & Session Invalidation:** When a user is suspended:
  - `is_suspended` is set to `true` and `suspended_at` is timestamped.
  - All active tokens in `login_activities` are immediately invalidated (`is_active = false`).
  - Subsequent login attempts (standard credentials or social OAuth) return `403 Forbidden`.
  - Admins cannot be suspended (returns `403 Forbidden`).

---

## 2. API Endpoints Summary

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/admin/user-manage/users` | List paginated users with tier, status, and search filters |
| `GET` | `/api/admin/user-manage/users/{id}` | Detailed view of a single user's profile, career, subscription, and stats |
| `POST` | `/api/admin/user-manage/users/{id}/toggle-suspend` | Toggle suspension status or explicitly set suspension state |
| `GET` | `/api/admin/user-manage/leave-request` | View account deletion logs (Leave Requests) |

---

## 3. Endpoints Details

### 3.1. List Users (`GET /api/admin/user-manage/users`)

Fetches paginated user records for the admin management table.

- **Method:** `GET`
- **URL:** `/api/admin/user-manage/users`
- **Auth:** Required (`Bearer <admin_token>`)

#### Query Parameters:

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `type` | `string` | No | `'all'` | User category filter: `'basic'`, `'premium'`, `'pro_industry'` (aliases: `'industry'`, `'pro-industry'`), or `'all'`. |
| `search` | `string` | No | `null` | Keyword matching `first_name`, `last_name`, `full_name`, `email`, `username`, or `mobile`. |
| `status` | `string` | No | `'all'` | Filter by status: `'active'`, `'suspended'`, or `'all'`. |
| `per_page` | `integer` | No | `15` | Number of items per page (default: `15`, min: `1`, max: `100`). |
| `page` | `integer` | No | `1` | Pagination page number. |

---

#### Example 1: Basic Users List
**Request:**
```http
GET /api/admin/user-manage/users?type=basic&page=1&per_page=15 HTTP/1.1
Host: localhost:8000
Authorization: Bearer <admin_token>
Accept: application/json
```

**Response (`200 OK`):**
```json
{
  "success": true,
  "tier": "basic",
  "total_users": 1,
  "data": [
    {
      "sl_no": 1,
      "id": 26,
      "full_name": "Jane Doe",
      "first_name": "Jane",
      "last_name": "Doe",
      "username": "janedoe",
      "email": "user2@gmail.com",
      "mobile": "+1234567890",
      "profile_image_url": "https://your-domain.com/storage/profiles/avatar.jpg",
      "title": "Clinical Psychologist",
      "location": "California, United States",
      "profession": [
        "Psychologist",
        "Researcher"
      ],
      "status": "active",
      "is_suspended": false,
      "suspended_at": null,
      "is_verified": true,
      "terms_accepted_at": "12 Oct 2026, 08:30 AM",
      "joined_at": "12 Oct 2026",
      "created_at": "2026-10-12T08:30:00.000000Z",
      "tier": "basic",
      "plan": null,
      "company": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

---

#### Example 2: Premium Users List
**Request:**
```http
GET /api/admin/user-manage/users?type=premium&status=active HTTP/1.1
Host: localhost:8000
Authorization: Bearer <admin_token>
Accept: application/json
```

**Response (`200 OK`):**
```json
{
  "success": true,
  "tier": "premium",
  "total_users": 16,
  "data": [
    {
      "sl_no": 1,
      "id": 9,
      "full_name": "Sarah Connor",
      "first_name": "Sarah",
      "last_name": "Connor",
      "username": "sarahconnor",
      "email": "researcher1@university.edu",
      "mobile": "+1987654321",
      "profile_image_url": "https://your-domain.com/storage/profiles/sarah.jpg",
      "title": "Neuroscience Fellow",
      "location": "Massachusetts, United States",
      "profession": [
        "Neuroscientist"
      ],
      "status": "active",
      "is_suspended": false,
      "suspended_at": null,
      "is_verified": true,
      "terms_accepted_at": "10 Oct 2026, 09:15 AM",
      "joined_at": "10 Oct 2026",
      "created_at": "2026-10-10T09:15:00.000000Z",
      "tier": "premium",
      "plan": {
        "id": 1,
        "name": "Pro User",
        "plan_type": "premium",
        "billing_rate": "19.99",
        "billing_cycle": "monthly",
        "status": "active",
        "current_period_end": "2026-11-10T09:15:00.000000Z",
        "cancel_at_period_end": false
      },
      "company": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 16,
    "last_page": 2,
    "from": 1,
    "to": 15
  }
}
```

---

#### Example 3: Pro Industry Users List
**Request:**
```http
GET /api/admin/user-manage/users?type=pro_industry HTTP/1.1
Host: localhost:8000
Authorization: Bearer <admin_token>
Accept: application/json
```

**Response (`200 OK`):**
```json
{
  "success": true,
  "tier": "pro_industry",
  "total_users": 8,
  "data": [
    {
      "sl_no": 1,
      "id": 1,
      "full_name": "Exec Director 1",
      "first_name": "Exec",
      "last_name": "Director 1",
      "username": "execdirector1",
      "email": "industry.owner1@example.com",
      "mobile": "+1555123456",
      "profile_image_url": "https://your-domain.com/storage/profiles/exec1.jpg",
      "title": "Managing Director",
      "location": "New York, United States",
      "profession": [
        "Executive"
      ],
      "status": "active",
      "is_suspended": false,
      "suspended_at": null,
      "is_verified": true,
      "terms_accepted_at": "05 Oct 2026, 11:00 AM",
      "joined_at": "05 Oct 2026",
      "created_at": "2026-10-05T11:00:00.000000Z",
      "tier": "pro_industry",
      "plan": {
        "id": 2,
        "name": "Pro Industries",
        "plan_type": "industry",
        "billing_rate": "49.99",
        "billing_cycle": "monthly",
        "status": "active",
        "current_period_end": "2026-11-05T11:00:00.000000Z",
        "cancel_at_period_end": false
      },
      "company": {
        "id": 1,
        "name": "CogniSense Therapeutics",
        "slug": "cognisense-therapeutics",
        "industry": "Biotech",
        "logo": "https://your-domain.com/storage/companies/cognisense.png",
        "website": "https://cognisense.example.com",
        "company_size": "50-100"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 8,
    "last_page": 1,
    "from": 1,
    "to": 8
  }
}
```

---

### 3.2. View User Details (`GET /api/admin/user-manage/users/{id}`)

Returns complete, aggregated profile data for a specific user.

- **Method:** `GET`
- **URL:** `/api/admin/user-manage/users/{id}`
- **Auth:** Required (`Bearer <admin_token>`)

#### Path Parameters:
| Parameter | Type | Required | Description |
|---|---|---|---|
| `id` | `integer` | **Yes** | Target User ID. |

**Request:**
```http
GET /api/admin/user-manage/users/1 HTTP/1.1
Host: localhost:8000
Authorization: Bearer <admin_token>
Accept: application/json
```

**Response (`200 OK`):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "first_name": "Jane",
    "last_name": "Doe",
    "full_name": "Jane Doe",
    "username": "janedoe",
    "title": "UI/UX Designer",
    "about": "I'm a UI/UX designer focused on creating intuitive, visually engaging, and user-centered digital experiences for websites and mobile applications. I specialize in transforming complex ideas into simple, functional, and aesthetically pleasing interfaces.",
    "email": "jane@example.com",
    "mobile": "+1234567890",
    "profile_image_url": "https://your-domain.com/storage/profiles/avatar.jpg",
    "cover_image_url": "https://your-domain.com/storage/covers/cover.jpg",
    "is_verified": true,
    "is_suspended": false,
    "status": "active",
    "suspended_at": null,
    "tier": "pro_industry",
    "location": "California, United States",
    "total_connections": 45,
    "current_company": {
      "id": 3,
      "name": "Softvence Agency, Delta",
      "company_name": "Softvence Agency, Delta",
      "logo": "https://your-domain.com/storage/companies/softvence.png",
      "logo_url": "https://your-domain.com/storage/companies/softvence.png",
      "website": "https://softvence.agency"
    },
    "graduated_institution": {
      "id": 1,
      "name": "BGMEA University of Fashion & Technology",
      "institution_name": "BGMEA University of Fashion & Technology",
      "logo": "https://your-domain.com/storage/institutions/buft.png",
      "logo_url": "https://your-domain.com/storage/institutions/buft.png",
      "website": "https://buft.edu.bd"
    },
    "member_since": "12 Oct 2026",
    "terms_accepted_at": "12 Oct 2026, 08:30 AM",
    "created_at": "2026-10-12T08:30:00.000000Z",
    "stats": {
      "posts_count": 14,
      "comments_count": 8,
      "connections_count": 45,
      "followers_count": 120,
      "following_count": 68
    },
    "activity": {
      "total_posts": 14,
      "total_comments": 8,
      "posts": [
        {
          "id": 101,
          "description": "Imagine changing one color... And watching 200 screens update instantly.",
          "created_at": "1 hour ago",
          "created_at_formatted": "13 Oct 2026, 11:30 AM",
          "created_at_iso": "2026-10-13T11:30:00.000000Z",
          "total_like": 42,
          "total_comment": 12,
          "author": {
            "id": 1,
            "name": "Jane Doe",
            "title": "UI/UX Designer",
            "avatar": "https://your-domain.com/storage/profiles/avatar.jpg"
          },
          "media": [
            {
              "id": 5,
              "url": "https://your-domain.com/storage/posts/design-tokens.png",
              "type": "image",
              "sort_order": 1
            }
          ]
        }
      ],
      "comments": [
        {
          "id": 201,
          "post_id": 98,
          "comment": "Great insight on design tokens structure!",
          "image_url": null,
          "like_count": 3,
          "reply_count": 1,
          "created_at": "2 hours ago",
          "created_at_formatted": "13 Oct 2026, 10:15 AM",
          "created_at_iso": "2026-10-13T10:15:00.000000Z",
          "post": {
            "id": 98,
            "description_snippet": "Exploring systematic token architecture across scalable enterprise interfaces..."
          }
        }
      ]
    },
    "profile": {
      "about": "I'm a UI/UX designer focused on creating intuitive, visually engaging...",
      "country": "United States",
      "address": "123 Medical Center Dr",
      "postal_code": "90210",
      "state": {
        "id": 5,
        "name": "California"
      },
      "profession": [
        "UI/UX Designer"
      ],
      "interests": [
        "Design Systems",
        "Interaction Design"
      ],
      "highest_degree": "Bachelor's degree",
      "study_category": "Design",
      "field_study": "Apparel and Textile Manufacture",
      "institution": "BGMEA University of Fashion & Technology",
      "graduation_year": "2022",
      "skills": [
        {
          "id": 1,
          "name": "User Interface Design"
        },
        {
          "id": 2,
          "name": "User Experience Design"
        }
      ],
      "current_position": {
        "id": 3,
        "name": "Softvence Agency, Delta",
        "logo": "https://your-domain.com/storage/companies/softvence.png"
      },
      "current_institute": {
        "id": 1,
        "name": "BGMEA University of Fashion & Technology",
        "logo": "https://your-domain.com/storage/institutions/buft.png"
      }
    },
    "experiences": [
      {
        "id": 4,
        "company_id": 3,
        "company_name": "Softvence Agency, Delta",
        "company_logo_url": "https://your-domain.com/storage/companies/softvence.png",
        "company": {
          "id": 3,
          "name": "Softvence Agency, Delta",
          "logo": "https://your-domain.com/storage/companies/softvence.png",
          "website": "https://softvence.agency"
        },
        "title": "UX Designer",
        "employment_type": "Full Time",
        "job_type_with_period": "Full Time • 6 months",
        "start_date": "2025-07-01",
        "start_date_formatted": "Jul 2025",
        "end_date": null,
        "end_date_formatted": "Still working",
        "duration": "6 months",
        "formatted_period": "Jul 2025 • Still working • 6 months",
        "is_current": true,
        "location": "Dhaka",
        "country": "Bangladesh",
        "location_type": "Remote",
        "formatted_location": "Dhaka • Bangladesh • Remote",
        "description": "I'm a UI/UX designer focused on creating intuitive, visually engaging, and user-centered digital experiences for websites and mobile applications...",
        "skills": [
          {
            "id": 1,
            "name": "User Interface Design"
          },
          {
            "id": 2,
            "name": "User Experience Design"
          }
        ]
      }
    ],
    "educations": [
      {
        "id": 2,
        "institution_id": 1,
        "institution_name": "BGMEA University of Fashion & Technology",
        "institution_logo_url": "https://your-domain.com/storage/institutions/buft.png",
        "institution": {
          "id": 1,
          "name": "BGMEA University of Fashion & Technology",
          "logo": "https://your-domain.com/storage/institutions/buft.png",
          "website": "https://buft.edu.bd"
        },
        "degree": "Bachelor's degree",
        "field_study": "Apparel and Textile Manufacture",
        "degree_with_field": "Bachelor's degree • Apparel and Textile Manufacture",
        "start_month": "01",
        "start_year": "2018",
        "end_month": "12",
        "end_year": "2022",
        "formatted_period": "2018 • 2022",
        "grade": "3.44",
        "description": "Comprehensive studies in design and manufacture.",
        "activities": "Design Club, Student Council",
        "is_current": false,
        "skills": [
          {
            "id": 1,
            "name": "User Interface Design"
          },
          {
            "id": 2,
            "name": "User Experience Design"
          }
        ]
      }
    ],
    "subscription": {
      "is_subscribed": true,
      "id": 1,
      "plan_id": 2,
      "plan_name": "Pro Industries",
      "plan_type": "industry",
      "billing_rate": "49.99",
      "billing_cycle": "monthly",
      "status": "active",
      "current_period_start": "2026-10-01T00:00:00.000000Z",
      "current_period_end": "2026-11-01T00:00:00.000000Z",
      "cancel_at_period_end": false,
      "features": [
        "company_profile",
        "build_network",
        "product_advertisement"
      ]
    },
    "industry": {
      "id": 1,
      "name": "CogniSense Therapeutics",
      "slug": "cognisense-therapeutics",
      "industry": "Biotech",
      "website": "https://cognisense.example.com",
      "address": "500 Innovation Way, Palo Alto, CA",
      "company_size": "50-100",
      "tagline": "Pioneering Neurotherapeutics",
      "description": "Developing novel targeted digital therapeutics for cognitive disorders.",
      "logo": "https://your-domain.com/storage/companies/cognisense.png",
      "cover_image": "https://your-domain.com/storage/covers/cognisense-cover.png",
      "views_count": 1420,
      "created_at": "2026-08-26T08:30:00.000000Z"
    }
  }
}
```

---

### 3.3. Toggle / Set User Suspension (`POST /api/admin/user-manage/users/{id}/toggle-suspend`)

Toggles or explicitly updates the suspension status of a user.

- **Method:** `POST`
- **URL:** `/api/admin/user-manage/users/{id}/toggle-suspend`
- **Auth:** Required (`Bearer <admin_token>`)

#### Path Parameters:
| Parameter | Type | Required | Description |
|---|---|---|---|
| `id` | `integer` | **Yes** | Target User ID to suspend or unsuspend. |

#### Request Body (Optional):

You may call this endpoint with **no body** to toggle the status, or pass an explicit payload:

##### Mode A: Toggle (Empty Body)
```http
POST /api/admin/user-manage/users/26/toggle-suspend HTTP/1.1
Host: localhost:8000
Authorization: Bearer <admin_token>
Accept: application/json
Content-Type: application/json

{}
```

##### Mode B: Explicit Boolean
```json
{
  "is_suspended": true
}
```
*(or `"is_suspended": false`)*

##### Mode C: Explicit Status String
```json
{
  "status": "suspended"
}
```
*(or `"status": "active"`)*

---

#### Success Response — When User is Suspended (`200 OK`):
```json
{
  "success": true,
  "message": "User has been suspended successfully.",
  "data": {
    "id": 26,
    "is_suspended": true,
    "status": "suspended",
    "suspended_at": "2026-10-13T05:45:00.000000Z"
  }
}
```

#### Success Response — When User is Unsuspended (`200 OK`):
```json
{
  "success": true,
  "message": "User has been unsuspended successfully.",
  "data": {
    "id": 26,
    "is_suspended": false,
    "status": "active",
    "suspended_at": null
  }
}
```

---

## 4. Error Responses

### 4.1. Attempting to Suspend an Admin Account (`403 Forbidden`):
```json
{
  "success": false,
  "message": "Admin accounts cannot be suspended."
}
```

### 4.2. User Not Found (`404 Not Found`):
```json
{
  "message": "No query results for model [App\\Models\\User] 99999"
}
```

### 4.3. Unauthenticated User (`401 Unauthorized`):
```json
{
  "message": "Unauthenticated."
}
```

### 4.4. Non-Admin Account Access (`403 Forbidden`):
```json
{
  "message": "Forbidden"
}
```

### 4.5. Suspended User Attempting to Log In (`403 Forbidden`):
When a suspended user attempts to log in via `/api/login` or OAuth social login:
```json
{
  "success": false,
  "message": "Your account has been suspended. Please contact support."
}
```

---

## 5. Frontend Integration Quick Reference

### Axios Example: Tabbed User Table

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://your-domain.com/api',
  headers: {
    Authorization: `Bearer ${localStorage.getItem('admin_token')}`,
    Accept: 'application/json',
  },
});

// 1. Fetch Users by Tab Type (e.g., 'basic', 'premium', 'pro_industry')
export const fetchUsers = async (type = 'basic', page = 1, search = '', status = 'all') => {
  const response = await api.get('/admin/user-manage/users', {
    params: { type, page, search, status },
  });
  return response.data;
};

// 2. View Single User Details
export const fetchUserDetails = async (userId) => {
  const response = await api.get(`/admin/user-manage/users/${userId}`);
  return response.data.data;
};

// 3. Toggle Suspension
export const toggleUserSuspension = async (userId, explicitState = null) => {
  const payload = explicitState !== null ? { is_suspended: explicitState } : {};
  const response = await api.post(`/admin/user-manage/users/${userId}/toggle-suspend`, payload);
  return response.data;
};
```
