# HTTP Routes

All routes are registered in `public/index.php`. The router uses `{param}` placeholders
that become named captures extracted into the `$params` array passed to the controller.

---

## Authentication

| Method | Path | Controller | Auth | Description |
|---|---|---|---|---|
| GET | `/login` | `AuthController@loginForm` | — | Login page |
| POST | `/login` | `AuthController@login` | — | Process credentials |
| GET | `/register` | `AuthController@registerForm` | — | Registration page |
| POST | `/register` | `AuthController@register` | — | Create account |
| POST | `/logout` | `AuthController@logout` | User | Destroy session |
| GET | `/profile/password` | `AuthController@passwordForm` | User | Password change form |
| POST | `/profile/password` | `AuthController@changePassword` | User | Process password change |

---

## Dashboard

| Method | Path | Controller | Auth | Description |
|---|---|---|---|---|
| GET | `/` | `DashboardController@index` | User | Groups + upcoming deadlines |

---

## Admin

| Method | Path | Controller | Auth | Description |
|---|---|---|---|---|
| GET | `/admin/settings` | `AdminController@settings` | Admin | Settings overview |
| POST | `/admin/settings` | `AdminController@saveSettings` | Admin | Save settings |
| GET | `/admin/invites` | `AdminController@invites` | Admin | Invite code list |
| POST | `/admin/invites/generate` | `AdminController@generateInvite` | Admin | Generate invite code |
| GET | `/admin/users` | `AdminController@users` | Admin | User list |
| POST | `/admin/users/{id}/ban` | `AdminController@banUser` | Admin | Ban user |
| POST | `/admin/users/{id}/unban` | `AdminController@unbanUser` | Admin | Unban user |
| POST | `/admin/users/{id}/delete` | `AdminController@deleteUser` | Admin | Delete user |

---

## Groups

| Method | Path | Controller | Auth | Description |
|---|---|---|---|---|
| GET | `/groups/create` | `GroupController@createForm` | User* | New group form |
| POST | `/groups/create` | `GroupController@create` | User* | Create group |
| GET | `/groups/{id}` | `GroupController@show` | Member | Group detail |
| POST | `/groups/{id}/delete` | `GroupController@delete` | Owner | Delete group |
| POST | `/groups/{id}/invite` | `GroupController@generateInvite` | Owner | Generate 24h join link |

\* Subject to the `users_can_create_groups` setting (admin-only when disabled).

---

## Events

| Method | Path | Controller | Auth | Description |
|---|---|---|---|---|
| GET | `/groups/{id}/events/create` | `EventController@createForm` | Member | New event form |
| POST | `/groups/{id}/events/create` | `EventController@create` | Member | Submit event |
| GET | `/events/{id}` | `EventController@show` | Member | Event detail + feedback |
| GET | `/events/{id}/edit` | `EventController@editForm` | Creator | Edit event form |
| POST | `/events/{id}/edit` | `EventController@edit` | Creator | Submit edited event |
| POST | `/events/{id}/delete` | `EventController@delete` | Creator | Delete event |
| POST | `/events/{id}/status` | `EventController@updateStatus` | Creator | Change status |
| POST | `/events/{id}/feedback` | `EventController@saveFeedback` | Member | Submit/update rating |

---

## Invite (Group Join)

| Method | Path | Controller | Auth | Description |
|---|---|---|---|---|
| GET | `/join` | `InviteController@join` | User | Show join confirmation |
| POST | `/join` | `InviteController@join` | User | Execute group join |

**Token:** passed as `?token=<32-char-hex>` query parameter.  
**Unauthenticated:** `requireAuth()` saves `/join?token=…` to session and redirects to `/login`; after login the user is sent back to the invite page.

---

## Auth Levels

| Level | Requirement |
|---|---|
| — | No authentication required |
| User | Valid session (`Session::userId()` is non-null) |
| Member | User + member of the specific group |
| Owner | User + `groups.owner_id` matches the current user |
| Creator | User + `events.creator_id` matches the current user |
| Admin | User + `users.is_admin = 1` |

---

## 404 Handling

Any unmatched route returns HTTP 404 and renders `src/Views/errors/404.php`.
