# Company Comment & Reply API Documentation

> **Target Audience:** Frontend Developers (Web / Mobile Apps), QA Engineers, and Backend Developers.  
> **Base URL:** `https://your-domain.com/api` (or local environment: `http://localhost:8000/api`)  
> **Headers Required:**
> - `Accept: application/json`
> - `Authorization: Bearer <access_token>`

---

## 1. Overview & Architecture

When a **Pro Industry User** creates posts on behalf of their company page (`Industry`), they frequently need to interact with comments as the **Company** (showing Company Name, Logo, and Company Badge) rather than their personal profile.

### Core Implementation Pattern
* **Database Accountability:** Comments and replies retain the authenticated `user_id` (for internal auditing, moderation, and rate limiting).
* **Company Association:** A nullable `industry_id` column links the comment/reply to the company page.
* **Default is Always Personal User:** By default across the app (newsfeed, exploring, timeline), actions are performed as the **Personal User** (`industry_id: null`). This avoids accidental brand posts.
* **Company Context (`as_company: true`):** When a company admin is on their **Company Profile** or management dashboard, the frontend explicitly sends `"as_company": true`.
* **Security & Authorization:** Only the verified creator/owner of the company can act on its behalf. Attempting to reply/comment as a company without authorization returns `403 Forbidden`.
* **Zero Breaking Changes:** Existing frontends reading `item.user.name` and `item.user.profile_image` automatically receive the company's branding when `is_company = true`.

---

## 2. Summary of Affected Routes

