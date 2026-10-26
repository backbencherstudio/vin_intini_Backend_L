# Industry Job Post Creation API Documentation

> **Target Audience:** Frontend Developers (Web / Mobile Apps), Backend Integrators, QA Engineers.  
> **Route:** `POST /api/industry/job-post/create`  
> **Controller:** `App\Http\Controllers\Api\IndustryJobPostController@store`  
> **Authentication:** Required (`Bearer <access_token>`)  
> **Headers:**
> - `Accept: application/json`
> - `Content-Type: application/json`
> - `Authorization: Bearer <your_access_token>`

---

## 1. Overview & Prerequisites

This endpoint allows verified Industry / Company users to create and publish job listings or save them as drafts.

### Prerequisites & Access Control
Before validation runs, the API enforces three levels of security checks:
1. **Authentication:** Valid user token required (`auth:api`).
2. **Profile Completion & Role:** User must have completed their profile (`profile_completed`) and possess the `role:user` role.
3. **Pro Industry Subscription:** User must have an active **Pro Industry** subscription (`PlanType::INDUSTRY`).  
   *Failure Response:* `403 Forbidden` (`"You must have an active Pro Industry subscription plan to create or publish a job post."`).
4. **Company Profile Association:** The user must have created a company profile (`$user->industry` must exist).  
   *Failure Response:* `422 Unprocessable Content` (`"Your account is not associated with any company profile. Please create a company profile first."`).

---

## 2. Operation Modes: Publish vs Draft

The API supports two distinct modes based on the request payload:
- **Publish Mode (Default):** Standard flow. All core job details are required, and `information_confirmed` must be accepted. The post is published immediately (`status: "published"`, `submitted_at: now()`).
- **Draft Mode:** Triggered when `status: "draft"` OR `action: "draft"` is passed in the request. Only `job_title` is strictly required; all other fields become optional/nullable.

---

## 3. Request Parameters & Validation Rules

| Field | Data Type | Required (Publish Mode) | Required (Draft Mode) | Validation Rules & Constraints | Description / Allowed Values |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`job_title`** | `string` | **Yes** | **Yes** | `required`, `string`, `max:255` | Title of the job vacancy (e.g., "Senior Full-Stack Engineer"). |
| **`position`** | `string` | **Yes** | No | `string`, `max:255` | Position or role designation. |
| **`category`** | `string` | **Yes** | No | `string`, `max:100` | Primary job category. |
| **`sub_category`** | `string` | No | No | `nullable`, `string`, `max:100` | Sub-category (optional). |
| **`job_description`** | `string` | **Yes** | No | `string`, `max:5000` | Detailed description, responsibilities, and requirements. |
| **`work_mode`** | `string` | **Yes** | No | `in:remote,on_site,hybrid` | Allowed values: `remote`, `on_site`, `hybrid`. |
| **`employment_type`** | `string` | **Yes** | No | `in:full_time,part_time,short_term,contract,internship` | Job employment nature. |
| **`network_type`** | `string` | **Yes** | No | `in:psychology,neuroscience` | Domain network: `psychology` or `neuroscience`. |
| **`employment_offering`** | `string` | **Yes** | No | `in:state,private` | Type of organization/offering: `state` or `private`. |
| **`state_id`** | `integer` | **Conditional** | No | `nullable`, `integer`, `exists:states,id` | **Required if `work_mode !== 'remote'` in publish mode.** ID from the `states` table. |
| **`city_id`** | `integer` | **Conditional** | No | `nullable`, `integer`, `exists:cities,id` | **Required if `work_mode !== 'remote'` in publish mode.** Must belong to the selected `state_id`. |
| **`level`** | `string` | No | No | `nullable`, `string`, `max:100` | Experience level (e.g., "Junior", "Mid-Level", "Senior", "Lead"). |
| **`experience`** | `string` | No | No | `nullable`, `string`, `max:100` | Experience required (e.g., "2-4 years", "Fresh Graduate"). |
| **`email`** | `string` | **Yes** | No | `email`, `max:255` | Contact/application destination email address. |
| **`phone_number`** | `string` | **Yes** | No | `string`, `max:50` | Contact phone number. |
| **`website`** | `string` | No | No | `nullable`, `url`, `max:255` | Company or application landing page URL. |
| **`salary_min`** | `numeric` | **Yes** | No | `numeric`, `min:0` | Minimum salary offering (e.g., `45000.00`). |
| **`salary_max`** | `numeric` | **Yes** | No | `numeric`, `gte:salary_min` | Maximum salary offering. Must be greater than or equal to `salary_min`. |
| **`salary_type`** | `string` | **Yes** | No | `in:Monthly,Yearly,Hourly` | Pay frequency: `Monthly`, `Yearly`, or `Hourly`. |
| **`location_url`** | `string` | No | No | `nullable`, `string`, `max:1000` | Google Maps or office location link. |
| **`tags`** | `array` / `string` | No | No | `nullable` | Array of strings (`["PHP", "Vue.js"]`) or comma-separated string (`"PHP, Vue.js"`). |
| **`start_date`** / **`announcement_start_date`** | `date` | No | No | `nullable`, `date` (format: `YYYY-MM-DD`) | Date when announcement starts. |
| **`end_date`** / **`announcement_end_date`** | `date` | No | No | `nullable`, `date`, `after_or_equal:start_date` | Application deadline. Must be on or after start date. |
| **`information_confirmed`** | `boolean` / `accepted` | **Yes** | No | `accepted` (`true`, `1`, `"yes"`, `"on"`) | Terms confirmation checkbox. |
| **`status`** | `string` | No | No | `in:draft,published,pending,archive,expired` | Set to `"draft"` for draft creation. Otherwise, defaults to `"published"`. |
| **`action`** | `string` | No | No | `nullable`, `string` | Optional alternate indicator for draft (e.g., `"draft"`). |

