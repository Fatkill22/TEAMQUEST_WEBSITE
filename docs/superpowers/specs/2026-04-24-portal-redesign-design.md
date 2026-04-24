# TeamQuest Training Portal — Design Specification
**Date:** 2026-04-24  
**Project:** TeamQuest Technologies Inc. Training Portal Redesign  
**Scope:** Full visual overhaul + new features via CSS + front-end JS + small PHP additions. Core exam/video logic untouched.

---

## 1. Design System

### Color Palette
| Role | Name | Hex |
|---|---|---|
| Primary | Deep Navy | `#1a237e` |
| Primary Dark | Darker Navy | `#0d1757` |
| Accent | Gold | `#c8960c` |
| Accent Hover | Warm Gold | `#e0a800` |
| Page Background | Off-white | `#f5f6fa` |
| Surface | White | `#ffffff` |
| Text Primary | Near-black | `#1c1c2e` |
| Text Muted | Medium gray | `#6b7280` |
| Success | Green | `#16a34a` |
| Danger | Red | `#dc2626` |
| Warning | Amber | `#f59e0b` |

### Typography
- **Font:** Inter (Google Fonts)
- Page titles: 22px bold
- Section headers: 16px semibold
- Body text: 14px regular
- Sidebar labels: 13px medium

### Spacing & Shape
- Card border-radius: 12px
- Button border-radius: 8px
- Button min-height: 40px
- Sidebar width: 240px fixed
- Content padding: 24px
- Card box-shadow: `0 2px 8px rgba(0,0,0,0.08)`

---

## 2. Global Layout Shell

All authenticated pages share the same shell:

```
┌──────────────────────────────────────────────────────┐
│  TOPBAR: Logo left | Page title center | User right  │
├────────────┬─────────────────────────────────────────┤
│            │                                         │
│  SIDEBAR   │         MAIN CONTENT AREA               │
│  (240px)   │         (fluid, scrollable)             │
│            │                                         │
│  Nav items │                                         │
│  w/ icons  │                                         │
│            │                                         │
│  [Logout]  │                                         │
│  at bottom │                                         │
└────────────┴─────────────────────────────────────────┘
```

### Topbar
- Navy background (`#1a237e`)
- TeamQuest logo (left, 50px height)
- Current page name (center, white, 16px semibold)
- Employee name + avatar initial circle (right, gold background)

### Sidebar
- Background: `#1a237e`
- Item: white icon (Bootstrap Icons) + white label, 44px height, 16px horizontal padding
- Active item: gold left-border (4px) + lighter navy background (`rgba(255,255,255,0.1)`)
- Hover: subtle white overlay
- Logout button: pinned to bottom, muted red text, icon left

### Employee Sidebar Items
| Icon | Label | Target |
|---|---|---|
| house-fill | Home | Dashboard home |
| book-fill | My Modules | Normal modules tab |
| clipboard-check | Gauge Exams | Gauge exams tab |
| bar-chart-fill | My Results | Results tab |

### Admin Sidebar Items
| Icon | Label | Target |
|---|---|---|
| house-fill | Home | Admin home |
| layers-fill | Manage Modules | Module management |
| clipboard-data | Gauge Study | Gauge management |
| people-fill | User Attempts | User attempts table |

---

## 3. Login & Register Pages

### Login (`INDEX.html`)
- Two-column layout (centered card on off-white background)
- Left panel: navy background, TeamQuest logo centered, building photo as blurred backdrop
- Right panel: white form area
  - "Welcome Back" heading (navy, 24px bold)
  - Username and password fields (48px height, gold focus ring)
  - Full-width login button (navy bg, white text)
  - "Register here" link below in muted text

### Register (`REGISTER.html`)
- Same two-column shell as login for visual consistency
- Form fields in 2-column grid: First Name | Last Name, then stacked fields
- Department dropdown with custom chevron styling
- Submit button: full-width, navy

---

## 4. Employee Dashboard — Home Screen

### Stat Cards (top row, 4 cards)
| Card | Icon | Content |
|---|---|---|
| Modules Assigned | book | Total module count |
| Completed | check-circle | X / Total |
| Average Score | star | Overall % average |
| Gauge Exams | clipboard | Count taken |

- White card, 12px radius, soft shadow
- Gold top-border accent (4px)
- Large navy number, muted label below

### "Continue Where You Left Off" Section
- Full-width highlighted card (navy left-border accent)
- Shows most recent incomplete module: thumbnail + title + "Continue" gold button
- If all complete: congratulations message with green check icon

### "My Progress" Section
- One horizontal progress bar per assigned module
- Colors: green (passed), red (failed), gray (not started)
- Module name label on the left, status chip on the right

### "Recent Results" Section
- Last 3 exam results in a clean table
- Columns: Module, Score, Status badge, Date
- "See All Results" link at bottom right

---

## 5. My Modules Page

- 3-column card grid (`col-lg-4`)
- Card anatomy:
  - Module image (180px fixed height, object-fit cover)
  - Title (bold, 15px)
  - Description (2-line clamp, muted text)
  - Attempt badge + status chip (Not Started / In Progress / Completed / Locked)
  - Step indicator strip: `① Watch Video → ② Take Exam → ③ View Results` — current step highlighted gold
