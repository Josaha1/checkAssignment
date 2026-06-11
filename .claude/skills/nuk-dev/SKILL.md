---
name: nuk-dev
description: Use when /nuk task is standard development workflow — non-accounting feature/bugfix/refactor on Bless Hub. Identical to bare /nuk lifecycle (Explore → Choice → Impact → Option → **Branch Fork from master** → TDD → Post-fix → Commit+PR). Branch is forked from up-to-date master BEFORE any code edit. Use this explicit form when user wants to scope task as "dev only — no accounting domain wrapper".
---

# /nuk-dev — Standard Dev Workflow

## Trigger

```
เรียกเมื่อ task เป็น:
  - Frontend / UI work (Vue components, Element Plus, Tailwind)
  - Non-accounting backend (auth, permission, sync infra, queue, log)
  - Performance / cleanup (rare — Go-Live)
  ❌ refactor BLOCKED (Go-Live)
  - Documentation only
  - Test infrastructure (Pest setup, factory, trait)
  - Integration (Telegram hook, Microsoft Graph, etc.)

ห้ามเรียกเมื่อ:
  - แตะ GL posting / in_process_status / Receipt apply / Aging
    → ใช้ /nuk-accounting แทน
  - แตะ posting reconciliation
    → ใช้ /nuk-gl-recon แทน
  - แตะ aging bucket / period boundary
    → ใช้ /nuk-accrual แทน
  - แตะ audit trail / variance / sync error
    → ใช้ /nuk-audit แทน
```

## Workflow

```
ใช้ /nuk parent SKILL.md ตรง ๆ:
  .claude/skills/nuk/SKILL.md

phase ตามลำดับ:
  P1   Explore       → Agent(Explore) สำรวจไฟล์ที่กระทบ
  P2   Choice Q      → AskUserQuestion ตอน scope ไม่ชัด
  P3   Impact        → Explore impact ของแต่ละคำตอบ
  P4   Option Review → เสนอ approach 2-3 ตัว เลือก Recommended
  P4.5 Branch Fork   → checkout master + pull → checkout -b <type>/<name>
                       (บังคับ ก่อนแก้โค้ดทุกครั้ง — เพื่อให้แก้บน latest)
  P5   TDD           → Red→Green (bug) / write test คู่ implement (feature)
  P6   Post-fix      → Explore caller / regression
  P7   Commit + PR   → git add + commit (josaha sign), ถาม push+PR?
```

## Difference จาก bare /nuk

```
bare /nuk     → /nuk-dev workflow (default — ตอน task type ยังไม่ชัด)
/nuk-dev      → explicit ระบุว่าไม่ใช่ accounting domain
/nuk-accounting → AR domain wrapper (Bless Hub)
/nuk-gl-recon  → GL recon wrapper (fund-admin pattern)
/nuk-accrual   → period close wrapper
/nuk-audit     → variance / audit wrapper

= /nuk-dev ≡ bare /nuk (เป็น alias)
  ใช้ /nuk-dev เมื่ออยาก signal "task นี้ไม่ใช่ accounting"
```

## Constraints (เดียวกับ /nuk parent)

```
✅ surgical edit เท่านั้น (Go-Live — production แล้ว)
✅ ห้ามเพิ่ม function / method / class ใหม่ — แก้ของเดิมเท่านั้น
✅ ห้าม refactor — ห้ามย้าย ห้ามเปลี่ยนชื่อ ห้ามปรับโครงสร้าง
✅ ห้ามแก้เยอะ — scope ต้องเล็กที่สุดเท่าที่แก้ปัญหาได้
✅ comment ไทย, อธิบาย WHY
✅ no auto-fallback / no silent catch
✅ branch fork จาก master **ก่อน** Phase 5 (P4.5) — pull latest ทุกครั้ง ห้ามแก้บน branch เก่า/master โดยตรง
✅ commit/PR ใน นาม josaha
❌ ห้าม reformat / restyle ไฟล์ที่ไม่ได้แก้
❌ ห้าม php artisan migrate (สร้าง file ได้, DBA รัน)
❌ ห้ามรัน test/migrate ด้วย .env หลัก (ชี้ UAT)
```

## Memory hook (เดียวกับ /nuk parent)

```
ทุก phase → ถ้าเจอ quirk/rule/infra/feedback → save/update memory
```

## Reference

- /nuk parent: `.claude/skills/nuk/SKILL.md` (383 lines — full 7-phase definition)