---

## 4. System-Generated Fields (Not required from Client)

The backend automatically calculates and assigns the following attributes upon creation:
- **`job_id`**: Industry-scoped unique alphanumeric identifier (e.g., `47K9M2`).
- **`slug`**: Auto-generated SEO slug formatted as `{job_title_slug}-{job_id}` (e.g., `lead-clinical-psychologist-47K9M2`).
- **`industry_id`**: Inferred from the authenticated user's associated company profile (`$user->industry->id`).
- **`created_by`**: The authenticated user's ID (`$user->id`).
- **`submitted_at`**: Set to current timestamp `now()` for published jobs (`null` for drafts).
- **`views_count`**, **`likes_count`**, **`applications_count`**: Initialized to `0`.

---

## 5. Example Request Payloads

### 5.1. Standard Job Post (Publish Mode - On-Site / Hybrid)
```json
{
  "job_title": "Lead Clinical Psychologist",
  "position": "Clinical Lead",
  "category": "Healthcare & Clinical",
  "sub_category": "Cognitive Behavioral Therapy",
  "job_description": "We are seeking an experienced Clinical Psychologist to lead our research and outpatient counseling team in our central medical facility...",
  "work_mode": "on_site",
  "employment_type": "full_time",
  "network_type": "psychology",
  "employment_offering": "private",
  "state_id": 12,
  "city_id": 145,
  "level": "Senior Level",
  "experience": "5+ Years",
  "email": "careers@neurohealth-institute.com",
  "phone_number": "+1-555-019-2834",
  "website": "https://neurohealth-institute.com/careers",
  "salary_min": 85000,
  "salary_max": 120000,
  "salary_type": "Yearly",
  "location_url": "https://maps.google.com/?q=NeuroHealth+Center",
  "tags": ["Clinical Psychology", "CBT", "Therapy", "Healthcare"],
  "announcement_start_date": "2026-11-01",
  "announcement_end_date": "2026-12-01",
  "information_confirmed": true
}
```

### 5.2. Remote Job Post (Publish Mode)
> Note: When `work_mode` is `"remote"`, `state_id` and `city_id` are **not required**.
```json
{
  "job_title": "Remote Computational Neuroscientist",
  "position": "Research Scientist",
  "category": "Neuroscience Research",
  "job_description": "Conduct deep learning and EEG data modeling remotely...",
  "work_mode": "remote",
  "employment_type": "full_time",
  "network_type": "neuroscience",
  "employment_offering": "private",
  "level": "Mid-Level",
  "experience": "3 Years",
  "email": "research@synapse-ai.io",
  "phone_number": "+1-555-332-9090",
  "salary_min": 7000,
  "salary_max": 9500,
  "salary_type": "Monthly",
  "tags": ["EEG", "Python", "Neuroscience", "Machine Learning"],
  "announcement_start_date": "2026-11-01",
  "announcement_end_date": "2026-11-30",
  "information_confirmed": true
}
```

