---
name: nuk
description: Use when user invokes /nuk or asks for the "nuk workflow" — orchestrates a task lifecycle (Explore → Bug-Root-Cause-Verify (if bug) → Choice Questions → Impact → Option Review → **Branch from master FIRST (checkout+pull+create branch) before any edit** → TDD Red + Implementation-Route (frontend-design if UX/UI) Green → Post-fix Impact Explore → Commit + PR) with cross-cutting memory + CLAUDE.md update guard at every phase. Triggers for any task, feature, bugfix, or issue on Bless Hub. Bug/issue tasks MUST invoke `superpowers:systematic-debugging` after Phase 1 Explore. Frontend UX/UI changes MUST invoke `frontend-design` before implementing Green. Branch MUST be forked from up-to-date master BEFORE Phase 5 implementation — never edit code on a stale branch. Whenever a project-wide rule, infra path, gotcha, or convention is discovered/changed, update the appropriate ROOT or PACKAGE CLAUDE.md (not memory) following the documented template structure — confirm with user once per task before first edit. Output style is concise dataflow diagrams, not prose. Always invoke this skill when user types /nuk regardless of what comes after.
---

# /nuk — Concise Dataflow Workflow

## บทบาท

orchestrator ทำงานเป็น **7 phase ตามลำดับ** ทุกครั้ง output เป็น **dataflow / flow diagram** — ไม่อธิบายยืดยาว ไม่ใส่ prose ไม่ใส่หัวข้อใหญ่

## หลักการ output

```
ALWAYS:
  - dataflow / box-arrow / table
  - bullet สั้น ๆ
  - ตรงจุด
NEVER:
  - prose paragraph ยาว ๆ
  - อธิบาย what (โค้ดบอกอยู่แล้ว)
  - emoji (ยกเว้น user ขอ)
```

---

## Phase 1 — Explore (ก่อนตอบเสมอ)

```
[user task]
   │
   ▼
spawn Explore agent  ──►  files / refs / data shape / dependencies
   │
   ▼
สรุปสิ่งที่พบ ── 5-8 bullet สั้น
```

**บังคับ**: ใช้ `Agent(subagent_type=Explore)` ห้ามเดาจากความจำ
**ขอบเขต Explore**: เฉพาะส่วนที่ task พูดถึง + ไฟล์ที่ผูกกับมันโดยตรง 1 ระดับ

---

## Phase 1.5 — Task Classify + Bug Root Cause Verify

```
Phase 1 explore findings
   │
   ▼
classify task type
┌─────────────────────────────┬───────────────────────────────────────┐
│ Type                          │ Action                                 │
├─────────────────────────────┼───────────────────────────────────────┤
│ BUG / ISSUE / regression      │ → Phase 1.5a (systematic-debugging)    │
│ symptom report ("X ผิดเพี้ยน")  │   บังคับ — ห้ามข้าม                     │
├─────────────────────────────┼───────────────────────────────────────┤
│ FEATURE / NEW capability      │ → ข้ามไป Phase 2 ตรง ๆ                  │
│ REFACTOR / CHORE / DOC        │                                       │
└─────────────────────────────┴───────────────────────────────────────┘
```

### Phase 1.5a — Systematic Debugging (เฉพาะ bug/issue)

```
trigger Skill(superpowers:systematic-debugging)
   │
   ├─ reproduce: ขั้นตอนทำ bug ออก
   ├─ binary search: ตัด candidate cause ทีละข้อ ด้วยหลักฐาน
   ├─ confirm root cause:
   │     "ถ้าแก้จุด X → bug หาย"
   │     "ถ้า revert X → bug กลับมา"
   ├─ แยก symptom กับ root cause ให้ขาด
   ▼
output: 1-2 ประโยค "ROOT CAUSE: <ไฟล์:บรรทัด> เพราะ <เหตุผล>"
   + หลักฐาน (log / git blame / manual repro)
```

**กฎ**:
- ❌ ห้ามเข้า Phase 2 ด้วย symptom — symptom ≠ root cause
- ❌ ห้ามเสนอ fix จาก guess (เช่น "น่าจะเป็นที่ filter ขาด") — ต้องมีหลักฐานว่าใช่จริง
- ✅ ถ้า debug แล้วเจอ root cause หลายชั้น (proximate vs ultimate) → ระบุทั้งคู่
- ✅ ถ้า debug แล้วยัง ambiguous → กลับ Phase 1 ขยาย Explore แทนที่จะเดา

### ทำไมต้องบังคับ debug ก่อน
```
❌ ข้าม debug → ไป fix ตามอาการ → fix ผิดที่ → bug กลับมาในรูปแบบใหม่
✅ debug ก่อน → เจอ root cause → fix ตรงจุด → ปิดบั๊กถาวร
```

### feed เข้า Phase 2

```
ROOT CAUSE statement (จาก 1.5a)
   │
   ▼
Phase 2 Choice Questions ใช้เป็น input
  - เลือก scope (fix root cause vs symptom band-aid)
  - เลือก breadth (จุดเดียว vs pattern ทั้งโค้ดเบส)
```

---

## Phase 2 — Choice Questions (สงสัยตรงไหน → ถามเป็นตัวเลือก)

