# Pro Industry Products & Advertisements API Documentation

> **Target Audience:** Frontend Developers (Web / Mobile Apps), QA Engineers, and AI Coding Agents.  
> **Base URL:** `https://your-domain.com/api` (or local environment: `http://localhost:8000/api`)  
> **Headers Required:**
> - `Accept: application/json`
> - `Authorization: Bearer <access_token>` (Required for all routes under authenticated groups)

---

## 1. System Overview & Core Constants

This API powers the **Industry Product Showcase / Advertisements** ecosystem. It serves two distinct personas:
1. **Public / Authenticated Users:** Browse products section-by-section, filter by sub-categories, view product details (with auto-unique-view tracking), and toggle likes (which also automatically records views).
2. **Pro Industry Company Subscribers:** Companies with an active Pro Industry subscription (`plan_type: industry`) can manage their advertisements, view real-time analytics (total ads, active ads, total views, total likes), and upload/update products.

### Allowed Enum Values
- **`network_type`**: `'psychology'` | `'neuroscience'`
- **`industry_type`**: `'biotechnology'` | `'psychotropics'`
- **`status`**: `'active'` | `'paused'` | `'draft'`

---

## 2. Public / User Endpoints

### 2.1. Products Feed
Fetches products for the public feed. Supports **three distinct modes**:
1. **Section-Wise Mode (Default for Web):** Returns all sections for the network/industry with sub-category tabs and initial products grouped together.
2. **Tab-Filter Mode:** When a user clicks a specific section or sub-category tab.
3. **Flat Paginated Mode (Recommended for Mobile App Infinite Scroll):** Returns a flat list of products across sections with standard pagination.

- **Method:** `GET`
- **URL:** `/api/industry/products/feed`
- **Auth:** Optional / Recommended (`Bearer <token>` to get personalized `is_liked` status)

#### Query Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `network_type` | `string` | **Yes** | — | `'psychology'` or `'neuroscience'` |
| `industry_type` | `string` | **Yes** | — | `'biotechnology'` or `'psychotropics'` |
| `section_id` | `integer` | No | `null` | Filter by specific Section ID. Triggers Tab-Filter mode. |
| `category_id` | `integer` | No | `null` | Filter by specific Sub-category ID. |
| `search` | `string` | No | `null` | Keyword search matching `product_name` or `description`. |
| `flat` | `boolean` | No | `false` | Pass `flat=1` for flat paginated list (Ideal for Mobile App infinite scroll). |
| `limit_per_section` | `integer`| No | `12` | Max products per section in Section-Wise mode (max: 50). |
| `per_page` | `integer` | No | `12` | Number of items per page in Tab-Filter / Flat mode (max: 100). |
| `page` | `integer` | No | `1` | Page number for pagination. |

---

#### Mode A: Section-Wise Response (Web Initial Page Load)
**Request:**
```http
GET /api/industry/products/feed?network_type=psychology&industry_type=biotechnology
```

**Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Section-wise products feed retrieved successfully.",
  "data": [
    {
      "section_id": 1,
      "section_name": "Neuroscientific and Psychophysiological Equipment",
      "network_type": "psychology",
      "industry_type": "biotechnology",
      "categories": [
        {
          "id": 10,
          "category_name": "Brain Scanners"
        },
        {
          "id": 11,
          "category_name": "Physiological Monitoring Devices"
        }
      ],
      "products": [
        {
          "id": 101,
          "product_id": "849201",
          "creator_id": 25,
          "creator": {
            "id": 25,
            "name": "Dr. Sarah Connor",
            "username": "sarah_connor",
            "profile_image": "https://domain.com/storage/profiles/avatar.png"
          },
          "industry_id": 4,
          "industry": {
            "id": 4,
            "name": "Biopac Systems",
            "logo": "https://domain.com/storage/industries/logo.png"
          },
          "product_name": "MP160 System with AcqKnowledge",
          "slug": "mp160-system-with-acqknowledge",
          "network_type": "psychology",
          "industry_type": "biotechnology",
          "section_id": 1,
          "section_name": "Neuroscientific and Psychophysiological Equipment",
          "category_id": 10,
          "category_name": "Brain Scanners",
          "description": "High-resolution functional brain imaging with real-time analysis.",
          "short_description": "High-resolution functional brain imaging with real-time analysis.",
          "product_url": "https://www.biopac.com/product/mp160-system/",
          "image": "industry_products/mp160.webp",
          "image_url": "https://domain.com/storage/industry_products/mp160.webp",
          "tags": ["EEG", "Brain Imaging", "AcqKnowledge"],
          "information_confirmed": true,
          "status": "active",
          "views_count": 48,
          "likes_count": 15,
          "is_liked": false,
          "created_at": "2026-10-05T06:15:00.000000Z",
          "updated_at": "2026-10-05T06:15:00.000000Z"
        }
      ]
    },
    {
      "section_id": 2,
      "section_name": "Psychological Assessment Instruments",
      "network_type": "psychology",
      "industry_type": "biotechnology",
      "categories": [
        {
          "id": 12,
          "category_name": "Intelligence and Cognitive Tests"
        },
        {
          "id": 13,
          "category_name": "Personality Assessments"
        }
      ],
      "products": [ ... ]
    }
  ]
}
```

---

#### Mode B: Tab Filter Response (User clicks a Sub-Category Tab or All Tab)
**Request:**
```http
GET /api/industry/products/feed?network_type=psychology&industry_type=biotechnology&section_id=1&category_id=10&page=1
```

**Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Products retrieved successfully.",
  "data": [
    {
      "id": 101,
      "product_name": "MP160 System with AcqKnowledge",
      "slug": "mp160-system-with-acqknowledge",
      "industry": {
        "id": 4,
        "name": "Biopac Systems",
        "logo": "https://domain.com/storage/industries/logo.png"
      },
      "section_name": "Neuroscientific and Psychophysiological Equipment",
      "category_name": "Brain Scanners",
      "short_description": "High-resolution functional brain imaging...",
      "product_url": "https://www.biopac.com/product/mp160-system/",
      "image_url": "https://domain.com/storage/industry_products/mp160.webp",
      "views_count": 48,
      "likes_count": 15,
      "is_liked": false
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 12,
    "total": 1,
    "last_page": 1,
    "has_more_pages": false
  }
}
```

---

#### Mode C: Flat Infinite Scroll Response (Mobile App / Catalog View)
**Request:**
```http
GET /api/industry/products/feed?network_type=psychology&industry_type=biotechnology&flat=1&page=1
```

**Response (`200 OK`):** Same structure as Mode B with `data` array and `pagination` object.

---

### 2.2. Categories & Cascading Dropdown Endpoints
Used for populating dependent cascading dropdowns in the **Product Creation / Edit Form** (e.g., Network Type -> Industry Type -> Select Section -> Select Category).

1. **Get Sections List for "Select a Section *" Dropdown:**
   - **Method:** `GET`
   - **URL:** `/api/industry/advertisements/sections`
   - **Query Params:** `network_type` (optional), `industry_type` (optional: `'biotechnology'` | `'psychotropics'`)
   - **Response (`200 OK`):**
     ```json
     {
       "success": true,
       "message": "Sections retrieved successfully.",
       "data": [
         {
           "id": 1,
           "name": "Neuroscientific and Psychophysiological Equipment",
           "network_type": "psychology",
           "industry_type": "biotechnology"
         }
       ]
     }
     ```

2. **Get Categories for Selected Section ("Select a Category *" Dropdown):**
   - **Method:** `GET`
   - **URL:** `/api/industry/advertisements/sections/{section_id}/categories`
   - **Response (`200 OK`):**
     ```json
     {
       "success": true,
       "message": "Section categories retrieved successfully.",
       "data": {
         "section_id": 1,
         "section_name": "Neuroscientific and Psychophysiological Equipment",
         "network_type": "psychology",
         "industry_type": "biotechnology",
         "categories": [
           {
             "id": 1,
             "section_id": 1,
             "category_name": "Brain Scanners"
           },
           {
             "id": 3,
             "section_id": 1,
             "category_name": "Physiological Monitoring Devices"
           }
         ]
       }
     }
     ```

---

