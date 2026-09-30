# Company (Industry) API — Routes & Responses

**Base URL:** `{base_url}/api` · e.g. `http://localhost:8000/api`
**Auth:** `Authorization: Bearer <token>` on every request. User must have the `user` role.

All write endpoints use **`multipart/form-data`**, never JSON.

---

## 1. Create Company Page

`POST /api/industry/create`

| Field | Required | Rules |
|---|---|---|
| `name` | yes | string, max 255 |
| `industry` | yes | string, max 255 |
| `authorization_confirmed` | yes | truthy (`accepted`) |
| `slug` | no | alpha_dash, max 255, unique |
| `website` | no | url, max 255 |
| `address` | no | max 2000 |
| `company_size` | no | max 100 |
| `tagline` | no | max 255 |
| `description` | no | max 5000 |
| `logo` | no | image, jpg/jpeg/png/webp, max 2 MB |
| `cover_image` | no | image, jpg/jpeg/png/webp, max 5 MB |

**201**
```json
{
  "success": true,
  "message": "Company page created successfully.",
  "data": {
    "id": 1,
    "name": "Bright Labs",
    "slug": "bright-labs",
    "industry": "Software",
    "website": "https://brightlabs.test",
    "address": "Level 7, Banani, Dhaka",
    "company_size": "51-200",
    "logo": "http://localhost:8000/storage/industry/logo.png",
    "cover_image": null,
    "tagline": "We build developer tooling",
    "description": "A product studio.",
    "authorization_confirmed": 1,
    "authorization_confirmed_at": "2026-05-29T10:15:00+06:00",
    "created_at": "2026-05-29T10:15:00+06:00",
    "updated_at": "2026-05-29T10:15:00+06:00"
  }
}
```

**403** no active subscription, or plan lacks `company_page` · **409** already own a page · **500**

---

## 2. Get Company Page

`GET /api/industry/show/{industryId}`

**200**
```json
{
  "success": true,
  "message": "Company page fetched successfully.",
  "data": {
    "id": 1,
    "name": "Bright Labs",
    "slug": "bright-labs",
    "industry": "Software",
    "address": "Level 7, Banani, Dhaka",
    "website": "https://brightlabs.test",
    "company_size": "51-200",
    "logo": "http://localhost:8000/storage/industry/logo.png",
    "cover_image": null,
    "tagline": "We build developer tooling",
    "description": "A product studio.",
    "followers_count": 120,
    "is_owner": false,
    "is_following": true
  }
}
```

**404** page not found

---

## 3. Update Company Page

`POST /api/industry/update`

Same fields as create, **all optional** (partial update). Owner only.

**200** — same `data` shape as create, message `"Company page updated successfully."`
**403** not owner · **404** · **422** validation · **500**

---

## 4. Delete Company Page

`DELETE /api/industry/delete`

Owner only. Deletes the page, all its posts, media, comments and replies.

**200**
```json
{
  "success": true,
  "message": "Company page and all related data deleted successfully.",
  "data": {
    "company_id": 1,
    "deleted_posts": 12,
    "deleted_post_media": 18,
    "deleted_comment_images": 3
  }
}
```

**403** not owner · **404** · **500**

---

## 5. Follow / Unfollow

`POST /api/industry/follow/{industryId}`

Toggles. Follows if not following, unfollows if already following.

**200**
```json
{
  "success": true,
  "message": "Follow status updated successfully.",
  "data": {
    "industry_id": 1,
    "is_following": true,
    "followers_count": 121
  }
}
```

**404** page not found · **422** cannot follow your own page

> Only affects who can see `followers` visibility posts.

---

## 6. Create Company Post

`POST /api/industry/post/create`

| Field | Required | Rules |
|---|---|---|
| `visibility` | yes | `public` \| `followers` \| `private` |
| `content` | no | max 10000 |
| `media` | no | array, max 10, jpg/jpeg/png/webp/mp4/mov/webm, 100 MB each |

Requires an active subscription and an existing company page. Must send `content` **or** at least one `media` file.

**201**
```json
{
  "success": true,
  "message": "Company post created successfully.",
  "data": {
    "post_id": 1,
    "company_id": 1,
    "created_by": 12,
    "content": "We just raised our Series A.",
    "visibility": "public",
    "company_name": "Bright Labs",
    "tagline": "We build developer tooling",
    "logo": "http://localhost:8000/storage/industry/logo.png",
    "media": [
      {
        "id": 1,
        "type": "image",
        "url": "http://localhost:8000/storage/post/a.jpg",
        "sort_order": 0
      }
    ],
    "time_ago": "1 minute ago",
    "likes_count": 0,
    "comments_count": 0,
    "is_liked": false,
    "created_at": "2026-05-29T10:15:00+06:00",
    "updated_at": "2026-05-29T10:15:00+06:00"
  }
}
```