```
ส่วนที่ Explore แล้วยังไม่ชัด
   │
   ▼
AskUserQuestion (2-4 options ต่อข้อ, max 3 ข้อ)
   │
   ▼
[user answers]
```

**กฎ**:
- ถามเฉพาะที่ส่งผลต่อ **path การแก้** (scope, behavior, edge case)
- มี Recommended option เป็นข้อแรก
- ห้ามถามเรื่อง preference style (รักษา convention เดิม)

---

## Phase 3 — Impact Analysis (per option)

อิง ISO 31000 risk + Bohner/Arnold Change Impact Analysis — output ต้อง decision-ready (user เลือก Phase 4 ได้โดยไม่ต้องถามซ้ำ)

### Inputs

```
Phase 1 explore findings  ──┐
Phase 2 user answers      ──┴──►  candidate options (≥2)
```

### Dispatch — parallel บังคับ

```
candidate options ── N ตัว
        │
        ▼
single message → spawn Agent(subagent_type=Explore) × N พร้อมกัน
        │  per agent:
        │    scope    = 1 option เท่านั้น
        │    budget   = ≤10 file reads, ≤5 grep
        │    forbid   = re-explore สิ่งที่ Phase 1 ครอบคลุมแล้ว
        │    output   = Impact Matrix (schema ด้านล่าง)
        ▼
รวมผล → Comparison Roll-up
```

**กฎ**: option > 1 → ทุก Agent ต้องอยู่ใน 1 assistant message (multiple tool_use blocks). ห้าม sequential.

### Impact Dimensions (7 มิติ — บังคับครบทุก option)

```
┌─────────────────┬──────────────────────────────────────────────┐
│ Dimension       │ ตรวจอะไร                                       │
├─────────────────┼──────────────────────────────────────────────┤
│ 1 Code           │ caller, inheritance, interface, event,       │
│                 │ middleware, route binding                    │
│ 2 Data           │ schema change, migration, seed, index,       │
│                 │ existing row drift, soft-delete behavior     │
│ 3 Contract      │ API req/resp, DTO, JSON shape, frontend bind,│
│                 │ mobile/external consumer (pythai_app, PEx)   │
│ 4 Config / Env  │ .env, config/, queue, scheduler, cron,       │
│                 │ supervisor, feature flag                     │
│ 5 Cross-cutting │ org_id filter, permission, audit append,     │
│                 │ session, multi-tenancy invariant             │
│ 6 Performance   │ N+1, query plan, index miss, queue lag,      │
│                 │ worker memory budget                         │
│ 7 Observability │ log path, ErrorLogHandler, monitoring_*,     │
│                 │ Telegram hook, alert                         │
└─────────────────┴──────────────────────────────────────────────┘
```

### Risk Scoring (Likelihood × Severity — ISO 31000)

```
              Severity →
              Low    Med    High   Critical
Likelihood  ┌──────┬──────┬──────┬──────┐
   Rare     │  1   │  2   │  3   │  4   │
   Possible │  2   │  4   │  6   │  8   │
   Likely   │  3   │  6   │  9   │ 12   │
   Certain  │  4   │  8   │ 12   │ 16   │
            └──────┴──────┴──────┴──────┘

Severity guide:
  Low      = cosmetic / single user / fully reversible
  Med      = single-feature regression / single org
  High     = cross-feature regression / data drift fixable
  Critical = data loss / cross-org leak / posting mismatch / security
```

### Per-option Output (template — ห้าม prose)

```markdown
### Option <X>: <name>

Blast radius : local | module | package | system | external
Reversibility: full | partial | one-way

Impact Matrix
┌──────────────┬─────────────────────────────────┬──────┬──────┬──────┐
│ Dimension     │ Affected artifact (file:line)    │ Like.│ Sev. │ Risk │
├──────────────┼─────────────────────────────────┼──────┼──────┼──────┤
│ Code          │ BillingController.php:1240        │  L   │  M   │  6   │
│ Data          │ pytar_billing_lines idx_*         │  R   │  L   │  1   │
│ Contract      │ POST /api/billing schema           │  P   │  H   │  6   │
│ Cross-cutting │ eager load missing org_id          │  L   │  C   │ 12   │
│ Performance   │ N+1 customer relation              │  C   │  M   │  8   │
│ Observability │ covered by ErrorLogHandler         │  —   │  —   │  —   │
└──────────────┴─────────────────────────────────┴──────┴──────┴──────┘

Top risk   : cross-org leak via eager load (12)
Mitigation : scope query → where('org_id', session('org_id'))
Tests need : 3 (org isolation, N+1 guard, contract)
```

### Comparison Roll-up (feeds Phase 4)

```
┌────────────┬──────────┬─────────┬───────┬──────────────┐
│ Option     │ Max risk │ ~LOC    │ Tests │ Reversibility│
├────────────┼──────────┼─────────┼───────┼──────────────┤
│ A surgical │    6     │   30    │   3   │ full         │
│ B refactor │   12     │  250    │   8   │ partial      │
│ C workarnd │    4     │   10    │   1   │ full         │
└────────────┴──────────┴─────────┴───────┴──────────────┘
```

### Decision Gate

