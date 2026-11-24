---
version: 1
slug: "frontend-src-layouts-applayout-vue"
primary_target: "frontend/src/layouts/AppLayout.vue"
related_targets: ["frontend/src/views"]
---

# CRM app shell and all signed-in screens

Scope: the whole SPA (AppLayout shell, login, My work dashboard, clients, client, project, notifications, users,
sections, section, reports, team report). Mode: Operate. Replacement world (redesign); every feature, permission
rule and piece of copy stays. Layout may change; no backend/API changes, no new npm packages. RTL-ready CSS
(logical properties), English only for now. Build path: code-led.

Audience and task: agency staff checking their own work a few times a day: what waits on them, the clients they
brought, the projects they own or help on. Must not feel corporate/grey.

## Direction contract

THESIS: Teams are lines and projects ride them. Every team owns one line colour that follows its clients and
projects everywhere, and every project shows its lifecycle as a short route of stations. Refuses the grey
sidebar + white cards + rounded status pills admin template.

OWN-WORLD: Midnight-enamel side rail (#0B1230) with porcelain text; light porcelain work ground (#F5F6F8) with
white panels on 1px cool rules, 4px corners, no soft card shadows. Line inks scarlet, cobalt, amber, green, plum,
teal; a team's ink is fixed by its id. Barlow for body and data with tabular numerals, Barlow Semi Condensed
uppercase tracked for labels, headers and table heads (signage voice). Primary action filled midnight; scarlet
reserved for alerts/destructive; project status never reuses a line ink (team ink tints, enamel neutrals).

STORY: The visitor sees what waits on them (unread notifications, requests), finds a client by its team line,
opens it and reads each project's position on its route, then acts (approve, assign helpers, complete with
invoice items) where they stand.

FIRST VIEWPORT: 256px midnight rail at the start edge: interchange-circle mark + "CRM", nav (My work, Clients,
Notifications with scarlet count, Sections, Users, Reports per role), "Your lines" list of the user's teams as
ink bars, user plate (initials, name, role) and Log out at the foot. Work area: page title "My work" and a
status line; panel "Waiting on you" (pending project requests with inline Approve for admins and the team's
leader, then unread notifications); panel "On your lines" (the user's projects on their routes in team ink, with
client and team line); panel "Your lines" (teams, leaders link to reports).

FORM: Transit Line (catalog challenger wayfinding-cartography-signage-midnight-transit-diagram, chosen over the
assigned Division Manual; my list order: 1 job docket, 2 bilingual wayfinding, 3 carbon invoice book, 4 identity
manual, 5 pinboard, 6 ledger, 7 rubber stamps). Signature move: the project route (Requested, Approved, Working,
Done; Cancelled = suspended dashed line) drawn in the team's ink on client project rows and large on the project
page; the fill advances with a 220ms transition when status changes. seed:fe823caa3698460b

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
