---
name: nuk-accounting
description: Use when /nuk task touches AR accounting in Bless Hub — Invoice GL posting, Receipt apply, Aging bucket, WHT, write-off, in_process_status transitions, double-entry validation. Wraps /nuk 7-phase workflow with mandatory ar-accounting-bless domain knowledge. Thin trigger that delegates to ar-accounting-bless skill — does not duplicate its rules.
---

# /nuk-accounting — AR Domain Wrapper

## Trigger

```
ALWAYS เรียกเมื่อ task แตะหนึ่งใน:
  - GL account selection (receivable / BR / write-off / WHT)
  - in_process_status transition (1→2→3→...→7/8)
  - Receipt apply / unapply / reverse
  - Aging bucket calculation / outstanding
  - Double-entry posting validation
  - Cancel flow (Invoice / Receipt / Billing) ที่กระทบ GL
  - Sync invoice / receipt ที่กระทบ ip / amount
```

## Pre-flight (บังคับก่อน Phase 1)

```
LOAD ar-accounting-bless skill ก่อนเสมอ
   │
   ▼
ใช้เนื้อหาใน ar-accounting-bless เป็น source of truth สำหรับ:
  - Double-entry tables (Invoice/Billing/Receipt/WHT/Discount)
  - 3 GL selection cases
  - 7-bucket aging logic + outstanding formula
  - in_process_status meaning ทั้ง 8 codes
  - 7 common mistakes
```

ห้าม inline copy เนื้อหา ar-accounting-bless ใน skill นี้ — link reference เท่านั้น

## Workflow (override /nuk)

```
Phase 1 Explore  ──► spawn Explore agent + ขอให้ verify GL posting logic
                     ตาม ar-accounting-bless ก่อนสรุป
                     │
                     ▼
                     ถ้า code ปัจจุบันขัด ar-accounting-bless rule
                     → flag ใน Phase 2 Choice Q ทันที

Phase 3 Impact   ──► เพิ่มเช็ค: GL posting balance, audit hook,
                     interface error (pytar_*_interface)

Phase 5 Test     ──► Validate cases ต้องครอบ:
                     - GL account ถูกเลือกตาม br_number / receivable
                     - in_process_status transition valid
                     - Receipt balance = Invoice apply + adjustments
                     - Aging bucket ตาม dynamic config
                     - Reverse logic ไม่ทิ้ง orphan adjustment
                     - Cross-org isolation (ดู cross-org-leakage memory)

Phase 6 Post-fix ──► run 3 verification queries จาก ar-accounting-bless:
                     - receipt balance check
                     - aging consistency
                     - orphan adjustment
```

## Common Mistakes (จาก ar-accounting-bless — บังคับเช็ค)

```
1. Aging ใช้ pytar_invoices.remaining_amount (ผิด) → ต้องใช้ histories_all
2. BR posting ผิด GL เพราะข้าม br_number logic
3. Receipt balance ไม่ตรง — ลืม pytar_receipt_adjustments
4. Reverse interface ไม่ trigger error log
5. WHT amount ลืม attribute_category
6. Sync race — invoice ip=5/6/7 ใน Bless มาจาก Receipt apply ไม่ใช่ sync
7. Outstanding calc ขาด histories_all OUTER APPLY
```

## Constraints (เพิ่มจาก /nuk เดิม)

```
✅ ทุก GL post ต้อง balance (debit = credit)
✅ ทุก org_id filter ต้องอยู่ — ห้าม cross-org leak
✅ Test ต้อง assert GL posting + interface error
❌ ห้าม fallback GL account เอง (ดู ar-accounting-bless case 3)
❌ ห้าม mutate pytar_invoices direct — ใช้ in_process_status flow
```

## Memory hook

```
P1 Explore  → ถ้าเจอ GL bug pattern ใหม่ → save (project)
P3 Impact   → ถ้าเจอ cross-org leakage → update cross-org-leakage.md
P5 TDD      → ถ้าเจอ accounting test setup quirk → save (testing)
P6 Post-fix → ถ้าเจอ verification query ใหม่ → update ar-accounting-bless reference
```

## Reference

- `.claude/skills/ar-accounting-bless/SKILL.md` (203 lines — domain rule ทั้งหมด)
- `Documentation/AccountingFlow.md` ใน Bless Hub repo
- /nuk parent: `.claude/skills/nuk/SKILL.md`