```
all options max risk ≤ 8       ──► proceed Phase 4
mixed (some ≤8, some ≥9)       ──► proceed Phase 4, mark high-risk
all options max risk ≥ 9       ──► loop กลับ Phase 2 (ขอ scope/constraint เพิ่ม)
critical (≥13) ใน option ใด     ──► drop option หรือ block จนกว่ามี mitigation
```

### Memory Hook (Phase 3 specific)

```
✅ save ถ้าเจอ:
  - cross-cutting leakage pattern ใหม่
  - external contract dep ที่ไม่ obvious (mobile bind, pythai_app field)
  - infra/queue/cron constraint ใหม่
  - performance landmine (N+1 ใน hot path, worker memory limit)
```

### AI Agent Efficiency Rules

```
✅ DO
  - parallel dispatch (1 message, N tool_use)
  - bounded scope per agent (file/grep budget)
  - structured table only — เทียบกันได้
  - quote ground truth (file:line) ทุก row
  - reuse Phase 1: ระบุชัด "covered in P1: X.php → skip"

❌ DON'T
  - sequential agent dispatch (เสีย latency × N)
  - prose paragraph (compare ยาก)
  - generic "may affect X" (ไม่ actionable)
  - re-explore Phase 1 findings
  - skip risk scoring → ไม่มี gate decision
```

---

## Phase 4 — Option Review (เสนอ approach เป็นตัวเลือก)

```
┌──────────────┬─────────────┬──────────────┐
│  Option A    │  Option B   │  Option C    │
├──────────────┼─────────────┼──────────────┤
│  surgical    │  minimal    │  workaround  │
│  files: X,Y  │  files: ... │  files: ...  │
│  risk: low   │  risk: med  │  risk: low   │
│  test: 2     │  test: 5    │  test: 1     │
└──────────────┴─────────────┴──────────────┘
       ▲ Recommended
```

`AskUserQuestion` → user เลือก

---

## Phase 4.5 — Branch Fork จาก master (บังคับ ก่อนแก้โค้ด)

```
user เลือก option แล้ว
   │
   ▼
ตรวจ git state ปัจจุบัน
   ├─ git status   (ต้อง clean / ไม่มี uncommitted)
   ├─ git branch --show-current
   ▼
ถ้า dirty → STOP ถาม user (stash / commit / discard)
ถ้า clean → ต่อ
   │
   ▼
git checkout master
   │
   ▼
git pull origin master            ◄── ต้อง pull ก่อน fork เพื่อได้ latest
   │
   ▼
classify branch type:
   fix/        bug/issue
   feat/       feature ใหม่
   refactor/   ❌ BLOCKED (Go-Live — ห้าม refactor)
   chore/      config/doc
   │
   ▼
git checkout -b <type>/<short-kebab-case>
   │
   ▼
✅ พร้อมแก้โค้ด — เข้า Phase 5
```

**กฎ**:
- ❌ ห้ามแก้โค้ดบน branch เก่าโดยไม่ pull master ก่อน — โค้ดอาจ stale ทำให้ conflict / แก้ผิด context
- ❌ ห้ามแก้โค้ดบน master โดยตรง
- ❌ ห้าม fork จาก branch อื่นที่ไม่ใช่ master (ยกเว้น user สั่งชัด)
- ✅ ถ้า branch ปัจจุบันคือ branch ที่ตั้งใจจะแก้อยู่แล้ว + เพิ่ง pull master rebased → ข้ามได้ แต่ต้อง verify ด้วย `git log master..HEAD`
- ✅ ถ้า user เคยตั้งชื่อ branch ไว้ใน sticky preference (memory) → ใช้ชื่อนั้น ไม่ตั้งใหม่

### ทำไมต้อง fork ก่อนแก้
```
❌ แก้ก่อน fork → โค้ดเก่า → merge conflict ตอน PR → เสีย context
❌ แก้บน master → push ไม่ได้ → ต้องย้ายไฟล์ → เสี่ยง lost
✅ fork จาก latest master → แก้บน working set ล่าสุด → PR clean
```

---

## Phase 5 — Design Test + TDD (Red → Green)

### Pre-test 5.0 — Param + Output Survey (บังคับ — ก่อนเขียน test ทุกครั้ง)

```
[function / endpoint / job ที่จะ test]
   │
   ▼
สำรวจให้ครบ — ห้ามข้าม:
  ├─ PARAM   : request body, query string, route param, session,
  │            header, env, config, factory state
  ├─ OUTPUT  : response field, status code, redirect, DB write,
  │            log entry, queue job, event, file write, email
  └─ INVARIANT: DB constraint, business rule, multi-tenant filter,
               permission, audit append, balance equation
```