**403** no active subscription / no company page · **422** no text or media · **500**

---

## 7. List Company Page Posts

`GET /api/industry/post/view/{industryId}?per_page=10`

**200**
```json
{
  "success": true,
  "message": "Company posts fetched successfully.",
  "data": [ { "post_id": 1, "...": "same shape as above" } ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 12,
    "last_page": 2,
    "has_more_pages": true
  }
}
```

**404** page not found

> `per_page` defaults to **10**, max 100.

---

## 8. My Latest Posts

`GET /api/industry/post/recent`

Reads the caller's own company page. Hard limit of 5, no pagination.

**200**
```json
{
  "success": true,
  "message": "Latest company posts fetched successfully.",
  "data": [ { "post_id": 1, "...": "same shape as above" } ]
}
```

**404** caller has no company page

---

## 9. Update Company Post

`POST /api/industry/post/update/{postId}`

Same fields as create, all optional. Owner only.

**200** — same `data` shape as create, message `"Company post updated successfully."`
**403** not owner / no active subscription · **404** · **422** nothing to update · **500**

---

## 10. Delete Company Post

`DELETE /api/industry/post/delete/{postId}`

Owner only. Cascades to media files, comments and replies.

**200**
```json
{
  "success": true,
  "message": "Company post deleted successfully.",
  "data": { "post_id": 1 }
}
```

**403** not owner · **404** · **500**

---

## 11. Toggle Post Like

`POST /api/industry/post/like/{postId}`

**200**
```json
{
  "success": true,
  "message": "Post like updated successfully.",
  "data": {
    "post_id": 1,
    "liked": true,
    "likes_count": 6
  }
}
```

**403** post not visible to you · **404** · **500**

---

## 12. List Post Likers

`GET /api/industry/post/likes/{postId}?per_page=10`

**200**
```json
{
  "success": true,
  "message": "Post liked users fetched successfully.",
  "data": [
    {
      "id": 12,
      "username": "mahmudul",
      "name": "Mahmudul Hasan",
      "profile_image": "http://localhost:8000/storage/profile/a.png"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 6,
    "last_page": 1,
    "has_more_pages": false
  }
}
```

**403** · **404**

---

## 13. Add Comment

`POST /api/industry/post/comment/{postId}`

| Field | Required | Rules |
|---|---|---|
| `comment` | no | max 5000 |
| `image` | no | image, jpg/jpeg/png/webp, max 5 MB |

Must send `comment` **or** `image`. Post visibility enforced.

**201**
```json
{
  "success": true,
  "message": "Comment added successfully.",
  "data": {
    "id": 1,
    "post_id": 1,
    "user_id": 12,
    "user": {
      "id": 12,
      "username": "mahmudul",
      "name": "Mahmudul Hasan",
      "title": "Backend Engineer",
      "profile_image": "http://localhost:8000/storage/profile/a.png"
    },
    "comment": "Congratulations on the raise!",
    "image": null,
    "likes_count": 0,
    "replies_count": 0,
    "is_liked": false,
    "created_at": "2026-05-29T11:00:00+06:00",
    "time_ago": "1 hour ago"
  }
}
```

**403** post not visible · **404** · **422** no text or image · **500**

---

## 14. List Comments

`GET /api/industry/post/comments/{postId}?per_page=10`

Each comment embeds its replies under `replies`.

**200**
```json
{
  "success": true,
  "message": "Post comments fetched successfully.",
  "data": [
    {
      "id": 1,
      "post_id": 1,
      "user_id": 12,
      "user": { "id": 12, "username": "mahmudul", "name": "Mahmudul Hasan", "title": null, "profile_image": null },
      "comment": "Congratulations on the raise!",
      "image": null,
      "likes_count": 2,
      "replies_count": 1,
      "is_liked": false,
      "created_at": "2026-05-29T11:00:00+06:00",
      "time_ago": "1 hour ago",
      "replies": [
        {
          "id": 1,
          "post_id": 1,
          "comment_id": 1,
          "user_id": 13,
          "user": { "id": 13, "username": "kamruzzaman", "name": "Kamruzzaman Islam", "title": null, "profile_image": null },
          "comment": "Thanks! Drop us a line.",
          "image": null,
          "likes_count": 0,
          "is_liked": false,
          "created_at": "2026-05-29T11:30:00+06:00",
          "time_ago": "30 minutes ago"
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 2,
    "last_page": 1,
    "has_more_pages": false
  }
}
```

**403** post not visible · **404**

---

## 15. Toggle Comment Like

`POST /api/industry/post/comment/like/{commentId}`

