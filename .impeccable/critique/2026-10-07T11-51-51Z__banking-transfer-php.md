---
target: banking/transfer.php
total_score: 25
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 2
target_identity: "file:C:\\Users\\Mukund Karthikeyan\\BankVWA\\banking\\transfer.php"
target_fingerprint: "sha256:eab0a025a82d58b8f3803bea4f18f5e1a57923af7aa19b15bdb2994dfb1ee6de"
target_path: "C:\\Users\\Mukund Karthikeyan\\BankVWA\\banking\\transfer.php"
timestamp: 2026-10-07T11-51-51Z
slug: banking-transfer-php
---
Method: dual-agent (A: design review · B: detector + rendered-HTML evidence)

## Design Health Score
| # | Heuristic | Score | Key Issue |
|---|---|---|---|
| 1 | Visibility of System Status | 2 | success = one small flash; no source/ref/balance/SR announce |
| 2 | Match System / Real World | 2 | "1,000" rejected; own-account transfer needs typing own number |
| 3 | User Control and Freedom | 2 | no review/cancel before money moves |
| 4 | Consistency and Standards | 3 | consistent; hint below button |
| 5 | Error Prevention | 1 | name only for saved payees; double submit; stale payee-confirm |
| 6 | Recognition Rather Than Recall | 2 | no payee picker |
| 7 | Flexibility and Efficiency | 3 | ?to= + autofocus good; no own-account/max shortcut |
| 8 | Aesthetic and Minimalist Design | 4 | four fields |
| 9 | Error Recovery | 3 | clear messages; not field-linked, focus lost |
| 10 | Help and Documentation | 3 | basics covered |
| Total | | 25/40 | Acceptable |

## Design Specificity Verdict
Bank-looking form, non-bank interaction (no review/name check/receipt). Detector: 0 findings on default, payee, error states; contrast canary flagged 12; unused-selector probes are ignored by the detector; print block neutral. No overlay.

## Priority Issues
- [P1] No recipient-name check / review step; payee-confirm built from ?to= goes stale if To edited. Two-step Review → Confirm with server re-validation. /impeccable shape
- [P1] Double submit can send twice: disable button on submit; one-time token later. /impeccable harden
- [P2] Errors/success silent to SR; focus to BODY after error. role=alert/status, focus first invalid field, aria-invalid/describedby. /impeccable harden
- [P2] Weak success receipt: receipt with amount/recipient/from/new balance/time/ref + links. /impeccable clarify
- [P3] No To picker for own accounts/payees; accept "1,000"/"₹500". /impeccable clarify

## Persona Red Flags
Alex: no picker, no max, "1,000" rejected, no repeat. Sam: focus lost after error, silent alerts, errors not field-linked. Jordan: can't verify number, limit after button, insufficient funds hides amount, self-transfer copy. Learner: stale payee-confirm; ?to= prefills strangers silently.

## Minor Observations
From default by sort order; limit hint → helper text; button could show amount; error relies on colour.

## Questions to Consider
Why does the secure reference skip review? Bind confirmation to submitted value? Success as an ending?
