# Admin Guide

This guide covers everything an admin can do in Letsgo.
The admin panel is accessible from the navigation bar when logged in with an admin account.

---

## Getting Admin Access

The **first user to register** is automatically granted admin rights. This happens
via the system-generated setup invite code shown on the login page on first boot.

Additional admin accounts can only be set by directly updating the database:
```sql
UPDATE users SET is_admin = 1 WHERE email = 'user@example.com';
```
There is currently no UI for promoting/demoting admins (intentional — keep the admin count small).

---

## Settings (`/admin/settings`)

The settings page is the admin panel home. It contains two toggles and links to the other admin sub-pages.

### Open Registration
**Default: Off (invite-only)**

| State | Effect |
|---|---|
| Off | Users must supply a valid invite code to register |
| On | Anyone can register without a code |

Use "On" for a controlled rollout or internal tool where all users are trusted.
Use "Off" (default) to control exactly who can join — useful for private groups.

> **Note:** The very first account always requires the system setup code, regardless of this setting.

### Users Can Create Groups
**Default: On**

| State | Effect |
|---|---|
| On | Any logged-in user can create new groups |
| Off | Only admin accounts can create groups |

Turn this off if you want to maintain a curated set of groups and prevent users from creating arbitrary ones.

---

## Invite Code Management (`/admin/invites`)

### Generating a Code
Click **"Kód generálása"** to create a new single-use registration code.

The code is a 12-character uppercase hex string (e.g. `A1B2C3D4E5F6`).
Share it with the person who should register — they enter it on the `/register` page.

### Code Lifecycle
- Each code can be used **once**.
- After use, the code is marked as consumed and the consuming user's email is shown.
- Codes do **not expire** (unlike group invite links which expire in 24h).

### System Code
The auto-generated first-boot code appears in this list with no creator (system-generated).
Once consumed it is shown as "used" like any other code.

---

## User Management (`/admin/users`)

### Viewing Users
The user list shows for each account:
- ID, username, email
- Role badge (admin / regular)
- Status badge (active / banned)
- Registration date

### Banning a User
Click **"Tiltás"** next to the user. This sets `is_banned = 1`.

**Effect:**
- The user's data is preserved.
- The user **cannot log in** — `User::verify()` returns `null` for banned accounts.
- Active sessions are **not immediately terminated** (the user stays logged in until their session expires or they log out). To force logout, you'd need to delete their session from the server.

### Unbanning a User
Click **"Tiltás feloldása"** to restore login access.

### Deleting a User
Click **"Törlés"** and confirm the dialog.

**What gets deleted (cascade):**
- The user account
- All groups the user owns → their events, responses, invite tokens, memberships
- The user's membership records in other groups
- The user's event responses
- App invite codes created by or consumed by this user are `SET NULL` (codes are kept for audit, but the user reference is cleared)

> **Warning:** This is permanent and cannot be undone.

### Self-Protection
Admins cannot ban or delete their own account. The action buttons are hidden for the current user's row, and the controller enforces this check server-side as well.

---

## First Boot Checklist

1. Start the Docker stack: `docker compose up -d`
2. Open `http://localhost:8080` — the setup code banner appears on the login page.
3. Click **"Admin fiók létrehozása →"** and register with the displayed code.
4. Go to **Admin → Settings** and configure:
   - Registration mode (open or invite-only)
   - Group creation permission
5. If invite-only: go to **Admin → Invite Codes** and generate codes for your users.
6. Share invite codes or the registration link with users.