```
ตาราง 1 — PARAM matrix
┌──────────────┬──────────┬──────────────────┬───────────────┐
│ Param        │ Type     │ Constraint        │ Test cases    │
├──────────────┼──────────┼──────────────────┼───────────────┤
│ org_id       │ int      │ session required  │ valid/missing │
│ as_of_date   │ date     │ Y-m-d             │ valid/empty/  │
│              │          │                   │ future/past   │
│ status       │ enum     │ 1..8              │ each value +  │
│              │          │                   │ invalid       │
│ ...          │          │                   │               │
└──────────────┴──────────┴──────────────────┴───────────────┘

ตาราง 2 — OUTPUT/SIDE-EFFECT matrix
┌──────────────┬──────────┬───────────────────────────────────┐
│ Output       │ Source   │ Assert method                      │
├──────────────┼──────────┼───────────────────────────────────┤
│ HTTP status  │ response │ assertStatus(...)                  │
│ JSON field X │ response │ assertJsonPath('data.X', ...)      │
│ DB row Y     │ DB       │ assertDatabaseHas / Missing       │
│ log entry Z  │ log      │ Log::assertLogged / spy            │
│ queue job W  │ queue    │ Queue::assertPushed                │
│ event E      │ event    │ Event::assertDispatched            │
│ audit row    │ DB       │ assertDatabaseHas('monitoring_*')  │
│ file F       │ storage  │ Storage::assertExists              │
└──────────────┴──────────┴───────────────────────────────────┘
```

```
GATING (ห้ามเขียน test ก่อนผ่าน):
❌ ห้ามเขียน test โดยไม่ทำตาราง 1 + ตาราง 2 ครบก่อน
✅ ทุก PARAM ต้องมี assertion อย่างน้อย 1 case (valid + edge)
✅ ทุก OUTPUT/side-effect ที่คาดหวัง ต้อง assert ทุกอัน
   (HTTP + JSON field + DB write + log + queue + event + file)
✅ INVARIANT ต้อง assert คงอยู่หลัง action
   (org_id filter, balance, append-only, permission)

WHY: ขาด assert = false-positive Green
     test pass แต่ logic จริงผิด ตรวจไม่เจอ
```

### Test design (ทุก task)

```
Validate Cases:        Flow Cases:
- input ปกติ           - happy path A→B→C
- input ขอบ            - error path
- input invalid        - permission/role
- empty / null         - cross-org
```

### TDD branch (เฉพาะ bug / issue)

```
Phase 5a  RED
  │
  ├─ เขียน test สะท้อน bug (ต้อง fail)
  │
  ▼ Bash run
  docker exec laradock-php-worker-1 sh -c "cd /var/www/bless-hub && vendor/bin/pest --filter=<TestName>"
  หรือ
  npx vitest run <test-file>
  │
  ▼
  ❌ Fail (ยืนยัน Red) ──► ถ้าผ่าน = test เขียนผิด
  │
Phase 5b  IMPLEMENTATION ROUTE (ดูตารางด้านล่าง) → GREEN
  │
  ▼ rerun test
  │
  ▼
  ✅ Pass (ยืนยัน Green)
  │
Phase 5c  REGRESSION
  │
  └─ run test suite ของ module ที่เกี่ยว → ห้าม fail เพิ่ม
```

**non-bug task** (feature ใหม่) → ข้าม 5a, ทำ 5b เลย แต่ test ต้องเขียนคู่ implement

### Phase 5b — Implementation Route (บังคับ classify ก่อน implement)

```
ก่อน implement Green
   │
   ▼
classify scope ของ change
┌──────────────────────────────────────┬──────────────────────────────────┐
│ Scope                                 │ Route                              │
├──────────────────────────────────────┼──────────────────────────────────┤
│ FRONTEND UX/UI                        │ → Skill(frontend-design)           │
│   (Vue component visual, layout,      │   บังคับ ก่อนเขียน Vue/CSS         │
│    spacing, color, typography,        │                                  │
│    interaction flow, modal, form,     │                                  │
│    a11y, responsive, redesign)        │                                  │
├──────────────────────────────────────┼──────────────────────────────────┤
│ BACKEND only                          │ → ทำ surgical TDD ปกติ              │
│   (controller, model, query, job,     │   (ตาม CLAUDE.md constraints)      │
│    migration, service)                │                                  │
├──────────────────────────────────────┼──────────────────────────────────┤
│ MIXED (BE + FE)                       │ → BE ก่อน (TDD) → FE ใช้           │
│                                       │   Skill(frontend-design)           │
└──────────────────────────────────────┴──────────────────────────────────┘
```

### Phase 5b-FE — Frontend Design Skill (เฉพาะ scope FE UX/UI)

```
trigger Skill(frontend-design)
   │
   ├─ design first: layout / state / interaction / a11y
   ├─ rationale ก่อนเขียน Vue/CSS
   ├─ คำนึงถึง convention เดิม:
   │     - Vue 3 + Element Plus + Tailwind CSS + scoped style
   │     - shared component (Htable, FlexibleDatePicker, ActionButton)
   │     - DateFormatters / Func utility
   │     - permissionMixin / v-permission
   ├─ output: Vue component / Blade view ที่ผ่าน Vitest test
   ▼
verify ใน browser ก่อน claim done (ตาม CLAUDE.md):
   - npm run dev → เปิด feature ในเบราว์เซอร์
   - test golden path + edge case
   - watch regression ของ feature อื่น
```