- Locked modules: 50% opacity + lock icon overlay on image

---

## 6. Take Exam Page

- "Question X of Y" indicator at top in gold
- Each question in a white card with generous padding
- Answer options as full-width clickable radio cards (not small radio inputs)
  - Default: white bg, navy border
  - Selected: navy bg, white text
- Sticky bottom bar: progress dots (filled = answered) + gold Submit button
- Submit button disabled until all questions answered
- Optional countdown timer shown as pill in topbar

---

## 7. My Results Page

- Filter pills at top: All / Passed / Failed
- Table rows with colored left-border: green (pass), red (fail)
- "Review Answers" button (gold) per row replacing plain text link
- Mistakes/Review page: side-by-side comparison cards
  - Left: Your Answer (red background)
  - Right: Correct Answer (green background)

---

## 8. Admin Dashboard — Home Screen

### Stat Cards (top row, 4 cards)
| Card | Shows |
|---|---|
| Total Users | Registered employee count |
| Active Modules | Published module count |
| Overall Pass Rate | % across all exams |
| Gauge Exams Taken | Total gauge submissions |

### Quick-Action Strip
Three large gold buttons below stat cards:
- "Add Module" → jumps to Manage Modules
- "Manage Questions" → jumps to question editor
- "View Reports" → jumps to gauge report

---

## 9. Admin — Manage Modules Page

- "Add New Module" form in a collapsible panel (hidden by default, toggle button at top)
- Module list as card table with:
  - Thumbnail preview (40×40px)
  - Title + department badge
  - Edit (pencil icon) and Delete (trash icon) buttons with tooltips
  - No accidental deletions: delete requires confirmation modal

---

## 10. Admin — User Attempts Page

- Search bar prominently at top
- Results grouped by module, expandable rows
- Reset button: subtle danger icon button (trash icon) to reduce accidental clicks

---

## 11. Gauge Pages

### Take Gauge Exam (`take_gauge_exam.php`)
- 50 items rendered as clean rows
- Large toggle buttons (0 / 1) per item instead of small inputs
- Items grouped in sets of 10 for visual chunking

### Gauge Report (`view_gauge_report.php`)
- Summary donut chart at top: Acceptable / Marginal / Unacceptable distribution
- Color-coded table rows (green / yellow / red per rating)
- Existing detail modal retained, styling improved

### Gauge Control (`gauge_control.php`)
- Master answer key in 10-column × 5-row grid (all 50 answers visible at once)
- Large toggle buttons (0 / 1) per cell

---

## 12. Global UX Improvements

### Toast Notifications
- Replace all `alert()` and redirect-based feedback
- Slide-in from bottom-right, auto-dismiss after 4 seconds
- Colors: green (success), red (error), navy (info)
- Implemented via a lightweight JS utility injected in shared `styles.css` + `scripts.js`

### Loading States
- Buttons show spinner icon + disabled state on click
- Prevents double-submits, reassures non-technical users

### Empty States
- When no modules/results exist: friendly icon + message
- Example: "No modules assigned yet. Check back soon!"

### Iconography
- Bootstrap Icons (already bundled with Bootstrap 5) used throughout
- Every nav item, button, and status has a consistent icon

### Transitions
- 200ms fade-in on sidebar section switches
- Subtle hover transitions on cards and buttons (150ms)

### Shared Stylesheet
- Single `assets/css/styles.css` file covers entire design system
- All pages link to this file — guarantees cohesion
- Overrides Bootstrap 5 defaults via CSS custom properties (`:root` variables)

---

## 13. File Delivery Plan

| File | Action |
|---|---|
| `assets/css/styles.css` | New — global design system stylesheet |
| `assets/js/scripts.js` | New — toast notifications, loading states, transitions |
| `INDEX.html` | Redesign login layout |
| `REGISTER.html` | Redesign register layout |
| `EMPLOYEE.php` | Replace tabs with sidebar shell + new dashboard home |
| `admin_modules.php` | Replace tabs with sidebar shell + admin home |
| `view_module.php` | Apply new shell + step indicator |
| `take_exam.php` | Redesign question cards + sticky bottom bar |
| `take_gauge_exam.php` | Redesign 50-item form with toggle buttons |
| `view_gauge_report.php` | Add donut chart (Chart.js CDN) + color-coded rows |
| `gauge_control.php` | Redesign as 10×5 grid |
| `gauge_study.php` | Apply new shell + large toggle buttons |
| `view_mistakes.php` | Side-by-side comparison card layout |
| `view_gauge_mistakes.php` | Same as view_mistakes treatment |
| `manage_exam.php` | Apply new shell + collapsible form panel |

---

## 14. Constraints

- Core PHP logic (exam grading, video upload, gauge scoring, session auth) must not change
- No new npm/build tools — plain CSS + vanilla JS only
- Bootstrap 5.3 retained as base framework
- Inter font loaded via Google Fonts CDN
- Bootstrap Icons used for all iconography (already available via CDN)
- Chart.js (CDN) used only for the gauge report donut chart — no other charting library needed
- Stat card counts (modules assigned, avg score, etc.) are computed via small PHP `SELECT` queries added to the top of `EMPLOYEE.php` and `admin_modules.php` — no new files, no schema changes
