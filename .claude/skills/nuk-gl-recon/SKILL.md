---
name: nuk-gl-recon
description: Use when /nuk task involves GL reconciliation — cross-check Receipt→GL posting balance, Invoice→GL posting, BR posting trace, break tracing for posting mismatch. Applies fund-admin GL recon pattern from anthropics/financial-services repo (external reference, not installed). Wraps /nuk 7-phase with reconciliation methodology.
---

# /nuk-gl-recon — GL Reconciliation Wrapper

## Trigger

```
เรียกเมื่อ task เกี่ยวกับ:
  - ตรวจ posting Receipt → GL ว่า balance ตรง
  - ตรวจ posting Invoice → GL หลัง confirm/cancel
  - หา "break" — รายการที่ posted แต่ไม่ตรงกับ source
  - Trace root cause posting mismatch
  - Reconcile pytar_*_interface ↔ Oracle EBS
  - ตรวจ orphan posting (มีใน GL แต่ไม่มี source)
```

## Reference Pattern (External — fund-admin)

```
Source: anthropics/claude-for-financial-services (NOT installed locally)
        plugins/agent-plugins/gl-reconciler/
        plugins/vertical-plugins/fund-admin/skills/

Install (optional, opt-in):
  claude plugin marketplace add anthropics/claude-for-financial-services
  claude plugin install fund-admin@claude-for-financial-services

ถ้าไม่ install → ใช้ pattern summary ด้านล่าง
```

## GL Recon Methodology (สรุปจาก fund-admin)

```
Step 1  ── Snapshot 2 sides
            │
            ├─ Source: pytar_receipts + pytar_receipt_lines (Bless)
            └─ Target: GL posting / Oracle EBS interface

Step 2  ── Compute balance ต่อ entity
            │
            ├─ source_total  = Σ(receipt_amount - wht - adjustment)
            └─ target_total  = Σ(GL credit ฝั่ง Customer A/R)

Step 3  ── หา break
            │
            ├─ where source_total ≠ target_total
            └─ group by document_number / posting_date

Step 4  ── Trace root cause
            │
            ├─ missing posting (source มี, target ไม่มี)
            ├─ extra posting (target มี, source ไม่มี)
            ├─ amount mismatch (มีทั้ง 2 แต่ค่าต่าง)
            └─ wrong GL account (br_number / receivable mix)

Step 5  ── Document break + propose fix
            (ต้อง human review ก่อน auto-fix)
```

## Workflow (override /nuk)

```
Phase 1 Explore  ──► spawn Explore + dump 2 SQL:
                     - source: pytar_receipts/lines/adjustments
                     - target: GL/interface table
                     compare ทันที

Phase 3 Impact   ──► หา break ที่อาจเกิด — list document_numbers ที่ risky

Phase 4 Option   ──► เสนอ:
                     A. Fix root cause + reverse posting
                     B. Adjustment journal entry
                     C. Investigation only (no fix)

Phase 5 Test     ──► reconciliation test:
                     - Σ source = Σ target
                     - no orphan in target
                     - no missing in target
                     - wht / adjustment ครบ
```

## Bless Hub-specific Tables

```
Source side:
  pytar_receipts            (header)
  pytar_receipt_lines       (apply lines)
  pytar_receipt_adjustments (write-off / bank charge)
  pytar_invoices            (Invoice ฝั่ง credit)

Target side:
  pytar_receipt_interface   (Oracle EBS bridge)
  pytar_invoice_interface
  monitoring_*_logs         (interface error)

Cross-check:
  apply_flag in pytar_receipt_adjustments — ดู br-cancel-detection memory
```

## Constraints

```
✅ ห้าม auto-fix break — flag ให้ DBA / accountant review
✅ ทุก break ต้อง trace ถึง document level
❌ ห้าม mass update GL posting โดยไม่มี audit trail
```

## Memory hook

```
P1 Explore  → quirk ของ interface table → save (project)
P3 Impact   → break pattern ใหม่ → save (cross-cutting)
P6 Post-fix → recon SQL ที่ใช้ได้จริง → save (architecture)
```

## Reference

- fund-admin skill (external — install ตาม instruction ด้านบน)
- ar-accounting-bless: GL Account Selection (3 cases) + interface error tracking
- Bless Hub: `Documentation/SyncManagement.md` (interface flow)
- /nuk parent: `.claude/skills/nuk/SKILL.md`
