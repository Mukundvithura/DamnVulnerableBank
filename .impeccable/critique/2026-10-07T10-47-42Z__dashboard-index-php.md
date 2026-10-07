---
target: dashboard/index.php
total_score: 22
max_score: 40
na_heuristics: 
p0_count: 1
p1_count: 2
target_identity: "file:C:\\Users\\Mukund Karthikeyan\\BankVWA\\dashboard\\index.php"
target_fingerprint: "sha256:400a56e3b7e5cc6525026ee0fc68d67539488da9fdfddf61a4aebd3486ed824a"
target_path: "C:\\Users\\Mukund Karthikeyan\\BankVWA\\dashboard\\index.php"
timestamp: 2026-10-07T10-47-42Z
slug: dashboard-index-php
---
Method: dual-agent (A: design review · B: detector + rendered-HTML evidence)

## Design Health Score
| # | Heuristic | Score | Key Issue |
|---|---|---|---|
| 1 | Visibility of System Status | 2 | No active-nav state; no "as of" time on balances |
| 2 | Match System / Real World | 2 | "Checking" not "Current"; Western digit grouping not lakh |
| 3 | User Control and Freedom | 3 | Profile unreachable on mobile (.nav-user display:none) |
| 4 | Consistency and Standards | 3 | Masking varies: •••• 0401 vs ••0401 |
| 5 | Error Prevention | 2 | money() uses abs(): negative balance shows positive |
| 6 | Recognition Rather Than Recall | 2 | Recent transactions hide which account |
| 7 | Flexibility and Efficiency | 1 | No quick actions, no View all, no shortcuts |
| 8 | Aesthetic and Minimalist Design | 3 | Calm, clear total → accounts → activity |
| 9 | Error Recovery | 2 | No zero-accounts empty state |
| 10 | Help and Documentation | 2 | Nothing tells a learner where they are |
| Total | | 22/40 | Acceptable |

## Design Specificity Verdict
Mostly interchangeable fintech template. Western grouping and "Checking" contradict the PRODUCT.md Indian commitment. Lab identity only in a small grey footer.
Detector: .php files skipped (no error); rendered-HTML scan gave 3 warnings: cramped-padding (false positive, print CSS), flat-type-hierarchy (mostly false positive; real part: .card h2 1.1rem), dark-glow (false positive). Real low-contrast missed at first: #6b7486 on #f3f6fb = 4.3:1 (footer).
No overlay (no injectable browser tool).

## Priority Issues
- [P0] Mobile nav overflows viewport (390px → 565px wide; Beneficiaries/Statements invisible off-bar; Profile hidden). Fix: .nav-links flex-wrap or contained scroll. /impeccable adapt
- [P1] Indian formats wrong: money() Western grouping; "Checking" → "Current". /impeccable clarify
- [P1] No active nav state, no quick actions (Transfer/Pay beneficiary/Statements). /impeccable layout
- [P2] Lab identity too quiet; footer fails contrast (4.3:1) and says "deliberately vulnerable" vs secure-reference framing. Add TRAINING LAB mark. /impeccable harden
- [P2] Recent Transactions dead end: add account to query, add View all. /impeccable clarify

## Persona Red Flags
Alex: no quick actions/View all, rows not clickable, no full account number to copy, no last-login.
Sam: no skip link (9+ tabs), nav lacks aria-label/aria-current, card reads "bullet bullet bullet bullet", "Salary credit06 Oct 2026" run-on, Total Balance not a heading, Profile hidden on mobile.
Riya (learner): footer claims vulnerable while page is secure reference; no mode signal; no session info.

## Minor Observations
Brand + Dashboard duplicate target; flash margin stacks on main margin; account balances 1.6rem compete with total 2.2rem; minus sign spaced from amount; no Today/Yesterday dates.

## Questions to Consider
Could a screenshot pass as a phishing mock-up? Would committing to Indian conventions beat visual polish? Is the dashboard the home for the future training-module entry point?