**Trigger frontend-design**:
```
✅ ใช้ frontend-design ถ้า:
  - เพิ่ม / แก้ Vue component ที่กระทบ visual
  - แก้ layout / spacing / color / typography
  - เปลี่ยน interaction flow (click, form, modal, drawer)
  - improve accessibility / responsive
  - redesign หรือ pattern ใหม่
  - เพิ่ม UI state (loading, empty, error)

❌ ข้าม frontend-design ถ้า:
  - bind variable เพิ่มโดยไม่กระทบ visual
  - fix typo ใน label / message
  - เพิ่ม API call / payload field (logic อย่างเดียว)
  - bug ที่ root cause อยู่ backend (FE แค่ render — แก้ BE)
```

---

## Phase 6 — Post-fix Impact Explore

```
[code ที่แก้]
   │
   ▼
spawn Explore agent  (medium breadth)
   ├─ caller ที่เรียก function ที่แก้
   ├─ frontend component ที่ bind data ที่เปลี่ยน
   ├─ test อื่นที่ touch path เดียวกัน
   ├─ migration / schema ที่ depend
   ▼
ผลกระทบ ── table:
┌─────────────┬──────────┬────────────┐
│ Affected    │ Type     │ Action     │
├─────────────┼──────────┼────────────┤
│ X.vue:120   │ caller   │ verify ok  │
│ Y test      │ regress  │ re-ran ✅   │
└─────────────┴──────────┴────────────┘
```

ถ้าเจอ impact ที่ไม่ได้ handle → กลับ Phase 5

---

## Phase 7 — Commit + PR

> branch ถูก fork จาก latest master ใน Phase 4.5 แล้ว — Phase นี้แค่ stage + commit + push + PR

### flow

```
ยืนยัน branch ปัจจุบัน = branch ที่สร้าง Phase 4.5
   │
   ▼
git status   (review สิ่งที่จะ commit)
   │
   ▼
git add <files แก้จริง — ห้าม -A>
   │
   ▼
git commit -m "<one-line ภาษาไทย ตาม convention repo>"
   │     (ห้าม Co-Authored-By Claude / AI signature)
   │
   ▼
ส่ง telegram (hook อัตโนมัติ)
   │
   ▼
[STOP] ──► AskUserQuestion: push + open PR ตอนนี้?
   │
   ├─ Yes → git push -u origin <branch> → gh pr create --base master
   └─ No  → จบที่ commit local
```

### PR body template

```markdown
## Summary
- <bullet what changed>

## Why
- <bullet root cause / requirement>

## Test
- [x] Pest <TestName> Red→Green
- [x] Vitest <FileName>
- [x] Manual: <steps>

## Impact (Phase 6)
- <bullet>
```

ห้ามใส่ "Generated with Claude Code" หรือ Co-Author Claude

---

## Cross-cutting — Memory Capture / Update

ทำงาน **ขนาน** ทุก phase ไม่ใช่ phase แยก — ระหว่างเดิน workflow ถ้าเจอสิ่งสำคัญ → save memory ทันที

### Memory paths

```
INDEX:    /home/josaha/.claude/projects/-home-josaha-01-Pprompt-collect-workspace-PYT-Bless/memory/MEMORY.md
FILES:    /home/josaha/.claude/projects/-home-josaha-01-Pprompt-collect-workspace-PYT-Bless/memory/<topic>.md
```

### Trigger — เมื่อไหร่ต้อง save

```
✅ SAVE ถ้าเจอ:
  - quirk / typo บังคับ (เช่น ptyar_invoice_comment_histories)
  - cross-org leakage pattern ใหม่
  - external system contract (API key, endpoint, auth model)
  - business rule ที่ไม่อยู่ใน code (เช่น ip=5/6/7 cancel rule)
  - legacy schema constraint (เช่น text vs nvarchar)
  - infra path (NFS mount, container name, port)
  - go-live / deadline / decision ที่ user บอก
  - feedback / correction จาก user (ทั้งบอกผิดและบอกถูก)
  - reference ระบบนอก (Linear, Slack, Grafana ID)

❌ ห้าม SAVE:
  - code pattern ที่ดูจาก code ได้
  - git history / who-changed-what
  - debug fix recipe (fix อยู่ใน code แล้ว)
  - ephemeral / current-conversation state
  - duplicate (มีอยู่แล้วใน MEMORY.md)
```

### Update vs Create

```
ก่อน save  ──►  อ่าน MEMORY.md ก่อน
   │
   ├─ มี entry ใกล้เคียง?
   │     ├─ yes → Edit ไฟล์เดิม (เพิ่ม/แก้ section ที่กระทบ)
   │     └─ no  → Write ไฟล์ใหม่ + เพิ่ม 1 บรรทัดใน MEMORY.md
   │
   └─ entry เดิมขัดแย้งกับสิ่งที่เพิ่งพบ?
         └─ Edit ทับ + log "Updated YYYY-MM-DD: <reason>" ในไฟล์
```

### Memory file template

```markdown
---
name: <kebab-case>
description: <1 บรรทัด — บอกตอนไหนควรนึกถึง>
type: user | feedback | project | reference
---

<rule / fact ที่จำ>

**Why:** <เหตุผล / incident / constraint>
**How to apply:** <ตอนไหนใช้ ที่ไหน>
```

### Insertion ใน MEMORY.md

```
เพิ่มภายใต้หมวดที่ตรง:
  - Architecture / Modules
  - Performance
  - Cross-cutting Concerns
  - Infra / Integration
  - Testing
  - Sticky User Preferences  (ถ้าเป็น preference ถาวร)

format: - [title.md](title.md) — <hook สั้น ๆ>
```