**200**
```json
{
  "success": true,
  "message": "Comment like updated successfully.",
  "data": {
    "comment_id": 1,
    "liked": true,
    "likes_count": 3
  }
}
```

**403** · **404** · **500**

---

## 16. List Comment Likers

`GET /api/industry/post/comment/likes/{commentId}?per_page=10`

Same `data` shape as #12 (`id`, `username`, `name`, `profile_image`) + `pagination`.
**403** · **404**

---

## 17. Reply to Comment

`POST /api/industry/post/comment/reply/{commentId}`

| Field | Required | Rules |
|---|---|---|
| `comment` | no | max 5000 |
| `image` | no | image, max 5 MB |

> Replies live in the **`replies`** table, not `parent_id` on comments.
> The body field is named **`comment`**, not `reply` — despite the model being `Reply`.

**201**
```json
{
  "success": true,
  "message": "Reply added successfully.",
  "data": {
    "id": 1,
    "post_id": 1,
    "comment_id": 1,
    "user_id": 13,
    "user": {
      "id": 13,
      "username": "kamruzzaman",
      "name": "Kamruzzaman Islam",
      "title": null,
      "profile_image": null
    },
    "comment": "Thanks! Drop us a line.",
    "image": null,
    "likes_count": 0,
    "is_liked": false,
    "created_at": "2026-05-29T11:30:00+06:00",
    "time_ago": "30 minutes ago"
  }
}
```

**403** parent post not visible · **404** · **422** no text or image · **500**

---

## 18. List Replies

`GET /api/industry/post/comment/replies/{commentId}?per_page=10`

**200**
```json
{
  "success": true,
  "message": "Replies fetched successfully.",
  "data": [
    {
      "id": 1,
      "post_id": 1,
      "comment_id": 1,
      "user_id": 13,
      "user": { "id": 13, "username": "kamruzzaman", "name": "Kamruzzaman Islam", "title": null, "profile_image": null },
      "comment": "Thanks! Drop us a line.",
      "image": null,
      "likes_count": 0,
      "is_liked": false,
      "created_at": "2026-05-29T11:30:00+06:00",
      "time_ago": "30 minutes ago"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1,
    "has_more_pages": false
  }
}
```

**403** · **404**

---

## 19. Toggle Reply Like

`POST /api/industry/post/comment/reply/like/{replyId}`

**200**
```json
{
  "success": true,
  "message": "Reply like updated successfully.",
  "data": {
    "reply_id": 1,
    "liked": true,
    "likes_count": 1
  }
}
```

**403** · **404** · **500**

---

## 20. List Reply Likers

`GET /api/industry/post/comment/reply/likes/{replyId}?per_page=10`

Same `data` shape as #12 + `pagination`.
**403** · **404**

---

## 21. Delete Comment

`DELETE /api/industry/post/comment/{commentId}`

Deletes the comment **and all of its replies**.

**200**
```json
{
  "success": true,
  "message": "Comment and its replies deleted successfully.",
  "data": {
    "comment_id": 1,
    "deleted_count": 2,
    "comments_count": 4
  }
}
```

**403** not allowed · **404** · **500**

---

## Error Shapes

**Simple error** (403 / 404 / 409)
```json
{ "success": false, "message": "Post not found." }
```

**Validation error** (422)
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "visibility": ["The selected visibility is invalid."]
  }
}
```

**Server error** (500)
```json
{
  "success": false,
  "message": "Failed to create company post.",
  "error": null
}
```
`error` is only populated when `APP_DEBUG=true`.

---

## Gotchas

- **Post shape here ≠ newsfeed post shape.** These endpoints serialise via `IndustryController::postResource()`, which returns `post_id` and `created_by`. The newsfeed returns `id`, `user_id`, and a nested `industry` object. Don't reuse one parser for both.
- **Company page create/update no longer return `created_by`.** Removed as of this change — the owner id is not in the response. Use `data.is_owner` on `GET /show` if you need ownership.
- **`logo` / `cover_image` are passed through unchanged when already absolute.** If the stored value starts with `http`, it is returned verbatim instead of being prefixed with the disk URL. Previously this produced a doubled URL like `https://cdn/storage/https://cdn/storage/...`.
- **`visibility` is limited to `public` / `followers` / `private`** on these endpoints — `connections` and `groups` are regular-post values and will 422.
- `company_id` is always set on company posts; `company_name`, `tagline`, `logo` appear only when the company exists.
- `time_ago`, `likes_count`, `comments_count`, `is_liked` only appear on endpoints that pass `withEngagement` (create, update, list, recent). Comment/reply resources always include them.
- `per_page` default is **10**, capped at 100.
- `media` items use `type` and `url` here — not `media_type` / `file` / `thumb` as in the newsfeed resource.
