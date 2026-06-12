# Deploy ฟรีบน Render + Neon Postgres (+ หน้า "รอปลุก")

สรุป: แอปรันบน **Render free** (หลับเมื่อไม่มีคนใช้ ~15 นาที) ใช้ **Neon Postgres ฟรี** เก็บข้อมูลถาวร
และมีหน้า **wake** (โฮสต์ฟรีแยก) คอยรอปลุกตอนหลับ → ทั้งหมด **$0/เดือน**

```
ผู้ใช้ → [wake page (เปิดตลอด)] → ping จนแอปตื่น → [Render app] → [Neon Postgres]
```

---

## 1) สร้าง Neon Postgres (ฟรี)

1. สมัคร <https://neon.tech> → New Project (เลือก region `Singapore`/ใกล้ไทย)
2. หน้า Dashboard → **Connection Details** จะได้:
   ```
   Host:     ep-xxxx.ap-southeast-1.aws.neon.tech
   Database: neondb
   User:     xxxx
   Password: xxxx
   ```
   (Neon บังคับ SSL — เราตั้ง `DB_SSLMODE=require` ให้แล้ว)

## 2) Deploy แอปบน Render

1. Push โค้ดขึ้น GitHub แล้ว (repo นี้มี `Dockerfile` + `render.yaml` พร้อม)
2. <https://render.com> → **New → Blueprint** → เลือก repo → Render อ่าน `render.yaml` เอง
3. กรอกค่า env ที่เป็น `sync:false`:
   | Key | ค่า |
   |-----|-----|
   | `APP_KEY` | รันที่เครื่อง: `./art.sh artisan key:generate --show` แล้ววางทั้ง `base64:...` |
   | `APP_URL` | `https://<ชื่อ-service>.onrender.com` |
   | `DB_HOST` `DB_DATABASE` `DB_USERNAME` `DB_PASSWORD` | จาก Neon |
4. Create → Render จะ build Docker, รัน `migrate --seed` อัตโนมัติตอนบูต
5. เปิด `https://<service>.onrender.com/admin/login` → `admin@example.com` / `password`

### เชื่อม Google Drive (สำหรับอัปไฟล์งาน)

1. Google Cloud Console → เปิด **Google Drive API** + สร้าง **OAuth Client (Web)**
2. Authorized redirect URI: `https://<service>.onrender.com/admin/google/callback`
3. ตั้ง env เพิ่มใน Render: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`
4. (Workspace) ตั้ง OAuth consent เป็น **Internal** → token ไม่หมดอายุ ไม่ต้อง verify
5. ในเว็บ → `/admin/drive` → กด "เชื่อมต่อ Google Drive" แล้ว login บัญชีมหาลัย (1TB)

> ครั้งแรกที่เข้าเว็บหลังหลับ จะรอ ~30–60 วิ (cold start) — แก้ด้วยข้อ 3

## 3) หน้า "รอปลุก" (wake) — โฮสต์ฟรีแยก

ไฟล์อยู่ที่ `deploy/wake/index.html`

1. แก้บรรทัดเดียว: `const APP_URL = "https://<service>.onrender.com";`
2. โฮสต์ฟรี (เลือกอย่างใดอย่างหนึ่ง — ที่พวกนี้ "เปิดตลอด ไม่หลับ"):
   - **Cloudflare Pages**: สร้าง project ชี้โฟลเดอร์ `deploy/wake` (หรือ drag-drop ไฟล์)
   - **GitHub Pages**: เอา `index.html` ขึ้น repo แล้วเปิด Pages
   - **Netlify**: drag-drop โฟลเดอร์ `deploy/wake`
3. ให้ผู้ใช้เปิด **URL ของหน้า wake** เป็นทางเข้าเว็บ
   → หน้านี้จะ ping แอป Render, โชว์สปินเนอร์ "กำลังเปิดระบบ", พอแอปตื่นเด้งเข้าเว็บอัตโนมัติ

## 4) (ทางเลือก) ไม่อยากให้หลับเลย — keep-alive

แทนที่จะรอปลุก ให้ ping แอปทุก ~10 นาที เพื่อกันหลับ:
- สมัคร <https://uptimerobot.com> (ฟรี) → Monitor type `HTTP`, URL = `https://<service>.onrender.com/up`, interval 5–10 นาที
- ผล: แอบไม่ค่อยหลับ → แทบไม่มี cold start (ใช้ชั่วโมงฟรีของ Render เกือบเต็มเดือน แต่ 1 service ยังอยู่ในโควต้า ~750 ชม.)

> ใช้ "หน้า wake" หรือ "keep-alive" อย่างใดอย่างหนึ่งก็พอ (จะใช้คู่กันก็ได้)

---

## หมายเหตุ

```
• ข้อมูลอยู่ใน Neon (ถาวร) — redeploy/หลับ ไม่หาย ✅
• migrate/seed รันทุกครั้งที่บูต แต่ idempotent (ไม่ซ้ำ/ไม่พัง)
• Production จริงควรลบ demo seed (นักศึกษา/วิชาตัวอย่าง) ออกจาก DatabaseSeeder
• อยาก always-on จริงจัง (ไม่หลับ ไม่ cold start) → ขยับเป็น Render Starter $7/mo
```
