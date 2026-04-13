# Features

Complete feature reference for Letsgo v1.0.3.

---

## Authentication

### Registration
- New accounts require a **single-use invite code** (12-char uppercase hex) by default.
- The admin can enable **open registration** (no code required) via the settings panel.
- The very first account is always created via a system-generated setup code and is automatically granted admin rights.
- Registration requires: **username** (3–30 chars, alphanumeric + `.` `_` `-`), **email address**, and **password** (min. 6 chars).
- Duplicate email and username are both checked and rejected with specific error messages.

### Login
- The login field accepts either an **email address** or a **username**.
- Banned accounts receive a specific error message instead of the generic "invalid credentials" message.
- After login, the user is redirected to the **originally intended page** (e.g. a group invite link) if one was saved before the login redirect.

### Logout
- Session is fully destroyed on logout.
- User is redirected to the login page.

### Password Change
- Any logged-in user can change their password at `/profile/password`.
- The current password must be verified before the new one is accepted.
- New password and confirmation must match.
- Minimum length: 6 characters.

---

## Groups

### Creating a Group
- Any authenticated user can create groups (unless restricted by the admin setting).
- Group name is the only required field.
- The creator is automatically added as the first member and becomes the **owner**.

### Group Detail Page
- Lists all **programme ideas** (events) in the group with status badges.
- Each event card shows **who added it** (username, or email if the user has no username).
- Shows the **member list** with the owner highlighted.
- Owner-only section: **invite link management** and **delete group**.

### Renaming a Group
- Only the group owner can rename a group.
- An inline form in the owner sidebar pre-fills the current name; submitting updates it immediately.

### Deleting a Group
- Only the group owner can delete a group.
- Deletion is confirmed via a browser dialog.
- Cascades: removes all members, events, responses, and invite tokens.

---

## Group Membership

### Joining via Invite Link
- The group owner generates a **24-hour invite token** from the group page.
- The link format is `/join?token=<32-char-hex>`.
- Any authenticated user who opens the link sees a confirmation page, then joins on POST.
- **Unauthenticated users** who follow an invite link are redirected to login; the token is preserved in session and the user lands on the join page after authenticating.
- Joining is idempotent (already-members are handled gracefully via `INSERT IGNORE`).
- The owner can regenerate a new token at any time; old tokens remain valid until their own expiry.

---

## Programme Ideas (Events)

### Creating an Event
- Any group member can propose a new programme idea.
- **Required:** title only.
- **Optional:** description, location, date, signup deadline, decision deadline, estimated cost, notes.
- New events start with status **IDEA**.

### Date Fields
Each date-related field supports two input modes, selectable via radio toggle:
- **Date picker** — stores a structured `DATE` value, enabling calendar display and deadline calculations.
- **Free text** — stores a `VARCHAR` for informal expressions like "next weekend", "TBD", "sometime in July".

Fields with date/text toggle:
- Event date
- Signup deadline
- Decision deadline

### Event Status Lifecycle
Only the event creator can change the status:

```
IDEA → DISCUSSING → FINAL
                  ↘ CANCELLED
```

| Status | Meaning |
|---|---|
| `IDEA` | Initial proposal, open for feedback |
| `DISCUSSING` | Being actively discussed |
| `FINAL` | Confirmed — it's happening |
| `CANCELLED` | Called off |

### Editing an Event
- Only the event creator can edit an event (`/events/{id}/edit`).
- All fields are editable: title, description, location, date, signup deadline, decision deadline, cost, notes.
- The edit form pre-fills all current values; the date/text mode is restored to whichever column is set.
- Status is not changed by editing — use the status control on the event detail page.

### Deleting an Event
- Only the event creator can delete an event.
- Cascades: removes all feedback responses.

---

## Feedback System

### Three-Scale Rating
Every group member can rate any event in their group on three independent **1–10 scales**:

| Scale | Question |
|---|---|
| **Interest** | How interested are you in this type of activity in general? |
| **Mood** | How much do you feel like it right now? |
| **Willingness** | How willing are you to actually go? |

The three scales are intentionally separate to help groups distinguish between
"we all like this idea but the timing is bad" vs. "we're ready to commit now".

### Group Averages
- Averages (rounded to 1 decimal place) and total response count are displayed on the event page.
- Shown as progress bars for visual comparison.

### Editing a Response
- Users can update their ratings at any time.
- The form pre-fills with the previously saved values.

---

## Upcoming Deadlines Widget

- The dashboard highlights events with a **signup or decision deadline in the next 7 days**, across all of the user's groups.
- Only events with a structured `DATE` deadline (not free-text) appear in this widget.
- Events with status `FINAL` or `CANCELLED` are excluded.
- Sorted by the nearest approaching deadline.

---

## Admin Panel

### Settings (`/admin/settings`)
| Setting | Default | Effect |
|---|---|---|
| Open registration | Off | When on: anyone can register without an invite code |
| Users can create groups | On | When off: only admins can create groups |

### Invite Code Management (`/admin/invites`)
- Generate single-use registration codes (12-char uppercase hex).
- View all codes: creation date, status (available / used), and which user consumed each code.
- System-generated first-boot code also appears in this list.

### User Management (`/admin/users`)
- List all registered users with their username, email, role, status, and registration date.
- **Ban** a user: prevents future logins without deleting their data.
- **Unban** a user: restores login access.
- **Delete** a user: permanently removes the account and all owned data (cascade).
- Admins cannot ban or delete their own account.

---

## UI / UX

- **SVG favicon** — blue rounded-square icon with a right-pointing arrow, served from `/assets/favicon.svg`.
- **Dark/light theme toggle** — preference saved to `localStorage`.
- **Invite link copy button** — uses Clipboard API with a "Copied!" confirmation.
- **Feedback sliders** — range inputs with live value display.
- **Responsive layout** — works on mobile (single column) and desktop (multi-column grid).
- **Flash messages** — one-request session messages for success/error feedback after form submissions.
- **Hungarian UI** — all strings in `lang/hu.json`; new languages supported by adding a new JSON file.
