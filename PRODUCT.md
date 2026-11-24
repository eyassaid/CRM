# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Staff of an agency / services firm that sells and delivers projects to outside clients. Three roles
(`App\Enums\UserRole`):

- **Admins** run the whole firm: sections, teams, users, every client and project, all reports.
- **Team leaders** run one or more teams: they see all of their teams' clients and projects, approve project
  requests, assign helpers, add employees, and read their teams' reports.
- **Employees** bring in clients (they own the clients they brought), request projects for them, and work on the
  projects a leader assigns them to as helpers.

The job is day-to-day operational work at a desk: recording clients, moving projects through their status,
completing them with invoice line items, and checking what needs attention.

## Product Purpose

An internal CRM that tracks the path from a client relationship to delivered, invoiced work: client → project →
invoice, organized by section → team → member. Success means everyone sees exactly the clients and projects they
are responsible for, requests and approvals move without side channels, and completed work produces an invoice and
notifies the right people automatically.

## Positioning

Built around how a services firm is actually organized: ownership follows whoever brought the client (never
reassigned), leaders approve and staff the work, and completing a project is what produces the invoice. Visibility
is role- and team-scoped, not global.

## Operating Context

- Project lifecycle: `not_started` → `working_on_it` → `done` (or `cancelled`). A project an employee creates is a
  pending request until the team leader or an admin approves it; pending projects get no invoices. Moving a project
  to `done` requires invoice items (name, cost) and generates a PDF invoice.
- Notifications: project assignment (email) and project completion (in-app + email) to admins and the team leader.
- Reports: per-team client/project counts, projects by status, and clients brought in per member.
- Files: client and project images; invoice PDFs.

## Capabilities and Constraints

- Vue 3 SPA (`frontend/`) over a Laravel JSON API (`backend/`); Tailwind CSS 3 with `@tailwindcss/forms`. No public
  registration: admins and team leaders create accounts.
- The UI hides what a user cannot open; navigation must stay in sync with backend policies.
- **Languages: English and Arabic, including right-to-left layout.** All future UI work must be direction-aware
  (logical properties / `rtl:` variants, mirrored layout, numerals and dates handled per locale). Arabic is not
  implemented yet (currently `lang="en"` only).
- The bottom-right "API responses" panel (`ResponseLog`) is a development aid, not part of the product. Design work
  should not treat it as a feature and may scope it to development builds.
- Undecided: the product name (currently the placeholder "CRM"); currency and invoice locale.

## Brand Commitments

None yet. No name, logo, or brand assets exist.

## Evidence on Hand

No real client data, testimonials, or brand assets. The seeder creates a demo admin only. Do not fabricate firm
names, clients, figures, or logos as if they were real.

## Product Principles

1. Show each person their own work first: what they own, what they help on, what waits on their approval.
2. Status and the next action are always clear: pending, in progress, done, cancelled, and who must act.
3. Mirror the firm's real structure and permissions; never show an action the user cannot take.
4. Built for both English and Arabic readers from the start, not translated later.

## Accessibility & Inclusion

Full RTL support for Arabic alongside LTR English. No other product-specific standard has been set.