### 5.3. Save As Draft
```json
{
  "status": "draft",
  "job_title": "Draft - Pediatric Neurologist Position",
  "job_description": "Work in progress draft..."
}
```

---

## 6. Example Response Payloads

### 6.1. Success: 201 Created (Published)
```json
{
  "success": true,
  "message": "Job post has been published successfully.",
  "data": {
    "id": 84,
    "job_id": "47K9M2",
    "slug": "lead-clinical-psychologist-47K9M2",
    "job_title": "Lead Clinical Psychologist",
    "position": "Clinical Lead",
    "category": "Healthcare & Clinical",
    "sub_category": "Cognitive Behavioral Therapy",
    "job_description": "We are seeking an experienced Clinical Psychologist...",
    "work_mode": "on_site",
    "employment_type": "full_time",
    "network_type": "psychology",
    "level": "Senior Level",
    "experience": "5+ Years",
    "employment_offering": "private",
    "email": "careers@neurohealth-institute.com",
    "phone_number": "+1-555-019-2834",
    "website": "https://neurohealth-institute.com/careers",
    "salary_min": "85000.00",
    "salary_max": "120000.00",
    "salary_type": "Yearly",
    "location_url": "https://maps.google.com/?q=NeuroHealth+Center",
    "tags": ["Clinical Psychology", "CBT", "Therapy", "Healthcare"],
    "status": "published",
    "information_confirmed": true,
    "views_count": 0,
    "likes_count": 0,
    "applications_count": 0,
    "is_liked": false,
    "is_saved": false,
    "is_applied": false,
    "application_status": null,
    "state": {
      "id": 12,
      "name": "California"
    },
    "city": {
      "id": 145,
      "name": "Los Angeles"
    },
    "industry": {
      "id": 47,
      "name": "NeuroHealth Institute",
      "slug": "neurohealth-institute",
      "logo": "https://your-domain.com/storage/industries/logos/neurohealth.png",
      "is_own_industry": true
    },
    "creator": {
      "id": 23,
      "name": "Dr. Sarah Jenkins",
      "username": "sjenkins",
      "profile_image": "https://your-domain.com/storage/profiles/sarah.jpg"
    },
    "announcement_start_date": "2026-11-01",
    "announcement_end_date": "2026-12-01",
    "submitted_at": "2026-10-26 15:35:00",
    "reviewed_at": null,
    "rejection_reason": null,
    "created_at": "2026-10-26 15:35:00"
  }
}
```

### 6.2. Success: 201 Created (Saved as Draft)
```json
{
  "success": true,
  "message": "Job post saved as draft successfully.",
  "data": {
    "id": 85,
    "job_id": "47X2L1",
    "slug": "draft-pediatric-neurologist-position-47X2L1",
    "job_title": "Draft - Pediatric Neurologist Position",
    "status": "draft",
    "information_confirmed": false,
    "submitted_at": null
  }
}
```

### 6.3. Error: 422 Unprocessable Content (Validation Failure)
```json
{
  "message": "The job title field is required. (and 5 more errors)",
  "errors": {
    "job_title": [
      "The job title field is required."
    ],
    "work_mode": [
      "The work mode field is required."
    ],
    "salary_max": [
      "The salary max must be greater than or equal to 85000."
    ],
    "state_id": [
      "The state id field is required when work mode is not remote."
    ],
    "information_confirmed": [
      "The information confirmed field must be accepted."
    ]
  }
}
```

### 6.4. Error: 403 Forbidden (Missing Pro Industry Subscription)
```json
{
  "success": false,
  "message": "You must have an active Pro Industry subscription plan to create or publish a job post."
}
```

### 6.5. Error: 422 Unprocessable Content (No Company Profile)
```json
{
  "success": false,
  "message": "Your account is not associated with any company profile. Please create a company profile first."
}
```
