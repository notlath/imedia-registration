---
name: Studio
colors:
  ink: '#0F1419'
  ink-soft: '#2A323C'
  ink-muted: '#5A6470'
  ink-faint: '#8C95A0'
  paper: '#F4F6F8'
  vellum: '#FFFFFF'
  rule: '#D8DDE3'
  rule-soft: '#E7EAEF'
  moss: '#1F574D'
  moss-strong: '#163E37'
  moss-soft: '#E2EEEB'
  moss-ink: '#FFFFFF'
  ember: '#B8541B'
  ember-strong: '#984211'
  ember-soft: '#FAE9DD'
  ember-ink: '#FFFFFF'
  surface-0: '#FFFFFF'
  surface-1: '#F4F6F8'
  surface-2: '#EEF1F4'
  surface-3: '#E5E9EE'
  surface-4: '#DDE2E8'
  success: '#1F574D'
  warning: '#946311'
  danger: '#A53223'
  status-pending-bg: '#FAEFD6'
  status-pending-fg: '#5C3E0A'
  status-tentative-bg: '#E0E6EE'
  status-tentative-fg: '#1F2C42'
  status-confirm-bg: '#D9E8E4'
  status-confirm-fg: '#143730'
  status-forfeit-bg: '#FBE3DE'
  status-forfeit-fg: '#6E1F12'
  status-reschedule-bg: '#F4E2C8'
  status-reschedule-fg: '#5C3E0A'
typography:
  display:
    family: Fraunces
    weight: '600'
    usage: Headlines, brand, section titles, KPI values
  body:
    family: Inter
    weight: '400'
    usage: All running text, labels, controls
  mono:
    family: JetBrains Mono
    weight: '400'
    usage: IDs, timestamps, JSON, code
rounded:
  sm: 0.25rem
  md: 0.375rem
  lg: 0.625rem
  xl: 0.75rem
  pill: 9999px
spacing:
  container-max: 1200px
  sidebar-width: 232px
  gutter: 1.5rem
  rhythm: 0.25rem
  section-gap: 2.5rem
motion:
  fast: 150ms
  base: 180ms
  slow: 220ms
  ease-out: cubic-bezier(0.32, 0.72, 0.32, 1)
signature:
  status-marks:
    - code: PD
      meaning: pending
    - code: TT
      meaning: tentative
    - code: OK
      meaning: confirm
    - code: FX
      meaning: forfeit
    - code: RS
      meaning: reschedule
---

## Subject

Inventive Media Registration is a daily-driver admin tool for an educational
institution's intake pipeline. Admissions staff use it throughout the day to
triage registrations, review applications, follow up on inquiries, and ship
emails. The interface optimizes for **information density without overwhelm**
and **scannable hierarchy under time pressure**.

## Brand & Style

This design system is built on a foundation of quiet, workmanlike precision —
a tool that respects the operator's time. The visual style is **Studio**: a
contemporary editorial aesthetic that uses a confident variable serif for
display, a clean humanist sans for body, and a single warm rust accent to
add humanity without sacrificing calm. The emotional goal is to feel
**considered, dependable, and quietly distinctive** — not a templated SaaS.

## Colors

The palette is driven by a cool slate foundation (`ink`, `paper`) with a
deep teal-jewel primary (`moss`) and a single warm rust accent (`ember`).
The 5 named brand colors are:

- **ink** `#0F1419` — foreground, deep slate with a cool undertone.
- **paper** `#F4F6F8` — page background, near-white with a cool tint (NOT cream).
- **vellum** `#FFFFFF` — highest surface, cards.
- **rule** `#D8DDE3` — hairline borders, dividers.
- **moss** `#1F574D` — primary, deep teal-jewel. "Approved", "go", trust without urgency.
- **ember** `#B8541B` — accent, warm rust. Destructive confirmations, focused state, KPI alert.

**Surface tiers** climb in 3% steps: `paper` → `#EEF1F4` → `#E5E9EE` → `#DDE2E8`.
This gives depth without weight. Each surface is a token; no one-off hex
values should appear in components.

**Status colors** are paired for AA contrast and reused across badges,
status marks, and doughnut chart slices: `pending` ochre, `tentative` slate-blue,
`confirm` moss-family, `forfeit` brick-rust, `reschedule` amber. All AA
contrast against their background.