### Phase mapping — เช็คทุกครั้ง

```
P1 Explore     → เจอ schema quirk / typo / legacy → save (project/reference)
P2 Choice Q    → user ตอบ surprising / strict → save (feedback)
P3 Impact      → เจอ cross-cutting risk pattern → save (cross-cutting)
P4 Option      → user เลือก approach surprising → save (feedback)
P5 TDD         → เจอ test setup quirk / DB constraint → save (testing)
P6 Post-fix    → เจอ caller pattern ที่ต้องระวัง → save (architecture)
P7 Branch/PR   → user สั่งเรื่อง branch/commit style → save (sticky pref)
```

### Stale memory check

```
ก่อนใช้ memory ที่อ้างถึง file/function/flag:
   │
   ▼
verify ด้วย Read / grep
   │
   ├─ ยังอยู่ → ใช้ได้
   └─ หาย/เปลี่ยน → Edit memory ให้ตรง state ปัจจุบัน
                     หรือลบถ้าไม่ relevant แล้ว
```

---

## Cross-cutting — CLAUDE.md Update Guard

CLAUDE.md = project-wide instruction (check-in git → ทั้ง team ใช้)
Memory = personal/session (ของ user คนเดียว)
ทั้งคู่ต้อง **ไม่ duplicate** กัน

### Paths

```
ROOT          : /home/josaha/01_Pprompt_collect/workspace/PYT-Bless/CLAUDE.md
PACKAGE       : /home/josaha/01_Pprompt_collect/workspace/PYT-Bless/packages/<Domain>/<Pkg>/CLAUDE.md
USER GLOBAL   : /home/josaha/.claude/CLAUDE.md   (ห้ามแก้ — ของ user เอง)
```

### Trigger — เมื่อไหร่ต้อง update CLAUDE.md

```
✅ UPDATE ถ้าเจอ:
  - convention/rule ใหม่ที่ทั้ง team ต้องรู้ (ห้ามใช้ X / ต้องใช้ Y)
  - infra/path ที่เปลี่ยน (container name, DB host/port, queue name)
  - gotcha สำคัญที่ทำให้ fail ซ้ำๆ (typo บังคับ, schema quirk)
  - business rule ที่ไม่อยู่ใน code (status transition ห้าม, ip flag rule)
  - module/package architecture เปลี่ยน (เพิ่ม/ลบ package, refactor folder)
  - testing setup เปลี่ยน (DB credential, env file path, factory state)
  - new endpoint/route ที่กลายเป็น public contract
  - cross-cutting concern ใหม่ (multi-tenancy filter, permission, audit append)
  - file/method line numbers shift มากใน controller ใหญ่ (มี table อ้างอิงใน CLAUDE.md)

❌ ห้าม UPDATE สำหรับ:
  - debug fix recipe (อยู่ใน commit message / PR description พอ)
  - one-off task note
  - personal preference (ใช้ memory แทน)
  - ephemeral state (current branch, in-progress work)
  - duplicate ของ memory (CLAUDE.md ↔ memory ต้องเลือกที่เดียว)
  - narrative / "บทเรียน" — CLAUDE.md ต้องเป็น declarative rule ไม่ใช่ story
```

### Update Rule — ที่ไหน (root vs package)

```
┌──────────────────────────────────────┬─────────────────────────┐
│ Scope of change                       │ ที่ต้อง update            │
├──────────────────────────────────────┼─────────────────────────┤
│ project-wide constraint                │ ROOT CLAUDE.md          │
│ testing/infra/architecture overall    │                         │
│ multi-tenancy / cross-cutting concern  │                         │
│ table prefix / date format / global   │                         │
├──────────────────────────────────────┼─────────────────────────┤
│ package-specific (Invoice/Billing/    │ PACKAGE CLAUDE.md        │
│   Receipt/BankReconcile/...)          │                         │
│ endpoint table / model / scope         │                         │
│ business rule เฉพาะ package           │                         │
│ gotcha ของ package                     │                         │
├──────────────────────────────────────┼─────────────────────────┤
│ user/style preference                  │ memory (ไม่ใช่ CLAUDE.md)│
└──────────────────────────────────────┴─────────────────────────┘
```

### โครงสร้าง CLAUDE.md ที่ถูกต้อง (template)

**ROOT** ต้องมี (ตามลำดับ):
```
1. Project Overview     — 1-3 บรรทัด: project + tech stack + multi-tenancy
2. Commands             — Build / Dev / Test / Lint / Docker (กลุ่มชัด)
3. Architecture          — Package Structure / ServiceProvider / Routing /
                           Frontend / Auth / Queues / Error Handling
4. Key Conventions       — Table prefix / Date format / external system /
                           cross-cutting concern (cross-org, permission)
```

