# Newsfeed (`GET /api/newsfeed`) Response Reference

On the Newsfeed (`GET /api/newsfeed`), each post item in `data` indicates what kind of post it is through the **`group`** and **`industry`** fields:

| Post Type | `visibility` | `group` | `industry` |
| :--- | :---: | :---: | :---: |
| **Regular User Post** | `"public"` or `"connections"` | `null` | `null` |
| **Group Post** | `"groups"` | `{ "id", "name", "logo" }` | `null` |
| **Pro Industry Post** | `"public"` or `"followers"` | `null` | `{ "id", "name", "slug", "tagline", "logo", "is_following" }` |

---

### Type 1: Regular User Post
* Both `group` and `industry` are `null`.

```json
{
  "id": 101,
  "user": {
    "id": 1,
    "username": "johndoe",
    "first_name": "John",
    "last_name": "Doe",
    "profile_image": "users/avatars/john.png",
    "title": "Senior Software Architect"
  },
  "description": "Just deployed our new search service update today!",
  "visibility": "public",
  "who_can_comment": "everyone",
  "total_like": 8,
  "total_comment": 1,
  "liked_by_me": false,
  "relationship_status": "connected",
  "media": [],
  "group": null,
  "industry": null,
  "created_at": "2026-10-01T10:15:00.000000Z",
  "can_edit": true,
  "can_delete": true
}
```

---

### Type 2: Group Post
* `visibility` is `"groups"`.
* **`group`** is populated with the group information.
* `industry` is `null`.

```json
{
  "id": 102,
  "user": {
    "id": 4,
    "username": "sarahconnor",
    "first_name": "Sarah",
    "last_name": "Connor",
    "profile_image": "users/avatars/sarah.png",
    "title": "DevOps Engineer"
  },
  "description": "What orchestration tools are you using for Laravel Octane in production?",
  "visibility": "groups",
  "who_can_comment": "everyone",
  "total_like": 14,
  "total_comment": 5,
  "liked_by_me": true,
  "relationship_status": "not_connected",
  "media": [],
  "group": {
    "id": 12,
    "name": "Cloud & DevOps Community",
    "logo": "groups/logos/devops.png"
  },
  "industry": null,
  "created_at": "2026-10-01T11:30:00.000000Z",
  "can_edit": false,
  "can_delete": false
}
```

---

### Type 3: Pro Industry (Company) Post
* `visibility` is `"public"` or `"followers"`.
* **`industry`** is populated with company information (name, slug, tagline, logo, and `is_following`).
* `group` is `null`.

```json
{
  "id": 103,
  "user": {
    "id": 2,
    "username": "alexfounder",
    "first_name": "Alex",
    "last_name": "Vance",
    "title": "Startup Founder"
  },
  "description": "We are proud to announce that Bright Labs has expanded our AI research team!",
  "visibility": "public",
  "who_can_comment": "everyone",
  "total_like": 32,
  "total_comment": 7,
  "liked_by_me": true,
  "relationship_status": "connected",
  "media": [
    {
      "id": 14,
      "post_id": 103,
      "file_path": "posts/banner.png",
      "type": "image",
      "order": 0,
      "url": "https://api.example.com/storage/posts/banner.png"
    }
  ],
  "group": null,
  "industry": {
    "id": 5,
    "name": "Bright Labs",
    "slug": "bright-labs",
    "tagline": "Innovating Next-Gen Solutions",
    "logo": "https://api.example.com/storage/industries/logos/brightlabs.png",
    "is_following": false
  },
  "created_at": "2026-10-01T12:00:00.000000Z",
  "can_edit": true,
  "can_delete": true
}
```

---

### Complete Newsfeed API Response (All 3 Types Together)

```json
{
  "success": true,
  "message": "Feed fetched successfully",
  "data": [
    {
      "id": 103,
      "user": {
        "id": 2,
        "username": "alexfounder",
        "first_name": "Alex",
        "last_name": "Vance",
        "profile_image": "users/avatars/alex.png",
        "title": "Startup Founder"
      },
      "description": "We are proud to announce that Bright Labs has expanded our AI research team!",
      "visibility": "public",
      "who_can_comment": "everyone",
      "total_like": 32,
      "total_comment": 7,
      "liked_by_me": true,
      "relationship_status": "connected",
      "media": [
        {
          "id": 14,
          "post_id": 103,
          "file_path": "posts/banner.png",
          "type": "image",
          "order": 0,
          "url": "https://api.example.com/storage/posts/banner.png"
        }
      ],
      "group": null,
      "industry": {
        "id": 5,
        "name": "Bright Labs",
        "slug": "bright-labs",
        "tagline": "Innovating Next-Gen Solutions",
        "logo": "https://api.example.com/storage/industries/logos/brightlabs.png",
        "is_following": false
      },
      "created_at": "2026-10-01T12:00:00.000000Z",
      "can_edit": true,
      "can_delete": true
    },
    {
      "id": 102,
      "user": {
        "id": 4,
        "username": "sarahconnor",
        "first_name": "Sarah",
        "last_name": "Connor",
        "profile_image": "users/avatars/sarah.png",
        "title": "DevOps Engineer"
      },
      "description": "What orchestration tools are you using for Laravel Octane in production?",
      "visibility": "groups",
      "who_can_comment": "everyone",
      "total_like": 14,
      "total_comment": 5,
      "liked_by_me": true,
      "relationship_status": "not_connected",
      "media": [],
      "group": {
        "id": 12,
        "name": "Cloud & DevOps Community",
        "logo": "groups/logos/devops.png"
      },
      "industry": null,
      "created_at": "2026-10-01T11:30:00.000000Z",
      "can_edit": false,
      "can_delete": false
    },
    {
      "id": 101,
      "user": {
        "id": 1,
        "username": "johndoe",
        "first_name": "John",
        "last_name": "Doe",
        "profile_image": "users/avatars/john.png",
        "title": "Senior Software Architect"
      },
      "description": "Just deployed our new search service update today!",
      "visibility": "public",
      "who_can_comment": "everyone",
      "total_like": 8,
      "total_comment": 1,
      "liked_by_me": false,
      "relationship_status": "connected",
      "media": [],
      "group": null,
      "industry": null,
      "created_at": "2026-10-01T10:15:00.000000Z",
      "can_edit": true,
      "can_delete": true
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 3,
    "last_page": 1
  }
}
```