## Typography

The type system uses **Fraunces** (variable serif) for display, **Inter**
for body, and **JetBrains Mono** for data.

- **Fraunces** is a contemporary variable serif with optical sizing and soft,
  slightly wonky terminals. Used with restraint — headlines, section titles,
  KPI numbers, the brand. The optical-size axis and the variable weight axis
  give the type system range without introducing extra families.
- **Inter** is the workhorse for all running text, labels, controls, and
  badges. Optimized for screen reading, neutral without being cold.
- **JetBrains Mono** is used for IDs, timestamps, JSON payloads, and any
  numeric display where alignment matters. Tabular figures are enabled on
  every numeric span via `font-variant-numeric: tabular-nums`.

Hierarchy is enforced through **weight, size, and optical size** together
— not size alone. Headlines use Fraunces 600 with `font-optical-sizing: auto`
to pick up the appropriate cut at each size.

## Layout & Spacing

- **Grid:** A 12-column system via `auto-fit minmax(...)` grids. Cards
  typically span 4 columns (3-up) or 6 columns (2-up) on desktop.
- **Container:** Content is centered within a 1200px max-width.
- **Sidebar:** 232px wide, sticky on desktop, drawer on mobile (≤768px).
- **Rhythm:** A 4px base spacing unit. Section gaps are 2.5rem; vertical
  rhythm between related elements is 1rem–1.5rem.
- **Responsive:** On mobile, the sidebar becomes a 80vw drawer; cards
  collapse to single column; horizontal gutters shrink to 1rem.

## Elevation & Depth

This system favors **hairline rules and tonal layering** over heavy shadows.

- **Cards:** 1px hairline border, 10px corner radius, no shadow at rest.
  Hover raises to a soft, diffused `0 1px 2px + 0 6px 16px` shadow.
- **Modals:** Single `0 16px 32px + 0 4px 8px` shadow tier, reserved for
  the modal overlay only.
- **Sticky elements:** 1px solid `rule` below, no shadow.
- **Surface tiers:** A vertical hierarchy of tints for backgrounds and
  table headers. No drop shadows on containers.

## Shapes

- **Buttons & inputs:** 6px corner radius — almost square. This signals
  "tool" rather than "consumer app".
- **Cards:** 10px corner radius.
- **Modals:** 12px corner radius.
- **Badges & pills:** Pill shape (9999px) for status badges and
  status marks.
- **Tabs:** 6px top corners only; underline indicator at the active tab.
- **KPI cards:** 10px corner radius, 2px `ink` rule at the top edge as a
  signature accent (turns `ember` in the alert variant).

## Signature Element — Status Marks

The one memorable thing this page is known for: a **2-letter monospaced
mark in a colored square**, placed immediately before the full status label.
Like a printer's production mark. Carries meaning even when the badge
background is invisible.

| Mark | Status      | Background token        |
|------|-------------|-------------------------|
| PD   | pending     | `status-pending-bg`     |
| TT   | tentative   | `status-tentative-bg`   |
| OK   | confirm     | `status-confirm-bg`     |
| FX   | forfeit     | `status-forfeit-bg`     |
| RS   | reschedule  | `status-reschedule-bg`  |

Marks are **semantic and inflexible**. They are always 1.5rem squares, set
in `JetBrains Mono` 700, 0.625rem. The accompanying label is set in `Inter`
500. Use the `.imreg-status` / `.imreg-status__mark` / `.imreg-status--{name}`
classes, not the old `.imreg-badge--{name}` (which is kept for non-status
pills like the outbox error code).

## Components

### Buttons
- **Primary:** Moss background, vellum text. Used for the main action on a
  page ("Save", "Edit", "New registration").
- **Secondary:** Vellum background, 1px rule border, ink text. The default
  for non-primary actions ("Cancel", "Filter", "Edit" when an adjacent
  primary exists).
