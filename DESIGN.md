---
name: CRM
description: An agency's internal CRM drawn as a transit network, where teams are lines and projects ride them.
colors:
  enamel-950: "#0B1230"
  enamel-800: "#1C2752"
  enamel-700: "#2A376A"
  enamel-300: "#9AA6C7"
  enamel-200: "#C3CBE0"
  porcelain: "#F5F6F8"
  panel-white: "#FFFFFF"
  rule: "#D9DEE7"
  ink: "#141B34"
  ink-muted: "#55607A"
  scarlet: "#D21F2B"
  scarlet-dark: "#A8141F"
  scarlet-soft: "#FDECEC"
  cobalt: "#1E5BFF"
  cobalt-dark: "#1747C8"
  cobalt-soft: "#EAF0FF"
  line-amber: "#F2B100"
  line-green: "#1F9D55"
  line-plum: "#7B3FA0"
  line-teal: "#0E8C8C"
  line-orange: "#E8590C"
  line-brown: "#8D5A2B"
typography:
  display:
    fontFamily: "Barlow Semi Condensed, Noto Kufi Arabic, ui-sans-serif, system-ui, sans-serif"
    fontSize: "2.25rem"
    fontWeight: 700
    lineHeight: 1.11
    letterSpacing: "0.025em"
  headline:
    fontFamily: "Barlow Semi Condensed, Noto Kufi Arabic, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 600
    lineHeight: 1.5
    letterSpacing: "0.05em"
  title:
    fontFamily: "Barlow, Noto Kufi Arabic, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 500
    lineHeight: 1.5
  body:
    fontFamily: "Barlow, Noto Kufi Arabic, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
  body-sm:
    fontFamily: "Barlow, Noto Kufi Arabic, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
  label:
    fontFamily: "Barlow Semi Condensed, Noto Kufi Arabic, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 600
    lineHeight: 1.43
    letterSpacing: "0.05em"
  label-sm:
    fontFamily: "Barlow Semi Condensed, Noto Kufi Arabic, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.33
    letterSpacing: "0.05em"
  data:
    fontFamily: "Barlow, Noto Kufi Arabic, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.43
    fontFeature: "tnum"
rounded:
  sm: "4px"
  full: "9999px"
spacing:
  xs: "8px"
  sm: "12px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  rail: "256px"
  content-max: "1280px"
components:
  button-primary:
    backgroundColor: "{colors.enamel-950}"
    textColor: "{colors.panel-white}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "8px 16px"
  button-primary-hover:
    backgroundColor: "{colors.enamel-700}"
  button-primary-active:
    backgroundColor: "{colors.enamel-800}"
  button-secondary:
    backgroundColor: "{colors.panel-white}"
    textColor: "{colors.enamel-950}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "8px 16px"
  button-secondary-hover:
    backgroundColor: "{colors.porcelain}"
  panel:
    backgroundColor: "{colors.panel-white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.sm}"
    padding: "24px"
  input:
    backgroundColor: "{colors.panel-white}"
    textColor: "{colors.ink}"
    rounded: "{rounded.sm}"
  field-label:
    textColor: "{colors.ink-muted}"
    typography: "{typography.label}"
  table-head:
    textColor: "{colors.ink-muted}"
    typography: "{typography.label-sm}"
    padding: "8px 12px"
  table-cell:
    textColor: "{colors.ink}"
    typography: "{typography.data}"
    padding: "12px 12px"
  link:
    textColor: "{colors.cobalt-dark}"
    typography: "{typography.body-sm}"
  link-hover:
    textColor: "{colors.enamel-950}"
  link-danger:
    textColor: "{colors.scarlet-dark}"
  error-banner:
    backgroundColor: "{colors.scarlet-soft}"
    textColor: "{colors.scarlet-dark}"
    rounded: "{rounded.sm}"
    padding: "8px 12px"
  side-rail:
    backgroundColor: "{colors.enamel-950}"
    textColor: "{colors.enamel-200}"
    width: "{spacing.rail}"
  nav-item-hover:
    backgroundColor: "{colors.enamel-800}"
    textColor: "{colors.panel-white}"
  unread-count:
    backgroundColor: "{colors.scarlet}"
    textColor: "{colors.panel-white}"
    rounded: "{rounded.full}"
    padding: "0 8px"
---

# Design System: CRM

## Overview

**Creative North Star: "The Transit Line"**

