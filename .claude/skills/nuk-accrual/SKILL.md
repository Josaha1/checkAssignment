---
name: nuk-accrual
description: Use when /nuk task involves accrual / period close concepts — aging bucket boundary, outstanding cross-period, roll-forward of remaining_amount, period-end snapshot. Applies fund-admin accrual pattern from anthropics/financial-services repo (external reference, not installed). Wraps /nuk 7-phase with period-aware testing.
---

# /nuk-accrual — Accrual / Period Close Wrapper

## Trigger

```
เรียกเมื่อ task เกี่ยวกับ:
  - Aging bucket calculation ที่ข้าม period
  - Outstanding amount ณ as-of date (snapshot)
  - Roll-forward remaining_amount จาก prior period → current
  - Period boundary cutoff (month-end / quarter-end)
  - Histories_all OUTER APPLY logic (point-in-time)
  - Cancel/reverse ย้อน period
```

## Reference Pattern (External — fund-admin)

```
Source: anthropics/claude-for-financial-services (NOT installed)
        accrual & roll-forward management methodology

Install (optional):
  claude plugin install fund-admin@claude-for-financial-services
```

## Accrual Methodology (สรุปจาก fund-admin)

```
Concept: Outstanding amount ณ point-in-time
   │
   ├─ ไม่ใช่ "current remaining_amount"
   │   (ค่าเปลี่ยนตามทุก receipt apply / adjustment)
   │
   └─ ต้องคำนวณจาก history snapshot ที่ ≤ as-of date
        │
        ▼
        OUTER APPLY (
          SELECT TOP 1 ... FROM histories_all
          WHERE document_id = ... AND created_at ≤ @as_of
          ORDER BY created_at DESC
        )

Roll-forward:
  prior_outstanding   (ณ end ของ period N-1)
  + new posting       (period N additions)
  - apply             (period N receipts)
  - adjustment        (period N write-off / WHT / bank charge)
  = current_outstanding (ณ end ของ period N)

  ✓ verify: prior + delta = current
```

## Bless Hub-specific (ระวัง)

```
pytar_invoices.remaining_amount  ← MUTABLE (ห้ามใช้คำนวณ aging ย้อน period)

ใช้แทน:
  pytar_invoices_histories_all   ← snapshot ทุก state change
                                    NO PRIMARY KEY (ดู memory)

7-bucket aging (ดู ar-accounting-bless):
  - dynamic config จาก pytar_lookup_type_rows
  - bucket boundary คำนวณจาก due_date - as_of_date
  - outstanding ต้องมาจาก histories_all OUTER APPLY
```

## Workflow (override /nuk)

```
Phase 1 Explore  ──► verify ทุก SQL ใช้ histories_all สำหรับ "as-of"
                     ห้ามใช้ pytar_invoices.remaining_amount โดยตรง

Phase 3 Impact   ──► เช็ค period boundary:
                     - cancel/reverse ย้อน period กระทบ aging อย่างไร
                     - sync invoice ใหม่ใน period ปัจจุบันกระทบ snapshot prior period?

Phase 5 Test     ──► period-aware test:
                     - aging ณ end-of-month-1 vs end-of-month-2 (mid-stream apply)
                     - reverse receipt → outstanding ย้อนกลับ
                     - sync invoice หลัง period close → ไม่กระทบ snapshot
                     - dynamic bucket config เปลี่ยน → re-bucket ทุก row

Phase 6 Post-fix ──► roll-forward sanity check:
                     prior + delta = current (ทุก org)
```

## Constraints

```
✅ Outstanding ณ as-of date → histories_all เท่านั้น
✅ Period close → snapshot ห้ามแก้ย้อน (audit trail)
✅ Roll-forward ต้อง balance
❌ ห้ามใช้ remaining_amount สำหรับ aging report ย้อน
❌ ห้าม mass-update histories_all (append-only)
```

## Memory hook

```
P1 Explore  → period boundary edge case → save (cross-cutting)
P3 Impact   → reverse-period gotcha → save (architecture)
P5 TDD      → period-aware factory pattern → save (testing)
```

## Reference

- ar-accounting-bless: AR Aging Calculation + dynamic bucket
- aging-report-architecture memory
- invoice-list-architecture memory (histories_all schema, no PK)
- /nuk parent: `.claude/skills/nuk/SKILL.md`
