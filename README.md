# ระบบส่งงานนักศึกษา (Student Assignment Submission)

ระบบให้นักศึกษาส่งงานด้วย **ลิงก์ Google Drive / online storage** และให้อาจารย์ตรวจ
ให้คะแนนรายชิ้นงาน พร้อม export คะแนนเป็น CSV — รองรับนักศึกษา ~200 คน

## Tech Stack

| ส่วน | เทคโนโลยี |
|------|-----------|
| Backend | Laravel 11 (PHP 8.4) |
| UI | Livewire + Tailwind CSS + Flowbite + Lucide icons |
| กราฟ | ApexCharts (แดชบอร์ด) |
| ธีม | Academic indigo + dark mode (สลับได้, จำค่าใน localStorage) |
| Database | SQLite (ไฟล์เดียว, ไม่ต้องตั้ง DB server) |
| Run | Docker (host ไม่ต้องลง PHP) |

## ติดตั้ง / รัน (ครั้งแรก)

ต้องมีแค่ **Docker** กับ **Node.js**

```bash
# 1. ติดตั้ง dependencies (ผ่าน docker — ไม่แตะ PHP เครื่อง)
docker run --rm -u $(id -u):$(id -g) -e COMPOSER_HOME=/tmp -v "$PWD":/app -w /app composer:2 install

# 2. สร้าง key + ฐานข้อมูล
cp .env.example .env   # ถ้ายังไม่มี .env
./art.sh artisan key:generate
touch database/database.sqlite
./art.sh artisan migrate --seed

# 3. build หน้าเว็บ (Tailwind)
npm install && npm run build

# 4. รัน
docker compose up -d
```

เปิด <http://localhost:8000>

> `art.sh` คือตัวช่วยรัน `php artisan` ผ่าน docker (เครื่องไม่ต้องมี PHP)

## บัญชีเริ่มต้น (จาก seed)

| บทบาท | เข้าที่ | username | password |
|-------|--------|----------|----------|
| แอดมิน | `/admin/login` | `admin@example.com` | `password` |
| นักศึกษา | `/student/login` | รหัสนักศึกษา เช่น `670012345` | วันเกิด เช่น `2005-03-15` |

## การใช้งาน

**แอดมิน** (`/admin`)
- **แดชบอร์ด**: สถิติการ์ด + กราฟการส่งงาน 7 วัน + โดนัทสถานะการตรวจ + งานล่าสุด
- **ตรวจ/ให้คะแนน**: เลือกวิชา+ห้อง → ตารางนักศึกษา×งาน → เปิดลิงก์ → กรอกคะแนน → Export CSV
- **สถานะการส่ง**: เลือกวิชา+ชิ้นงาน+ห้อง → ดูว่าใครส่งแล้ว/ยังไม่ส่ง พร้อมสรุปจำนวน
- **ศูนย์รายงาน**: สรุปทุกวิชา + สถิติคะแนนรายชิ้นงาน (เฉลี่ย/สูงสุด/ต่ำสุด) + Export CSV รวมศูนย์
- **รายวิชา / ห้องเรียน / นักศึกษา**: จัดการข้อมูล + นำเข้านักศึกษา CSV + ลงทะเบียน (ทีละคน/ทั้งห้อง)
- **อาจารย์/ผู้ดูแล**: สร้าง-แก้-ลบบัญชี admin ได้ในระบบ (กันลบตัวเอง)

**นักศึกษา** (`/student`)
- **หน้าหลัก**: เน้นงานที่ยังไม่ส่ง แยกตามวิชา
- **วิชาของฉัน**: ทุกวิชาที่ลงทะเบียน + แถบความคืบหน้าการส่ง
- **ประวัติการส่ง**: รายการที่เคยส่ง + ลิงก์ + เวลา
- ส่งงานด้วยการวาง **ลิงก์เท่านั้น** (แก้ลิงก์ได้จนกว่าจะถูกให้คะแนน)
- **ดูคะแนนตัวเองไม่ได้** (เห็นแค่สถานะ "ส่งแล้ว")

## รูปแบบไฟล์ CSV นำเข้านักศึกษา

หัวคอลัมน์: `student_code,full_name,birthdate,room`
วันเกิดรับได้ทั้ง `YYYY-MM-DD` และ `DD/MM/YYYY` — ตัวอย่างที่ `docs/students_template.csv`

```csv
student_code,full_name,birthdate,room
670012345,สมชาย ใจดี,2005-03-15,ปวส.1/1
670012346,สมหญิง รักเรียน,22/07/2005,ปวส.1/1
```

ถ้าชื่อห้องยังไม่มีจะถูกสร้างให้อัตโนมัติ และรหัสนักศึกษาซ้ำจะเป็นการอัปเดตข้อมูลเดิม

## ทดสอบ

```bash
./art.sh vendor/bin/pest
```

## หมายเหตุการ deploy

- SQLite พอสำหรับ 200 คน (โหลดต่ำ เป็นการแนบลิงก์) — backup = คัดลอกไฟล์ `database/database.sqlite`
- ต้องการ throughput สูงขึ้น: เปลี่ยน image เป็น `serversideup/php:8.4-fpm-nginx` (nginx+php-fpm) หรือย้ายไป MySQL ด้วยการแก้ `.env`