The firm is drawn as a transit network. Every team owns one line colour, fixed by its id, and that ink follows the team's clients and projects onto every screen as a short bar beside the name. Every project is a short route of four stations (Requested, Approved, Working, Done) drawn in its team's ink, so status is read as a position on a line rather than as a coloured label. The shell is a midnight-enamel side rail, the kind of dark enamel a station sign is printed on; the work area is a cool porcelain ground with white panels on thin cool rules.

The voice is signage: condensed, uppercase, tracked labels for headings, navigation, buttons and table heads, against a plain humanist body face for names, sentences and figures. Density is operational, not airy: panels are dense lists divided by hairlines, figures line up in tabular columns, and colour is spent on lines and on the few things that need a person (unread notifications, errors, destructive actions). The system refuses the grey sidebar, white cards and rounded status pills of the stock admin template.

The world is built for English and Arabic from the start: layout is written in logical directions so it mirrors under right-to-left without a second stylesheet.

**Key Characteristics:**
- Midnight-enamel rail at the start edge, porcelain work ground, white panels on 1px cool rules.
- A team's line ink, fixed by id, marks its clients and projects everywhere.
- Project status is a route of stations in team ink, never a coloured pill.
- Signage voice (condensed, uppercase, tracked) for labels; humanist body face with tabular figures for data.
- Flat surfaces, 4px corners, no soft shadows.
- Direction-aware by construction (logical properties, mirrored spacing).

## Colors

A deep enamel-and-porcelain base carrying a set of saturated transit inks, each with one job.