| Method | Endpoint | Controller Action | Description | Key Changes |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/industry/post/comment/reply/{commentId}` | `IndustryController@replyComment` | Add a reply to a comment | Accepts `"as_company": true` when replying from company profile view (defaults to `false`). |
| `GET` | `/api/industry/post/comment/replies/{commentId}` | `IndustryController@replyList` | Get paginated replies for a comment | Eager loads `industry`; returns company identity for company replies. |
| `POST` | `/api/industry/post/comment/{postId}` | `IndustryController@storeComment` | Add a comment to a company post | Accepts `"as_company": true` when commenting from company profile view (defaults to `false`). |
| `GET` | `/api/industry/post/comments/{postId}` | `IndustryController@commentList` | Get comments & latest 3 replies | Eager loads `industry` on comments and nested replies. |
| `GET` | `/api/reply-list/{comment}` | `CommentController@replyList` | Generic reply list | Returns company identity and `is_company_reply` for company replies. |

---

## 3. Detailed Endpoint Documentation

### 3.1. Add a Reply to a Comment
Post a reply to an existing comment on a company post.

- **Method:** `POST`
- **URL:** `/api/industry/post/comment/reply/{commentId}`
- **Auth:** Required (`Bearer <token>`)

#### Request Body Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `comment` | `string` | Optional* | `null` | Reply text content (max 5000 chars). *Must provide either `comment` or `image`. |
| `image` | `file` | Optional* | `null` | Image attachment (`jpg, jpeg, png, webp`, max 5MB). |
| `as_company` | `boolean` | Optional | `false` | Pass `true` to reply as the Company. Defaults to `false` (Personal User). |
| `industry_id` | `integer` | Optional | `null` | Specific company ID (must be owned by the authenticated user). |

#### Request Examples:

**Scenario A: Replying as Company (when on Company Profile view)**
```json
{
  "comment": "Thank you for reaching out! Our team is looking into this.",
  "as_company": true
}
```

**Scenario B: Replying as Personal Profile (Default everywhere else)**
```json
{
  "comment": "Speaking personally as John, I love this idea!"
}
```

#### Successful Response (`201 Created` - Company Reply):
```json
{
  "success": true,
  "message": "Reply added successfully.",
  "data": {
    "id": 42,
    "post_id": 10,
    "comment_id": 5,
    "user_id": 1,
    "industry_id": 3,
    "is_company_reply": true,
    "user": {
      "id": 3,
      "username": "acme-biotech",
      "name": "Acme Biotech",
      "title": "Pioneering Neurotherapeutics",
      "profile_image": "https://domain.com/storage/industries/logos/acme.png",
      "is_company": true
    },
    "industry": {
      "id": 3,
      "name": "Acme Biotech",
      "slug": "acme-biotech",
      "tagline": "Pioneering Neurotherapeutics",
      "logo": "https://domain.com/storage/industries/logos/acme.png"
    },
    "author": {
      "type": "company",
      "id": 3,
      "name": "Acme Biotech",
      "username": "acme-biotech",
      "title": "Pioneering Neurotherapeutics",
      "profile_image": "https://domain.com/storage/industries/logos/acme.png",
      "is_company": true,
      "posted_by": {
        "id": 1,
        "name": "John Doe"
      }
    },
    "comment": "Thank you for reaching out! Our team is looking into this.",
    "image": null,
    "likes_count": 0,
    "is_liked": false,
    "created_at": "2026-10-14T08:25:00.000000Z",
    "time_ago": "1 second ago"
  }
}
```

#### Error Responses:
* `403 Forbidden` (Unauthorized company attempt):
  ```json
  {
    "success": false,
    "message": "You are not authorized to reply on behalf of this company."
  }
  ```
* `422 Unprocessable Content`:
  ```json
  {
    "success": false,
    "message": "Reply must contain text or image."
  }
  ```

---

### 3.2. Get Comment Replies
Fetches all replies for a specific comment under a company post with pagination.

- **Method:** `GET`
- **URL:** `/api/industry/post/comment/replies/{commentId}`
- **Auth:** Required (`Bearer <token>`)

#### Query Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `per_page` | `integer` | No | `10` | Number of replies per page. |
| `page` | `integer` | No | `1` | Page number. |

#### Successful Response (`200 OK`):
```json
{
  "success": true,
  "message": "Comment replies fetched successfully.",
  "data": [
    {
      "id": 42,
      "post_id": 10,
      "comment_id": 5,
      "user_id": 1,
      "industry_id": 3,
      "is_company_reply": true,
      "user": {
        "id": 3,
        "username": "acme-biotech",
        "name": "Acme Biotech",
        "title": "Pioneering Neurotherapeutics",
        "profile_image": "https://domain.com/storage/industries/logos/acme.png",
        "is_company": true
      },
      "industry": {
        "id": 3,
        "name": "Acme Biotech",
        "slug": "acme-biotech",
        "tagline": "Pioneering Neurotherapeutics",
        "logo": "https://domain.com/storage/industries/logos/acme.png"
      },
      "author": {
        "type": "company",
        "id": 3,
        "name": "Acme Biotech",
        "username": "acme-biotech",
        "title": "Pioneering Neurotherapeutics",
        "profile_image": "https://domain.com/storage/industries/logos/acme.png",
        "is_company": true,
        "posted_by": {
          "id": 1,
          "name": "John Doe"
        }
      },
      "comment": "Thank you for reaching out! Our team is looking into this.",
      "image": null,
      "likes_count": 4,
      "is_liked": true,
      "created_at": "2026-10-14T08:25:00.000000Z",
      "time_ago": "2 hours ago"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1
  }
}
```

---

### 3.3. Add a Comment to a Company Post
Add a parent comment to a company post.

- **Method:** `POST`
- **URL:** `/api/industry/post/comment/{postId}`
- **Auth:** Required (`Bearer <token>`)

#### Request Body Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `comment` | `string` | Optional* | `null` | Comment text content (max 5000 chars). *Must provide either `comment` or `image`. |
| `image` | `file` | Optional* | `null` | Image attachment (`jpg, jpeg, png, webp`, max 5MB). |
| `as_company` | `boolean` | Optional | `false` | Pass `true` to comment as the Company. Defaults to `false` (Personal User). |
| `industry_id` | `integer` | Optional | `null` | Specific company ID (must be owned by the authenticated user). |

#### Successful Response (`201 Created`):
```json
{
  "success": true,
  "message": "Comment added successfully.",
  "data": {
    "id": 5,
    "post_id": 10,
    "user_id": 1,
    "industry_id": 3,
    "is_company_comment": true,
    "user": {
      "id": 3,
      "username": "acme-biotech",
      "name": "Acme Biotech",
      "title": "Pioneering Neurotherapeutics",
      "profile_image": "https://domain.com/storage/industries/logos/acme.png",
      "is_company": true
    },
    "industry": {
      "id": 3,
      "name": "Acme Biotech",
      "slug": "acme-biotech",
      "tagline": "Pioneering Neurotherapeutics",
      "logo": "https://domain.com/storage/industries/logos/acme.png"
    },
    "author": {
      "type": "company",
      "id": 3,
      "name": "Acme Biotech",
      "username": "acme-biotech",
      "title": "Pioneering Neurotherapeutics",
      "profile_image": "https://domain.com/storage/industries/logos/acme.png",
      "is_company": true,
      "posted_by": {
        "id": 1,
        "name": "John Doe"
      }
    },
    "comment": "Official announcement regarding our clinical trial.",
    "image": null,
    "likes_count": 0,
    "replies_count": 0,
    "is_liked": false,
    "created_at": "2026-10-14T08:00:00.000000Z",
    "time_ago": "1 minute ago"
  }
}
```

---

### 3.4. Get Post Comments (with Nested Replies)
Fetches paginated comments for a company post, with the top 3 most recent replies embedded per comment.

- **Method:** `GET`
- **URL:** `/api/industry/post/comments/{postId}`
- **Auth:** Required (`Bearer <token>`)

Both the top-level comment items and the nested `replies` array include the `is_company_reply`, `is_company_comment`, `industry`, and company profile under `user`.

---

## 4. Frontend Integration Guide

### 4.1. Identity Switcher in Reply Input (UI Pattern)
When an admin/owner views a post belonging to their company, render an identity picker above or inside the comment input:

```
[ Company Logo ] Replying as: Acme Biotech ▾
                 --------------------------
                 ✓ Acme Biotech (Company)
                   John Doe (Personal)
[ Write a reply...                                              ] [ Send ]
```

- When **"Acme Biotech"** is selected $\rightarrow$ send payload with `"as_company": true`.
- When **"John Doe"** is selected $\rightarrow$ omit `as_company` or send `"as_company": false`.

### 4.2. Displaying Company Replies in Feed
In your comment card component:

```javascript
const isCompany = reply.is_company_reply || reply.user?.is_company;

const avatarUrl = isCompany ? reply.industry?.logo : reply.user?.profile_image;
const displayName = isCompany ? reply.industry?.name : reply.user?.name;
const subtitle = isCompany ? reply.industry?.tagline : reply.user?.title;

return (
  <div className="reply-card">
    <img src={avatarUrl} alt={displayName} className="avatar" />
    <div className="content">
      <div className="header">
        <span className="name">{displayName}</span>
        {isCompany && <span className="badge company-badge">Company</span>}
      </div>
      {subtitle && <span className="subtitle">{subtitle}</span>}
      <p className="body">{reply.comment}</p>
    </div>
  </div>
);
```

---

## 5. Database Schema & Models Reference

* **Migration File:** [2026_10_14_081945_add_industry_id_to_comments_and_replies_tables.php](file:///Users/svad/Desktop/vin_intini_Backend_L/database/migrations/2026_10_14_081945_add_industry_id_to_comments_and_replies_tables.php)
* **Models:**
  - [Comment.php](file:///Users/svad/Desktop/vin_intini_Backend_L/app/Models/Comment.php) $\rightarrow$ `industry()` relationship, `isCompanyComment()` helper.
  - [Reply.php](file:///Users/svad/Desktop/vin_intini_Backend_L/app/Models/Reply.php) $\rightarrow$ `industry()` relationship, `isCompanyReply()` helper.
* **Controllers:**
  - [IndustryController.php](file:///Users/svad/Desktop/vin_intini_Backend_L/app/Http/Controllers/Auth/IndustryController.php)
  - [CommentController.php](file:///Users/svad/Desktop/vin_intini_Backend_L/app/Http/Controllers/Api/CommentController.php)