**PACKAGE** ต้องมี (ตามลำดับ):
```
1. Overview               — package ทำอะไร (1-2 บรรทัด)
2. File Map              — table: ไฟล์ / หน้าที่ / หมายเหตุ (line range ถ้าใหญ่)
3. API Endpoints         — table: Method / Route / Action / Line / Purpose
4. Model: <Name>         — Status Constants / flag / enum / Column Mapping /
                           Key Relationships / Key Scopes
5. Business Rules         — rule ของแต่ละ action (Confirm/Cancel/Unconfirm/...)
6. ⚠️ Gotchas             — quirk ที่ developer ต้องรู้ (numbered list)
7. Tests                 — file map + total count
```

### Update Flow

```
ก่อน update CLAUDE.md
   │
   ▼
1. Read CLAUDE.md ปัจจุบัน (root + package ที่เกี่ยว) — verify state
   │
   ▼
2. confirm กับ user ผ่าน AskUserQuestion ว่าควรเข้า CLAUDE.md จริงหรือไม่
   (เฉพาะครั้งแรกใน task เดียว — confirm 1 ครั้ง update หลายส่วนได้)
   │
   ▼
3. มี entry ใกล้เคียง?
   ├─ yes → Edit ทับ section เดิม (รักษา structure เดิม)
   └─ no  → แทรกใน section ที่ตรงตาม template structure
   │
   ▼
4. cross-check: เปิด memory ดู — ห้าม duplicate
   │     ├─ ถ้า memory มีแล้ว + ควรเป็น CLAUDE.md → ย้ายไป CLAUDE.md + ลบ memory
   │     └─ ถ้า CLAUDE.md ครอบแล้ว → memory พูดถึง path ไป CLAUDE.md แทน
   │
   ▼
5. log change ใน "Change Log" / "Recent Changes" section ถ้า template มี
```

### Phase mapping — เช็คทุกครั้ง

```
P1 Explore     → file map / endpoint table outdated → update PACKAGE CLAUDE.md
P1.5 Debug    → root cause = quirk ที่คนอื่นพลาดได้ → update Gotchas section
P2 Choice Q    → user สั่ง rule ใหม่ (เช่น ห้ามแก้ table) → update CLAUDE.md (Key Conventions)
P3 Impact      → cross-cutting risk pattern ใหม่ → update ROOT CLAUDE.md
P5 TDD         → testing setup เปลี่ยน (env, DB cred, factory) → update ROOT (Testing)
P6 Post-fix    → architecture change กระทบ caller → update Architecture/File Map
P7 Branch/PR   → branch convention เปลี่ยน → update ROOT (Commands/Git)
```

### Anti-patterns (ห้ามทำ)

```
❌ เขียน rule ลง CLAUDE.md ซ้ำกับ memory
❌ เขียน debug fix recipe ลง CLAUDE.md (ใช้ commit/PR แทน)
❌ เขียน narrative ("เคยเจอ X แล้วแก้ Y") — ใช้ rule declarative แทน
❌ แก้ CLAUDE.md โดยไม่ confirm user (กระทบทั้ง team)
❌ ไม่ตรงตามโครงสร้าง template ที่ section มีอยู่ (สร้าง section ใหม่นอกแบบ)
❌ duplicate section / endpoint table ที่อยู่ทั้ง root และ package
```

### Update vs Memory — ตัดสินใจอย่างไร

```
ถาม:  "rule นี้คนอื่นใน team ต้องรู้ตอนทำงาน Bless Hub ไหม?"
   ├─ yes → CLAUDE.md (root หรือ package)
   └─ no  → memory

ถาม:  "rule นี้เป็น preference ของผม josaha คนเดียวหรือทั้ง team?"
   ├─ ผมคนเดียว → memory (Sticky User Preferences)
   └─ ทั้ง team  → CLAUDE.md
```

---

## Constraints (จาก CLAUDE.md — บังคับ)

```
✅ surgical edit เท่านั้น (Go-Live — production แล้ว)
✅ ห้ามเพิ่ม function / method / class ใหม่ — แก้ของเดิมเท่านั้น
✅ ห้าม refactor — ห้ามย้าย ห้ามเปลี่ยนชื่อ ห้ามปรับโครงสร้าง
✅ ห้ามแก้เยอะ — scope ต้องเล็กที่สุดเท่าที่แก้ปัญหาได้
✅ comment ไทย, อธิบาย WHY
✅ comment สั้น 1-2 บรรทัดเท่านั้น
✅ no auto-fallback / no silent catch
✅ branch fork จาก master **ก่อนแก้โค้ด** (Phase 4.5) — pull latest ทุกครั้ง
✅ commit/PR ใน นาม josaha
❌ ห้าม reformat / restyle ไฟล์ที่ไม่ได้แก้
❌ ห้าม comment ยาวเป็น paragraph / multi-line block / docstring ยืด
❌ ห้าม comment restate WHAT (โค้ดบอกอยู่แล้ว) — เขียนเฉพาะ WHY ที่ไม่ obvious
❌ ห้าม php artisan migrate (สร้าง file ได้, DBA รัน)
❌ ห้ามรัน test/migrate ด้วย .env หลัก (ชี้ UAT)
```

### Comment policy (ตัวอย่าง)

