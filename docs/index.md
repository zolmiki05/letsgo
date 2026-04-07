# Letsgo – Documentation

> **What should we do next?**

Letsgo is a group activity planner. It lets any community — friends, colleagues, clubs — collect programme ideas, gather feedback from members, and reach a consensus on what to do next.

---

## Table of Contents

| Document | Description |
|---|---|
| [Installation & Operations](installation.md) | Docker setup, first boot, environment variables |
| [Architecture](architecture.md) | Tech stack, directory structure, request lifecycle |
| [Database Schema](database.md) | Tables, columns, relationships, migrations |
| [HTTP Routes](routes.md) | All endpoints, controller methods, access rules |
| [Features](features.md) | Complete feature list with detailed descriptions |
| [Admin Guide](admin-guide.md) | Admin panel, settings, user management |

---

## Quick Summary

- **Stack:** PHP 8.2, MySQL 8.x, Apache — no framework, runs in Docker
- **Auth:** Session-based, invite-code-gated registration (or open registration)
- **Version:** 1.0.0
- **UI Language:** Hungarian (`lang/hu.json`)