### Primary
- **Midnight Enamel** (enamel-950): the side rail, the filled primary button, the strongest text on light ground (secondary-button text and border, link hover), and the hollow core of the interchange mark. It is the system's authority colour.
- **Enamel Steps** (enamel-800, enamel-700): rail hover and active-press surfaces, the rail's foot divider, and the primary button's hover (enamel-700) and press (enamel-800).
- **Enamel Mist** (enamel-300, enamel-200): text on the rail (enamel-200 for links and team names, enamel-300 for the rail's quieter headings, role line and Log out), the dashed track of a cancelled route, breadcrumb separators, read-notification markers, and the thin scrollbar thumb.

### Secondary
- **Signal Cobalt** (cobalt, cobalt-dark, cobalt-soft): the interaction colour. Focus outlines and text selection are cobalt; the text caret and focused input border are cobalt; inline links are cobalt-dark; the row being edited and a chosen helper are washed in cobalt-soft. Cobalt is also team line 2.

### Tertiary
- **Alarm Scarlet** (scarlet, scarlet-dark, scarlet-soft): the unread count in the rail, unread-notification markers, error banners (scarlet-soft ground, scarlet-dark text, scarlet border at 30%), field errors and destructive links (scarlet-dark). Scarlet is also team line 1 and one stroke of the mark.

### Line Inks
Eight team inks in a fixed order: scarlet, cobalt, line-amber, line-green, line-plum, line-teal, line-orange, line-brown. A team's ink is `LINE_INKS[(id - 1) % 8]`; a record with no team falls back to enamel-300. Inks appear as short rounded bars (line markers), the filled part of a project route, the border of a client's initial tile, and the team-report status bar.

### Neutral
- **Porcelain** (porcelain): the work ground behind panels, loading skeletons, secondary-button and row hover.
- **Panel White** (panel-white): panels, inputs, route stations.
- **Cool Rule** (rule): panel borders, input borders, table heads' bottom rule, the empty part of a route, not-started status. Table rows use the rule at 60%.
- **Station Ink** (ink): body text and titles on light ground.
- **Muted Ink** (ink-muted): metadata lines, form labels, table heads, placeholders, empty states.

### Named Rules
**The Fixed Line Rule.** A team's ink is derived from its id through the shared line list, never picked per screen. Wherever a team, its client or its project appears, the same ink marks it.

**The No Status Ink Rule.** Project status never takes a line ink of its own. Status is expressed in the project's team ink (filled route, or team ink mixed 55% with white for "working" in aggregate bars), in enamel neutrals (rule for not started) or as a suspended dashed enamel-300 track (cancelled).

**The Alarm Rule.** As a UI state rather than a team line, scarlet means only: unread, error, or destructive. Never use it for emphasis, decoration or success.

## Typography

**Display Font:** Barlow Semi Condensed (with Noto Kufi Arabic, then the system sans stack)
**Body Font:** Barlow (with Noto Kufi Arabic, then the system sans stack)

**Character:** Station signage over timetable text. The condensed face is always uppercase and tracked, so it reads as a sign, not a sentence; the regular Barlow carries everything a person reads in full.

Fonts load from fonts.bunny.net: Barlow 400/500/600/700 and Barlow Semi Condensed 500/600/700. Noto Kufi Arabic is named in both stacks for the coming Arabic locale but is not loaded yet.

### Hierarchy
- **Display** (signage 700, 2.25rem, uppercase, 0.025em tracking): the page title, once per page ("My work", a client's or project's name).
- **Headline** (signage 600, 1rem, uppercase, 0.05em tracking): panel titles ("Waiting on you", "Invoices") and rail navigation.
- **Title** (Barlow 500, 1rem): record names in lists, linked to their detail page.
- **Body** (Barlow 400, 1rem, 1.5): sentences, notification text, the status line under a page title.
- **Body small** (Barlow 400, 0.875rem): metadata lines, table text, links, field errors.
- **Label** (signage 600, 0.875rem, uppercase, 0.05em tracking): form labels, buttons, breadcrumbs.
- **Label small** (signage 600, 0.75rem, uppercase, 0.05em tracking): table heads, route station names, the rail's "Your lines" heading (at 0.1em).
- **Data** (Barlow with tabular figures): every table, and any count, total, id or date set outside one, so figures align in columns.

### Named Rules
**The Signage Voice Rule.** The condensed face is used only uppercase and tracked, and only for things that name or direct (titles, labels, buttons, table heads, navigation). Anything read as prose stays in Barlow, sentence case.

**The Tabular Figures Rule.** Counts, totals, ids and dates use tabular numerals so columns line up.

## Layout

Two-zone shell. At the large breakpoint (1024px) the enamel rail is a 256px column pinned full-height at the start edge and the work area scrolls beside it; below that the rail folds into a 64px top bar with a menu toggle that opens the same nav, lines and user plate.

The work area is centred at a 1280px maximum with 16px side padding (32px from 640px) and 32px top and bottom padding (40px at large). A page opens with an optional breadcrumb, the page title and a muted status line, then 32px of space before the panels. Panels sit in a 3-column grid with 24px gaps at large: the main list takes two columns and a form or summary panel takes the third, sticking 40px from the top while the list scrolls. Below large everything stacks in one column.

Inside a panel, the header strip is 24px by 16px with a rule beneath; list rows run 24px horizontally by 12 to 16px vertically, separated by hairlines rather than gaps. Forms stack fields 20px apart. On small screens, tables drop their head row and reflow each row into wrapped label-free lines.

Direction: all horizontal spacing and alignment is logical (start/end margins and padding, start/end text alignment), spaced action groups reverse under right-to-left, and phone numbers are forced left-to-right inside any direction.

## Elevation & Depth

Flat. No surface carries a soft shadow. Depth comes from tone and line: the dark rail against the light ground, white panels on porcelain, and 1px cool rules around and inside panels. State changes are tonal (porcelain or cobalt-soft washes, enamel steps on the rail), never lifts.

### Named Rules
**The Ruled Panel Rule.** A panel is separated from the ground by a 1px rule and a white fill, not by a shadow. If a surface needs more separation, use tone (rail enamel, porcelain), not elevation.

## Shapes

Small, firm corners: 4px on panels, inputs, buttons, banners, image and initial tiles. Fully rounded shapes are reserved for line geometry and markers: line bars (5 to 8px high, 20 to 40px long), route tracks and stations, notification dots, the user's initials disc and the unread count. A station is a white circle with a 2px ink border; the current station is larger, and stations already passed are filled with ink. Suspended (cancelled) tracks are drawn as 6px dashes with 4px gaps.

The brand mark is two crossing line strokes (scarlet and cobalt) meeting at a white-ringed interchange circle; the login screen draws the same idea at full panel scale as four lines meeting at one interchange. Icons are authored inline SVG with stroked 2px paths; there is no icon library.

## Components

### Buttons
Firm and signage-voiced.
- **Shape:** gently squared (4px).
- **Primary:** filled midnight enamel with white label text, 8px by 16px, label typography. One primary per form or decision.
- **Hover / Focus:** hover steps to enamel-700, press to enamel-800, colour transitions over 200ms; focus is the global 2px cobalt outline at 2px offset. Disabled drops to 50% opacity with a not-allowed cursor.
- **Secondary:** white with a 1px enamel-950 border and enamel-950 label; hover washes to porcelain. Used for Cancel, pagination and lesser actions.
- **Compact:** inline row actions (Approve in a list) reuse the primary at 4px by 12px.

### Links
- **Inline link:** cobalt-dark, body-small medium weight, hover to enamel-950 with an underline offset 4px.
- **Destructive link:** scarlet-dark, hover to scarlet with underline. Row actions (Edit, Delete) are links grouped at the row's end, not buttons.

### Cards / Containers (Panels)
- **Corner Style:** 4px.
- **Background:** white on the porcelain ground.
- **Shadow Strategy:** none (see Elevation & Depth).
- **Border:** 1px cool rule.
- **Internal Padding:** 24px for form panels; list panels drop the padding and use a ruled header strip and full-bleed ruled rows.

### Inputs / Fields
- **Style:** white, 1px cool-rule border, 4px corners, ink text, muted-ink placeholder, no inset shadow; label above in signage label style, 4px gap.
- **Focus:** border and ring turn cobalt; caret is cobalt.
- **Error:** field error text sits below in body-small scarlet-dark.
- **File input:** the picker button is a small porcelain chip with a rule border and signage label, washing to white on hover.
- **Choice tiles:** multi-select helpers are 4px bordered tiles that wash porcelain on hover and turn cobalt-bordered on cobalt-soft when chosen.

### Tables
Body-small data with tabular figures. Head cells are label-small, muted ink, start-aligned, over a full rule; body rows are divided by the rule at 60%, the last row unruled. Action columns are end-aligned.

### Navigation
- **Rail:** enamel-950, 256px. Wordmark "CRM" in signage 700 with the widest tracking beside the mark.
- **Items:** headline signage in enamel-200, 8px by 12px, 4px corners; hover to enamel-800 ground with white text over 150ms.
- **Active:** white text with a 3px white underline under the label.
- **Unread count:** scarlet disc-pill at the item's end with white tabular text.
- **Your lines:** a list of the user's teams, each an ink bar and name; overflow summarised as "and N more".
- **User plate:** initials disc, name in white, role in enamel-300, then Log out in muted rail text.
- **Mobile:** the rail becomes a 64px top bar; a stroked menu/close SVG toggles the same content.
- **Breadcrumb:** label signage above the page title, muted parent link, enamel-300 slash, current page in ink.

### Error Banner
A 4px scarlet-soft strip with a 30% scarlet border and scarlet-dark body-small text, announced as an alert.

### Project Route (signature)
A project's lifecycle drawn as a line with four stations: Requested, Approved, Working, Done, in the team's ink. A request waits at the first station until approved and reads "Awaiting approval"; approved projects sit at Approved (not started), Working, or Done. The filled part of the track and passed stations take the ink; the current station is a larger ink-ringed white circle; unreached stations are rule-ringed. A cancelled project's line is suspended: a dashed enamel-300 track with enamel-300 stations and a struck-through label. The compact form (96px track plus label, optionally a fixed 128px label so routes align in a list) sits in rows; the large form spans the project header with station names beneath in label-small. The fill animates its width over 200ms ease-out, and not at all under reduced motion.

### Team Line
The ink bar and name pair that marks a team anywhere: a short rounded bar in the team's ink followed by the name, or "No team" in muted ink. A client without an image shows its initial in a 4px tile bordered in its team's ink.

### Team Status Bar
In a team report, a 12px rounded bar split into status segments in proportion to counts: done in full team ink, working in team ink mixed 55% with white, not started in rule, cancelled as the dashed suspended pattern, with a legend of matching short bars and tabular counts.

## Do's and Don'ts

### Do:
- **Do** derive every team colour from `LINE_INKS[(id - 1) % 8]` and show it as a short rounded bar, a route, or an ink border.
- **Do** show project status as a Project Route in the team's ink; for aggregates use team ink, team ink mixed with white, rule, and the dashed suspended track.
- **Do** use filled midnight enamel for the one primary action, the bordered secondary for everything else, and links for row actions.
- **Do** set titles, labels, buttons, nav and table heads in the uppercase tracked signage face, and everything read as prose in Barlow.
- **Do** use tabular figures for every count, total, id and date.
- **Do** write spacing and alignment in logical directions (start/end), reverse spaced groups under right-to-left, and force phone numbers left-to-right.
- **Do** draw icons as authored inline SVG strokes.
- **Do** keep motion to 150 to 200ms colour and width transitions on state change, and drop them under reduced motion.

### Don't:
- **Don't** render project status as rounded coloured pills or give a status its own line ink.
- **Don't** use scarlet as a UI colour for anything but unread, error and destructive.
- **Don't** add soft card shadows; separate surfaces with the 1px rule and tone.
- **Don't** use physical left/right spacing or alignment.
- **Don't** add an icon library or glyph characters as icons.
- **Don't** add entrance, scroll or decorative motion.
