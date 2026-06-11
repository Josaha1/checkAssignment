---
name: nuk-audit
description: Use when /nuk task involves audit trails — variance commentary on monitoring activity, sync error investigation, posting mismatch root cause, change history reconstruction. Applies fund-admin audit/variance pattern from anthropics/financial-services repo (external reference, not installed). Wraps /nuk 7-phase with audit-first lens.
---

# /nuk-audit — Audit / Variance Commentary Wrapper

## Trigger

```
เรียกเมื่อ task เกี่ยวกับ:
  - Variance commentary (อธิบายตัวเลขเปลี่ยนทำไม)
  - Sync error / interface error investigation
  - Reconstruct change history ของ document (Invoice / Receipt)
  - Audit trail ของ approval workflow
  - User activity reconstruction
  - Posting mismatch root cause analysis
```

## Reference Pattern (External — fund-admin)

```
Source: anthropics/claude-for-financial-services (NOT installed)
        - LP statement auditing
        - NAV variance commentary
        - Break tracing & root cause

Install (optional):
  claude plugin install fund-admin@claude-for-financial-services
```

## Audit Methodology

```
Variance commentary structure (จาก fund-admin):

  Δ value       :  current - prior
  Δ driver      :  list event ที่ทำให้เปลี่ยน
  Δ explanation :  ทำไม event นั้นเกิด (root cause)
  Δ verification:  query ที่พิสูจน์ explanation

Apply กับ Bless Hub:
  Document changed:
    - what changed   → diff field-by-field จาก histories_all
    - who changed    → monitoring_invoice_activities.actor
    - when changed   → created_at + activity_type
    - why changed    → cross-ref กับ sync_histories / receipt event
```

## Bless Hub Audit Tables (4 sources)

```
1. monitoring_invoice_activities      (append-only, user action + system event)
2. pytar_invoices_histories_all        (state snapshot ทุก write)
3. ptyar_invoice_comment_histories     (typo "ptyar" — ดู memory, comment thread)
4. monitoring_error_logs               (exception lifecycle จาก ErrorLogHandler)

Sync-specific:
  sync_histories                       (CSV batch sync record)
  invoice-sync-trace-*.log             (file log per sync run)
  sync-audit-*.log                     (old/new diff)
  org-{ID}/invoice-sync-*.log          (per-org sync log)

⚠️ ไม่มี full row snapshot (ดู invoice-sync-investigation memory)
```

## Workflow (override /nuk)

```
Phase 1 Explore  ──► spawn Explore + dump activity timeline ของ document
                     UNION 4 sources ข้างบน → order by created_at

Phase 3 Impact   ──► เช็คว่า change ใหม่จะ append audit เพิ่มไหม
                     - missing activity log = audit gap (ห้าม)
                     - silent error catch = no monitoring_error_logs (ห้าม)

Phase 5 Test     ──► audit-aware test:
                     - assert activity row ถูก append หลัง action
                     - assert error log มี exception trace
                     - assert sync_histories record มี
                     - timeline order ไม่กลับ

Phase 6 Post-fix ──► reconstruct timeline ทดสอบ regression
                     - ใช้ same UNION query
                     - ไม่มี gap / duplicate
```

## Variance Commentary Template

```markdown
## Variance: <Document Number>
**Field**: <e.g., remaining_amount>
**Δ**: <prior_value> → <current_value> (Δ <amount>)
**Driver**: <e.g., Receipt #R-001 apply 1500 บาท>
**Root Cause**: <e.g., Invoice ip=4 → ip=6 หลัง full apply>
**Verification**:
  ```sql
  SELECT * FROM pytar_invoices_histories_all WHERE document_id = X
  ```
**Audit trail**:
  - monitoring_invoice_activities row #...
  - sync_histories run #...
```

## Constraints

```
✅ ทุก action ต้อง append audit row — silent change = bug
✅ ทุก exception ต้องลง monitoring_error_logs (ErrorLogHandler register แล้ว)
✅ Variance ต้อง verifiable ด้วย SQL/log
❌ ห้าม empty try-catch (CLAUDE.md no silent error)
❌ ห้าม mutate audit table (append-only)
```

## Memory hook

```
P1 Explore  → audit gap pattern → save (architecture)
P3 Impact   → variance pattern → save (cross-cutting)
P6 Post-fix → SQL ที่ reconstruct timeline ได้ → save (architecture)
```

## Reference

- ar-accounting-bless: Audit Hooks (4 monitoring tables)
- invoice-timeline-attachments memory (UNION 4 sources query)
- invoice-sync-investigation memory (sync audit limit)
- /nuk parent: `.claude/skills/nuk/SKILL.md`