### 2.3. Product Details (Auto Unique View Counter)
Returns complete product data.  
**Special Behavior:** Automatically records a unique view for the authenticated user or IP address. View count will **never increment more than once** per user for a given product.

- **Method:** `GET`
- **URL:** `/api/industry/products/{id}/details`
- **Auth:** Recommended (`Bearer <token>`)

**Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Product details retrieved successfully.",
  "data": {
    "id": 101,
    "product_id": "849201",
    "creator_id": 25,
    "creator": {
      "id": 25,
      "name": "Dr. Sarah Connor",
      "username": "sarah_connor",
      "profile_image": "https://domain.com/storage/profiles/avatar.png"
    },
    "industry_id": 4,
    "industry": {
      "id": 4,
      "name": "Biopac Systems",
      "logo": "https://domain.com/storage/industries/logo.png"
    },
    "product_name": "MP160 System with AcqKnowledge",
    "slug": "mp160-system-with-acqknowledge",
    "network_type": "psychology",
    "industry_type": "biotechnology",
    "section_id": 1,
    "section_name": "Neuroscientific and Psychophysiological Equipment",
    "category_id": 10,
    "category_name": "Brain Scanners",
    "description": "Comprehensive multi-channel data acquisition system for life science research...",
    "short_description": "Comprehensive multi-channel data acquisition system...",
    "product_url": "https://www.biopac.com/product/mp160-system/",
    "image": "industry_products/mp160.webp",
    "image_url": "https://domain.com/storage/industry_products/mp160.webp",
    "tags": ["EEG", "Brain Imaging"],
    "poc_name": "Sheikh Muhammad Ashik",
    "poc_email": "smashik@company.com",
    "poc_phone": "+1 234 5678 87",
    "information_confirmed": true,
    "status": "active",
    "views_count": 49,
    "likes_count": 15,
    "is_liked": false,
    "created_at": "2026-10-05T06:15:00.000000Z",
    "updated_at": "2026-10-05T06:15:00.000000Z"
  }
}
```

---

### 2.4. Toggle Like on Product (With Auto Unique View)
Toggles the product like state for the current authenticated user.  
**Special Behavior:** If the user liked the product from the feed card without opening details, the backend **automatically counts the unique view** if not already recorded.

- **Method:** `POST`
- **URL:** `/api/industry/products/{id}/like`
- **Auth:** Required (`Bearer <token>`)

**Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Product liked successfully.",
  "data": {
    "is_liked": true,
    "likes_count": 16,
    "views_count": 50
  }
}
```
*(When unliked, `message` is "Product unliked successfully.", `is_liked: false`, and `likes_count` decrements by 1).*

---

## 3. Pro Industry Advertisement Management (Company Portal)

These endpoints require an authenticated user who has:
1. Role `user`.
2. An approved Company Profile (`Industry`).
3. An active **Pro Industry Subscription** (`PlanType::INDUSTRY`).

---

### 3.1. Advertisement Dashboard Metrics
Provides real-time analytics for the company's advertisement dashboard overview.

- **Method:** `GET`
- **URL:** `/api/industry/advertisements/dashboard`
- **Auth:** Required (`Bearer <token>`)

**Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Advertisement dashboard data retrieved successfully.",
  "data": {
    "company": {
      "id": 4,
      "name": "Biopac Systems",
      "slug": "biopac-systems",
      "logo": "https://domain.com/storage/industries/logo.png",
      "status": "approved"
    },
    "subscription": {
      "plan_name": "Pro Industry Annual",
      "status": "active",
      "ends_at": "2027-10-01T00:00:00.000000Z"
    },
    "metrics": {
      "total_advertisements": 14,
      "active_advertisements": 12,
      "total_likes": 340,
      "total_views": 1820,
      "remaining_slots": null
    }
  }
}
```

---

### 3.2. My Listings (Company Advertisements Table)
Fetches all products owned by the authenticated user's company with search and status filtering.

- **Method:** `GET`
- **URL:** `/api/industry/advertisements/my-listings`
- **Auth:** Required (`Bearer <token>`)

#### Query Parameters:
| Parameter | Type | Required | Values | Description |
| :--- | :--- | :--- | :--- | :--- |
| `status` | `string` | No | `'all'`, `'active'`, `'paused'`, `'draft'` | Default: `'all'` |
| `search` | `string` | No | Text | Search in title or description |
| `per_page` | `integer`| No | 1-100 | Default: `15` |
| `page` | `integer`| No | Integer | Page number |

**Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Company advertisement listings retrieved successfully.",
  "data": [
    {
      "id": 101,
      "product_name": "MP160 System with AcqKnowledge",
      "slug": "mp160-system-with-acqknowledge",
      "section_name": "Neuroscientific and Psychophysiological Equipment",
      "category_name": "Brain Scanners",
      "status": "active",
      "views_count": 49,
      "likes_count": 16,
      "image_url": "https://domain.com/storage/industry_products/mp160.webp",
      "product_url": "https://www.biopac.com/product/mp160-system/",
      "created_at": "2026-10-05T06:15:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1,
    "has_more_pages": false
  }
}
```