```
❌ BAD (ยาวเกิน + restate code)
  // ฟังก์ชันนี้รับ org_id จาก session แล้ว query pytar_invoices
  // ที่มี status = 'Confirmed' จากนั้น loop ทุก row เพื่อ
  // คำนวณ remaining_amount แล้ว return เป็น array
  function getInvoices($orgId) { ... }

✅ GOOD (1-2 บรรทัด + WHY)
  // remaining ต้อง pull จาก histories_all กัน race ตอน sync rerun
  function getInvoices($orgId) { ... }

✅ GOOD (ไม่มี comment เลย ถ้า code ชัดในตัว)
  function calculateAgingDays($dueDate, $asOf) {
      return $asOf->diffInDays($dueDate);
  }
```

---

## Decision Tree (สรุป)

```
                /nuk + task
                     │
          ┌──────────▼──────────┐
          │  Phase 1: Explore   │
          └──────────┬──────────┘
                     │
          ┌──────────▼─────────────────┐
          │ Phase 1.5: Task Classify    │
          │   bug?  feature?  refactor? │
          └──────┬───────────────┬──────┘
                 │ bug/issue     │ feature/refactor/chore
                 ▼               │
        Phase 1.5a               │
        Skill(systematic-        │
              debugging)         │
        → ROOT CAUSE statement   │
                 │               │
                 └───────┬───────┘
                         ▼
              ┌──────────▼──────────┐
              │ ชัดเจนพอ?            │
              └──────┬───────┬──────┘
                     │ no    │ yes
                     ▼       │
            Phase 2 Choice Q │
                     │       │
                     ▼       │
            Phase 3 Impact   │
                     │       │
                     └───┬───┘
                         ▼
              Phase 4 Option Review
                         │
                    user เลือก
                         │
                         ▼
              ┌──────────▼─────────────────┐
              │ Phase 4.5 Branch Fork        │
              │ checkout master + pull       │
              │ + checkout -b <type>/<name>  │
              │ (บังคับ ก่อนแก้โค้ดทุกครั้ง)    │
              └──────────┬─────────────────┘
                         │
                         ▼
              Phase 5a RED (เฉพาะ bug)
                         │
                         ▼
              ┌──────────▼─────────────────┐
              │ Phase 5b Route: scope FE?   │
              └──────┬─────────────┬───────┘
                     │ FE UX/UI    │ BE only
                     ▼             │
            Skill(frontend-        │
                  design)          │
                  → Vue/CSS        │
                     │             │
                     └──────┬──────┘
                            ▼
                       GREEN + 5c REGRESSION
                            │
                            ▼
              Phase 6 Post-fix Explore
                            │
                            ▼
              Phase 7 Commit → ถาม push+PR

ทุก phase  ──►  ถ้าเจอสิ่งสำคัญ (quirk/rule/infra/feedback) → save/update memory ทันที
```

## Skill Routing Summary (บังคับ)

```
┌──────────────────────────┬──────────────────────────────────────────┐
│ Trigger                    │ Skill                                       │
├──────────────────────────┼──────────────────────────────────────────┤
│ task = bug / issue /        │ superpowers:systematic-debugging           │
│ regression                  │ — invoke ที่ Phase 1.5a บังคับ               │
├──────────────────────────┼──────────────────────────────────────────┤
│ scope แตะ FE UX/UI         │ frontend-design                            │
│ (Vue, layout, interaction)  │ — invoke ที่ Phase 5b ก่อนเขียน Vue/CSS      │
└──────────────────────────┴──────────────────────────────────────────┘
```

## Documentation Guard (บังคับ)

```
┌──────────────────────────┬──────────────────────────────────────────┐
│ Where the change belongs   │ File to update                              │
├──────────────────────────┼──────────────────────────────────────────┤
│ project-wide rule/infra/    │ ROOT CLAUDE.md                             │
│ architecture/cross-cutting  │ /home/.../PYT-Bless/CLAUDE.md             │
├──────────────────────────┼──────────────────────────────────────────┤
│ package endpoint/business   │ PACKAGE CLAUDE.md                          │
│ rule/gotcha/file map        │ packages/<Domain>/<Pkg>/CLAUDE.md          │
├──────────────────────────┼──────────────────────────────────────────┤
│ user/session preference     │ memory (ไม่ใช่ CLAUDE.md)                   │
└──────────────────────────┴──────────────────────────────────────────┘

กฎ: confirm กับ user 1 ครั้งใน task → ตามด้วย Edit ตรง ๆ ตาม template structure
     CLAUDE.md ต้อง declarative rule, ไม่ duplicate กับ memory
```

## ห้าม skip phase

ถ้า task เล็กมาก (1 บรรทัด typo) → ทำสั้น แต่ยังต้องผ่านทุก phase (Phase 2/3/4 อาจรวบเป็น 1 ตาราง)
- Phase 1.5a debug — บังคับเฉพาะ bug; ห้ามข้ามถ้าเป็น bug แม้จะเล็ก
- Phase 4.5 Branch Fork — บังคับทุก task ที่มีการแก้โค้ด; ห้ามแก้บน branch เก่าโดยไม่ pull master, ห้ามแก้บน master โดยตรง (typo 1 บรรทัดก็ต้อง fork)
- Phase 5b frontend-design — บังคับเฉพาะแตะ FE UX/UI; bind variable ไม่นับ
- CLAUDE.md update guard — บังคับเช็คทุก phase; ถ้าเจอ trigger ต้อง confirm + update ก่อน Phase 7
