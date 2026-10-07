---
version: 1
slug: "dashboard-index-php"
primary_target: "dashboard/index.php"
related_targets: ["auth/login.php","includes/header.php","assets/css/style.css"]
---

# Surface brief: customer app shell (dashboard + sign-in)

Scope: shared app shell (top nav, mobile slide-in menu), sign-in, dashboard; the token system propagates to every page. Mode: Operate.

Audience/job: security learners using a believable Indian online bank; check money, act in one click. Constraints: UI only (no PHP logic, queries, routes, sessions); keep "Current"; no "Remember me"; Security Lab UI out of scope. Lab identity: tagline "Secure Banking. Vulnerable by Design." on sign-in plus the footer note.

Unresolved: Security Lab surfaces; admin audit-log filtering (needs a PHP query change).

## Direction contract

THESIS: A premium Indian digital bank built from one token system; money typography is the hero; refuses the white-rounded-card-on-grey template.

OWN-WORLD: Deep navy shell bar; cool porcelain ground; white surfaces with hairline borders only where grouping needs them; one cobalt action blue; semantic green/amber/red as tints; Geist with tabular figures; balances in large tabular numerals with lakh grouping.

STORY: The learner signs in to something that feels like a real bank, sees their money first, acts in one click, and always knows it is a training lab.

FIRST VIEWPORT: Desktop: navy top bar (brand, 6 links, Admin for admins, profile + logout). Dashboard: greeting + last login; navy balance panel with total at display size and quick actions; account tiles; transactions table with right-aligned amounts. Mobile: top bar + slide-in menu, same order stacked. Sign-in: navy brand panel with tagline and true security notes beside the form.

FORM: brief-pinned direction; concept-seed not run; seed key: none (brief-pinned).

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