---

### 3.3. Create Product / Advertisement
Creates a new product under the company profile.

- **Method:** `POST`
- **URL:** `/api/industry/advertisements/create`
- **Auth:** Required (`Bearer <token>`)
- **Content-Type:** `multipart/form-data`

#### Form-Data Fields:
| Field | Type | Required | Description / Rules |
| :--- | :--- | :--- | :--- |
| `network_type` | `string` | **Yes** | `'psychology'` or `'neuroscience'` |
| `industry_type` | `string` | **Yes** | `'biotechnology'` or `'psychotropics'` |
| `section_id` | `integer` | **Yes** | Valid `industry_sections.id` |
| `category_id` | `integer` | **Yes** | Valid `industry_categories.id` |
| `product_name` | `string` | **Yes** | Max 255 chars |
| `description` | `string` | **Yes** | Detailed description (HTML or Plain Text, max 10000 chars) |
| `product_url` | `string` | No | Valid URL (optional, max 1000 chars, e.g., `https://biopac.com/item`) |
| `image` | `file` | **Yes** | Image file (`jpeg, png, jpg, gif, webp`, max: 10MB) |
| `tags` | `array` or `string` | No | e.g. `tags[0]=EEG&tags[1]=Brain` or `"EEG, Brain"` |
| `poc_name` | `string` | No | Person of Contact Name (e.g. `Sheikh Muhammad Ashik`, max: 255) |
| `poc_email` | `string` | No | Person of Contact Email (e.g. `smashik@company.com`, max: 255) |
| `poc_phone` | `string` | No | Person of Contact Phone Number (e.g. `+1 234 5678 87`, max: 50) |
| `information_confirmed` | `boolean` | **Yes** | Must be `1` or `true` |
| `status` | `string` | No | `'active'`, `'paused'`, or `'draft'`. Default: `'active'` |

**Success Response (`201 Created`):**
```json
{
  "success": true,
  "message": "Product created successfully.",
  "data": {
    "id": 102,
    "product_id": "719304",
    "product_name": "EyeLink 1000 Plus",
    "slug": "eyelink-1000-plus-39d0",
    "image_url": "https://domain.com/storage/industry_products/eyelink.webp",
    "status": "active"
  }
}
```

---

### 3.4. Get Product Data for Edit
Pre-fills the update modal / form.

- **Method:** `GET`
- **URL:** `/api/industry/advertisements/{id}`
- **Auth:** Required (`Bearer <token>`)

**Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Product edit data retrieved successfully.",
  "data": {
    "id": 102,
    "network_type": "psychology",
    "industry_type": "biotechnology",
    "section_id": 1,
    "category_id": 10,
    "product_name": "EyeLink 1000 Plus",
    "description": "High-precision eye tracking system...",
    "product_url": "https://www.sr-research.com/",
    "image_url": "https://domain.com/storage/industry_products/eyelink.webp",
    "tags": ["Eye Tracking", "Vision"],
    "information_confirmed": true,
    "status": "active"
  }
}
```

---

### 3.5. Update Product / Advertisement
Updates an existing product.  
> **Important Note for Multipart Updates:** PHP/Laravel cannot parse `multipart/form-data` natively via HTTP `PUT`. Therefore, send a **`POST`** request and include `_method: PUT` in the form body, or send standard JSON if not uploading a new image.

- **Method:** `POST` (with `_method: PUT`) or `PUT`
- **URL:** `/api/industry/advertisements/{id}/update`
- **Auth:** Required (`Bearer <token>`)
- **Content-Type:** `multipart/form-data`

#### Form-Data Fields:
All fields from Create Product are available as **optional / nullable**:
- `_method`: `PUT` (Required when using `POST` for file replacement)
- `product_name`: `string`
- `section_id`: `integer`
- `category_id`: `integer`
- `description`: `string`
- `product_url`: `string`
- `image`: `file` (Send only if replacing the image. If omitted, existing image is kept)
- `tags`: `array` or `string`
- `poc_name`: `string` (Person of Contact Name)
- `poc_email`: `string` (Person of Contact Email)
- `poc_phone`: `string` (Person of Contact Phone)
- `status`: `'active'` | `'paused'` | `'draft'`

**Success Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Product updated successfully.",
  "data": {
    "id": 102,
    "product_name": "EyeLink 1000 Plus Updated",
    "image_url": "https://domain.com/storage/industry_products/new_eyelink.webp"
  }
}
```

---

### 3.6. Delete Product / Advertisement
Removes the product, purges stored images, and updates company counters.

- **Method:** `DELETE`
- **URL:** `/api/industry/advertisements/{id}/delete`
- **Auth:** Required (`Bearer <token>`)

**Success Response (`200 OK`):**
```json
{
  "success": true,
  "message": "Product deleted successfully."
}
```

---

## 4. Frontend & Mobile Integration Guide

### 4.1. Web Feed Implementation Workflow
1. **Initial Page Load:**
   Call `GET /api/industry/products/feed?network_type=psychology&industry_type=biotechnology`.
2. **Render Sections:**
   Map over `response.data`. Each item is a section block.
3. **Render Tabs:**
   - Display a static `[ All ]` tab at index 0.
   - Follow with `section.categories` as additional tabs.
   - By default, highlight the `[ All ]` tab and render `section.products`.
4. **Tab Switch Interaction:**
   When user clicks a sub-category tab (e.g. `id: 10`):
   ```javascript
   const res = await fetch(`/api/industry/products/feed?network_type=psychology&industry_type=biotechnology&section_id=${sectionId}&category_id=${catId}`);
   // Update products state for that specific section only
   ```
5. **View More / All Tab Click:**
   When user clicks `[ All ]` inside a section or clicks "View More":
   ```javascript
   const res = await fetch(`/api/industry/products/feed?network_type=psychology&industry_type=biotechnology&section_id=${sectionId}&page=2`);
   ```

### 4.2. Mobile App (Flutter / React Native) Workflow
1. **Section Horizontal Carousels:**
   Use the default response from `GET /api/industry/products/feed?network_type=...&industry_type=...` to render vertical section titles, horizontal sub-category chips, and horizontal product cards.
2. **Infinite Scroll Catalog:**
   Call with `flat=1`:
   ```http
   GET /api/industry/products/feed?network_type=psychology&industry_type=biotechnology&flat=1&page=1
   ```
   Append new products on scroll until `has_more_pages === false`.
3. **Instant Like & View:**
   Call `POST /api/industry/products/{id}/like`. Optimistically toggle heart icon locally, then update `likes_count` and `is_liked` from the response.

---

## 5. Error Responses Reference

### `401 Unauthorized`
Returned when an authenticated route is called without a token.
```json
{
  "message": "Unauthenticated."
}
```

### `403 Forbidden (No Pro Subscription)`
Returned when a non-subscribed user tries to create/manage advertisements.
```json
{
  "success": false,
  "message": "An active Pro Industry subscription is required to perform this action."
}
```

### `422 Unprocessable Content (Validation Errors)`
Returned when required fields are missing or invalid.
```json
{
  "message": "The product name field is required. (and 1 more error)",
  "errors": {
    "product_name": ["The product name field is required."],
    "image": ["The image must be an image.", "The image must not be greater than 10240 kilobytes."]
  }
}
```