- **Ghost:** Transparent, ink text. Tertiary actions.
- **Danger:** Vellum background, 1px danger-soft border, danger text. The
  default for destructive actions. (Previously danger was always a filled
  red; the new default is gentler. Use `.imreg-btn--danger-solid` for the
  rare case where the destructive action is the page's only CTA.)
- All buttons: 6px corners, 0.5rem/0.875rem padding, 2.25rem min height.
  Active state: `translateY(0.5px)`. Focus: 2px moss outline, 2px offset.

### Cards
- 1px rule border, 10px radius, 1.5rem/1.5rem padding. No shadow at rest;
  soft hover shadow on interactive variants. Header/footer are opt-in via
  `.imreg-card__header` and `.imreg-card__footer` with 1px rule dividers.

### KPI Cards
- Same surface as a card plus a 2px `ink` rule at the top. The value is
  Fraunces 600 at 2.25rem, tabular figures, optical-size auto. Alert
  variant flips the top rule to `ember` and tints the background.

### Tables
- 1px rule border on the wrapper, 1px `rule-soft` between rows. The
  header row uses `surface-1` background, 0.6875rem uppercase labels.
  Hovering a row tints it `surface-1`. Empty state takes 3rem vertical
  padding and shows a muted message.

### Tabs
- Underline pattern. 1px `rule` border under the row, 2px `ember` border
  on the active tab. The active tab label uses ink color, not moss —
  moss is reserved for primary actions.

### Modals
- Native `<dialog>` element (built-in focus trap, Escape-to-close). 12px
  radius, single shadow tier, 440px max width. Footer uses `surface-1`
  background and rounds with the modal.

### Inputs & Fields
- 6px corner radius, 1px rule border, vellum background, 0.5rem/0.75rem
  padding, 2.25rem min height. Hover deepens the border; focus adds a
  3px moss-tinted ring and a moss border. Error state uses a danger
  border + danger ring.

### Pagination
- Tabular-figure page numbers in `JetBrains Mono`. Active page is filled
  ink (inverted) rather than moss — moss is reserved for primary action.

### Theme Toggle
- Three-state cycle: light → dark → auto. Sun and moon SVG icons swap
  based on the `.dark` class on `<html>`. The toggle is read by
  `app.js`; the CSS just provides the visual swap.

## Motion

- Standard duration: **180ms** (`var(--motion-base)`). Fast micro-interactions
  use **150ms**, complex modals use **220ms**.
- Easing: `cubic-bezier(0.32, 0.72, 0.32, 1)` (snappy ease-out) for enter,
  `cubic-bezier(0.4, 0, 1, 1)` for exit. Enter faster than exit.
- KPI cards stagger their entrance: 0ms / 40ms / 80ms / 120ms.
- `prefers-reduced-motion: reduce` collapses every animation to 0.01ms.

## Dark Mode

Dark mode re-defines the same tokens under `.dark` on `<html>`. The
JS sets the class before paint to avoid FOUC.

- Background: ink (`#0F1419`). Surfaces tier upward in 4% steps.
- Moss and ember lift to AA contrast: moss → `#3FA39A`, ember → `#E07A4A`.
- Rule and outline stay visible (lifted to `#2A323C` / `#5A6470`).
- Status backgrounds become deeper containers, foregrounds lift to light
  tints — so the marks and badges remain readable.

## Accessibility

- All text contrast ≥ 4.5:1 (body) / 3:1 (large) on both themes.
- Focus rings: 2px moss outline with 2px offset on every interactive
  element, set globally via `:focus-visible`.
- Skip link is present on every page (`.imreg-skip-link`).
- `aria-current="page"`, `role="alert"`, `aria-live="polite|assertive"`
  used correctly throughout the partials and views.
- Status marks use the `aria-hidden` pattern when paired with a
  text label (the text is the accessible name).
- Reduced-motion respected at every animation, transition, and the
  modal/drawer entrance.

## Filesystem

All icons are **inline SVG**. No icon font. No emoji as icons. The
theme toggle, the sidebar hamburger, and the search/filter affordances
all use 1.5px-stroke SVGs sized to 1.125rem.

## JS Contract

`public/assets/js/app.js` reads specific CSS custom properties for chart
palette injection. The Studio system keeps these variable names stable:

- `--color-primary`, `--color-secondary`, `--color-tertiary`
- `--color-on-surface`, `--color-outline-variant`
- `--color-surface-container-lowest`
- `--color-status-{pending,tentative,confirm,forfeit,reschedule}-{bg,fg}`

New components should consume the new tokens (`--color-moss`, `--color-ink`,
etc.) directly. The legacy variables are aliases kept for the JS contract.
