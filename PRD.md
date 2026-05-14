# Medical Outreach Management System — Product Requirements Document

> **For Cursor / AI coding assistants:** This document is the source of truth for building the system. Read it fully before writing any code. Section 14 ("Build Order") tells you what to build first. Do not invent features that are not in this PRD. When unclear, prefer the simpler implementation and flag the ambiguity in a comment.

---

## 1. Project Overview

### 1.1 What we are building
A web-based, tablet-friendly system to manage the end-to-end journey of beneficiaries through a medical outreach event — from pre-registration to final counselling. Multiple medical stations (Check-in, Vitals, General Doctor, Lab, Pharmacy, Eye Clinic, Dental Clinic, Counselling) operate in parallel, each with their own role-scoped interface.

### 1.2 Why it exists
Medical outreaches currently rely on paper forms and manual handoffs, which slow throughput, lose data, and make post-event reporting nearly impossible. This system replaces paper with a fast, station-based digital workflow and produces clean data for donor-facing reports.

### 1.3 Operating environment
- **On-site server:** A mini-PC running the application at the outreach venue.
- **Local network:** A travel Wi-Fi router creates a private LAN. Tablets connect to it.
- **No internet required during the outreach.** The server *is* the network.
- **Post-outreach:** Database is backed up and pushed to a cloud copy where reporting dashboards run.

### 1.4 Tech stack (non-negotiable)
- **Backend:** Laravel 11 (PHP 8.3+)
- **Database:** MySQL 8.x
- **Frontend (stations):** Livewire 3 + Alpine.js + Tailwind CSS
- **Admin / Reports:** Filament 3
- **Auth:** Laravel Breeze (Livewire stack)
- **Roles & Permissions:** spatie/laravel-permission
- **Imports:** maatwebsite/excel
- **QR codes:** simplesoftwareio/simple-qrcode
- **Server OS:** Ubuntu Server 24.04
- **Web server:** Nginx + PHP-FPM

### 1.5 What this system is NOT
- Not an Electronic Health Record (EHR). It records what happens at one outreach event, not a longitudinal medical history.
- Not a billing system. Services are free.
- Not multi-tenant. One organisation, one deployment per outreach (though a cloud copy aggregates across outreaches for reporting).
- Not offline-on-tablet. Tablets must be connected to the local Wi-Fi to function. Offline-on-tablet is explicitly out of scope.

---

## 2. Users & Roles

The system has seven user roles. Each role has a dedicated dashboard. Roles are mutually exclusive per user account (one user = one role), but a single human can have multiple accounts if needed (rare).

| Role | Purpose | Primary screen |
|---|---|---|
| `admin` | Setup, user management, outreach configuration, reports | Filament admin panel |
| `check_in` | Register walk-ins, import pre-registrations, issue codes | Check-in station |
| `nurse` | Take vitals, select interventions per beneficiary | Vitals station |
| `doctor` | General consultation, write Rx, order lab tests | Doctor station |
| `lab` | Receive test orders, record results | Lab station |
| `pharmacist` | View prescriptions, mark drugs dispensed | Pharmacy station |
| `eye_care` | Eye examination and findings | Eye Clinic station |
| `dental_care` | Dental examination and treatment | Dental Clinic station |
| `counsellor` | Final counselling, prayer, missions referral | Counselling station |

> **Beneficiaries are NOT users.** They do not log in. They are records acted upon by staff.

### 2.1 Role permissions summary
- All station roles can **search and select any beneficiary** in the current outreach.
- All station roles can **read the full visit history** for the beneficiary they have selected (vitals, all prior consultations, lab results, prescriptions). This is essential — a pharmacist needs to see what the doctor prescribed; the doctor needs to see vitals.
- Only roles whose station owns a record can **write** to that record (e.g. only `lab` writes lab results; only `doctor` writes prescriptions and consultations).
- `admin` can do everything, including soft-deletes and corrections.

---

## 3. Core Concepts & Domain Model

Read this section carefully. Getting the model right is more important than any UI decision.

### 3.1 The three layers

**Beneficiary** — a person. One record per human, ever. Reusable across future outreaches. Contains identity, contact, and baseline medical info (allergies, existing conditions).

**Outreach** — an event (name, location, date range). Each outreach is independent.

**Visit** — one Beneficiary attending one Outreach. A beneficiary who comes back next year creates a new Visit, not a new Beneficiary. The Visit is the container for everything that happens that day: check-in, vitals, and all interventions.

### 3.2 Intervention — the unit of counting

An **Intervention** is one type of service delivered within a Visit. A single Visit can have multiple Interventions.

Three intervention types:
- `general_consultation` — see General Doctor (may branch to Lab and/or Pharmacy)
- `eye_care` — see Eye Clinic
- `dental_care` — see Dental Clinic

**Rule:** If a beneficiary sees the General Doctor *and* the Eye Clinic in one Visit, that is **2 Interventions**, not 1. Reporting counts Interventions, not Visits, when reporting on services delivered.

### 3.3 Intervention stages

Each Intervention has a `status` that drives the workflow. The valid statuses depend on the intervention type.

**For `general_consultation`:**
```
pending → in_consultation → awaiting_lab → consultation_review → awaiting_pharmacy → awaiting_counselling → completed
                          ↓ (if no lab needed)
                          awaiting_pharmacy
                          ↓ (if no Rx needed)
                          awaiting_counselling
```

**For `eye_care` and `dental_care`:**
```
pending → in_exam → awaiting_counselling → completed
                  ↓ (eye_care may also route to pharmacy if drops/Rx given)
                  awaiting_pharmacy → awaiting_counselling → completed
```

> **Implementation note:** Use a string enum column with a PHP enum class. Each station queries `where('status', $expectedStatus)` to populate its queue.

### 3.4 Visit-level vs Intervention-level data

| Data | Lives on | Why |
|---|---|---|
| Vitals (BP, pulse, BMI, etc.) | **Visit** | Taken once, referenced by all stations |
| HIV result, Blood glucose | **Visit** | Part of the focus tests at vitals stage |
| Consultation notes | **Intervention** (one per general_consultation) | Each consultation is its own clinical record |
| Lab order/result | **Intervention** (via consultation) | Tied to the specific consultation that ordered it |
| Prescription | **Intervention** | Tied to the specific consultation |
| Eye exam findings | **Intervention** (one per eye_care) | Specialty-specific |
| Dental exam findings | **Intervention** (one per dental_care) | Specialty-specific |
| Counselling session | **Visit** | Happens once at the end of the visit, not per intervention |

---

## 4. The Patient Journey (End-to-End Workflow)

This is the workflow the system must support. Build the stations to match these steps exactly.

### Step 1 — Pre-Registration (outside the system)
Beneficiaries fill a Google Form before the outreach. Admin exports as CSV/XLSX. Form columns are listed in Section 7.

### Step 2 — Check-in
Either:
- **Imported pre-registrant arrives:** Check-in staff searches by name/phone, confirms identity, generates a check-in code, creates a Visit record, prints/displays the slip.
- **Walk-in arrives:** Check-in staff registers them as a new Beneficiary on the spot, then creates the Visit and issues the slip.

A **check-in slip** contains:
- Beneficiary full name
- Check-in code (e.g. `MOA-0142`)
- QR code encoding the check-in code
- Outreach name and date

### Step 3 — Waiting Area
Beneficiary waits to be called for Vitals. No system action needed — they just hold their slip.

### Step 4 — Vitals (Nurse)
Nurse searches/scans the beneficiary, opens their Visit, records vitals (BP, pulse, temperature, weight, height, BMI auto-calculated, blood glucose, HIV status, notes), and **selects which interventions the beneficiary will receive** (one or more of: General Consultation, Eye Care, Dental Care). Saving creates the Intervention records and routes the beneficiary forward.

### Step 5 — Branching (based on selected interventions)

**5a. General Doctor**
Doctor searches/scans beneficiary, sees vitals, records complaint/observations/diagnosis, chooses next action:
- Order lab tests (specify which tests) → status becomes `awaiting_lab`
- Write prescription (no lab) → status becomes `awaiting_pharmacy`
- No drugs, no lab, send to counselling → status becomes `awaiting_counselling`
- Done → `completed`

**5b. Lab**
Lab searches/scans beneficiary, sees pending test orders, records results, saves. Status becomes `consultation_review` (routes back to Doctor).

**5c. Doctor review**
Doctor sees the beneficiary returned with lab results, reviews, writes prescription if needed, marks next step.

**5d. Pharmacy**
Pharmacist searches/scans beneficiary, sees prescription items, marks each item as available/unavailable and dispensed/not. Status becomes `awaiting_counselling`.

**5e. Eye Clinic (parallel path)**
Eye care staff searches/scans beneficiary, records visual acuity (L/R), findings, whether glasses are prescribed, any drops/medications. If drops are prescribed, intervention routes to Pharmacy. Otherwise routes to Counselling.

**5f. Dental Clinic (parallel path)**
Dental staff searches/scans beneficiary, records findings, treatment performed, any referrals. Routes to Counselling.

### Step 6 — Counselling / Missions / Prayer
Counsellor searches/scans beneficiary, records counselling type (wellness, prayer, missions referral), notes. Marks the Visit as `completed`.

### Step 7 — Completed
Beneficiary's Visit is closed. They keep their slip as proof of visit.

---

## 5. Beneficiary Lookup (Used by Every Station)

Every station has the same search component at the top of its screen.

### 5.1 Lookup methods (in priority order)
1. **Check-in code** — typed (e.g. `0142` — outreach prefix auto-prepended)
2. **QR scan** — tablet camera scans the slip
3. **Name (fuzzy)** — first/last name partial match
4. **Phone** — last 4 digits or full

### 5.2 Search component requirements
- Single search input at top of every station screen
- Auto-detects input type: 4-digit number = code; long digit string = phone; alpha = name
- QR scan button opens camera and decodes via `html5-qrcode` JavaScript library
- Results show: name, age, gender, check-in code, current intervention stages with badges
- Selecting a result loads the beneficiary into the station's main panel

### 5.3 Station-scoped queue (left rail)
In addition to free search, each station shows a **queue** of beneficiaries whose status matches that station:
- Vitals queue: visits with status `checked_in` and no vitals recorded yet
- Doctor queue: interventions with type `general_consultation` and status `pending` or `consultation_review`
- Lab queue: interventions with status `awaiting_lab`
- Pharmacy queue: interventions with status `awaiting_pharmacy`
- Eye queue: interventions with type `eye_care` and status `pending`
- Dental queue: interventions with type `dental_care` and status `pending`
- Counselling queue: visits with any intervention in status `awaiting_counselling` and no counselling session yet

Queues auto-refresh every 10 seconds (Livewire polling).

---

## 6. Database Schema

All tables use UUIDs as primary keys. Standard Laravel `timestamps` and `softDeletes` apply everywhere unless noted.

### 6.1 `users`
Standard Laravel users table + role assigned via spatie/laravel-permission.

### 6.2 `outreaches`
```
id (uuid, pk)
name (string)
location (string)
start_date (date)
end_date (date)
code_prefix (string, e.g. "MOA")           // used for generating check-in codes
status (enum: 'planned', 'active', 'closed')
notes (text, nullable)
created_at, updated_at
```
Only one outreach can have status `active` at a time. The "active" outreach is the default context for all stations.

### 6.3 `beneficiaries`
```
id (uuid, pk)
full_name (string)
gender (enum: 'male', 'female', 'other')
date_of_birth (date)
phone (string, indexed)
email (string, nullable)
residential_address (text)
existing_medical_conditions (text, nullable)
medication_status (enum: 'none', 'occasional', 'regular', nullable)
medication_list (text, nullable)
allergies (text, nullable)
emergency_contact_name (string, nullable)
emergency_contact_relationship (string, nullable)
emergency_contact_number (string, nullable)
medical_consent (boolean, default false)
communication_preference (enum: 'sms', 'whatsapp', 'email', 'phone_call', nullable)
source (enum: 'google_form_import', 'walk_in')
imported_at (timestamp, nullable)
created_by_user_id (uuid, fk users, nullable)
created_at, updated_at, deleted_at
```

### 6.4 `visits`
```
id (uuid, pk)
beneficiary_id (uuid, fk beneficiaries)
outreach_id (uuid, fk outreaches)
check_in_code (string, unique, e.g. "MOA-0142")
checked_in_at (timestamp)
checked_in_by_user_id (uuid, fk users)
current_stage (enum: 'checked_in', 'vitals_done', 'in_progress', 'counselling', 'completed')
status (enum: 'open', 'completed', 'no_show')
completed_at (timestamp, nullable)
created_at, updated_at, deleted_at
```
Unique compound index on `(beneficiary_id, outreach_id)` — a beneficiary visits an outreach once.

### 6.5 `vitals`
```
id (uuid, pk)
visit_id (uuid, fk visits, unique)        // one vitals record per visit
taken_by_user_id (uuid, fk users)
blood_pressure_systolic (integer, nullable)
blood_pressure_diastolic (integer, nullable)
pulse (integer, nullable)
temperature (decimal 4,1, nullable)
weight_kg (decimal 5,2, nullable)
height_cm (decimal 5,2, nullable)
bmi (decimal 4,1, nullable)               // auto-computed on save
blood_glucose (decimal 5,1, nullable)
hiv_status (enum: 'negative', 'positive', 'declined', 'not_tested', nullable)
notes (text, nullable)
taken_at (timestamp)
created_at, updated_at
```

### 6.6 `interventions`
```
id (uuid, pk)
visit_id (uuid, fk visits)
type (enum: 'general_consultation', 'eye_care', 'dental_care')
status (enum: see Section 3.3)
started_at (timestamp, nullable)
completed_at (timestamp, nullable)
created_at, updated_at, deleted_at
```

### 6.7 `consultations`
One per `general_consultation` intervention (may have multiple over time if doctor sees patient before and after lab — handle this by having one consultation record updated, not multiple).
```
id (uuid, pk)
intervention_id (uuid, fk interventions, unique)
doctor_user_id (uuid, fk users)
chief_complaint (text)
observations (text, nullable)
diagnosis (text, nullable)
next_action (enum: 'lab', 'pharmacy', 'counselling', 'done', nullable)
notes (text, nullable)
created_at, updated_at
```

### 6.8 `lab_orders`
```
id (uuid, pk)
consultation_id (uuid, fk consultations)
ordered_by_user_id (uuid, fk users)
status (enum: 'pending', 'completed', 'cancelled')
created_at, updated_at
```

### 6.9 `lab_order_items`
```
id (uuid, pk)
lab_order_id (uuid, fk lab_orders)
test_name (string)                         // free text initially; later: catalogue table
notes (text, nullable)
result (text, nullable)
result_recorded_by_user_id (uuid, fk users, nullable)
result_recorded_at (timestamp, nullable)
```

### 6.10 `prescriptions`
```
id (uuid, pk)
intervention_id (uuid, fk interventions)   // can be general OR eye_care (for drops)
prescribed_by_user_id (uuid, fk users)
notes (text, nullable)
created_at, updated_at
```

### 6.11 `prescription_items`
```
id (uuid, pk)
prescription_id (uuid, fk prescriptions)
drug_name (string)
dosage (string)                            // e.g. "500mg"
frequency (string)                         // e.g. "twice daily"
duration (string)                          // e.g. "5 days"
quantity (integer)
availability (enum: 'available', 'unavailable', 'partial', nullable)
dispensed_status (enum: 'pending', 'dispensed', 'declined_by_beneficiary', default 'pending')
dispensed_by_user_id (uuid, fk users, nullable)
dispensed_at (timestamp, nullable)
notes (text, nullable)
```

### 6.12 `eye_exams`
```
id (uuid, pk)
intervention_id (uuid, fk interventions, unique)
examined_by_user_id (uuid, fk users)
visual_acuity_left (string, nullable)      // e.g. "20/40"
visual_acuity_right (string, nullable)
findings (text, nullable)
glasses_prescribed (boolean, default false)
glasses_prescription_details (text, nullable)
drops_prescribed (boolean, default false)  // if true, create a prescription record
referral_needed (boolean, default false)
referral_notes (text, nullable)
notes (text, nullable)
created_at, updated_at
```

### 6.13 `dental_exams`
```
id (uuid, pk)
intervention_id (uuid, fk interventions, unique)
examined_by_user_id (uuid, fk users)
findings (text)
treatment_performed (text, nullable)
referral_needed (boolean, default false)
referral_notes (text, nullable)
notes (text, nullable)
created_at, updated_at
```

### 6.14 `counselling_sessions`
```
id (uuid, pk)
visit_id (uuid, fk visits, unique)
counsellor_user_id (uuid, fk users)
types (json)                               // array: ['wellness', 'prayer', 'missions']
notes (text, nullable)
created_at, updated_at
```

### 6.15 `imports`
Tracks each CSV/XLSX import for auditability.
```
id (uuid, pk)
outreach_id (uuid, fk outreaches)
imported_by_user_id (uuid, fk users)
filename (string)
total_rows (integer)
successful_rows (integer)
failed_rows (integer)
errors (json, nullable)
created_at
```

---

## 7. Google Form / Spreadsheet Import

### 7.1 Expected columns (from current Google Form)
1. Timestamp
2. Email address
3. Full Name
4. Gender
5. Date of Birth
6. Phone Number
7. Email Address
8. Residential Address
9. Existing medical conditions
10. Medication status
11. Medication list
12. Allergies
13. Emergency Contact Name
14. Relationship
15. Emergency Contact Number
16. Medical consent
17. Communication preference

### 7.2 Import behaviour
- Admin uploads CSV or XLSX via Filament.
- System maps columns by **header name** (case-insensitive, whitespace-trimmed). If a column is missing, show error before importing.
- For each row:
  - Match against existing beneficiaries by `phone` (primary) or `full_name + date_of_birth` (fallback).
  - If match found: update the beneficiary record (do not duplicate).
  - If no match: create new beneficiary with `source = 'google_form_import'`.
  - Do NOT auto-create a Visit. The Visit is created at check-in when the person arrives.
- Show import summary: X created, Y updated, Z failed (with reasons).
- Save the report to `imports` table.

### 7.3 Walk-in registration
Same fields as the form, entered manually by check-in staff. Phone and full name are required; everything else is optional for walk-ins (since they may be in a hurry — but the form should encourage completion).

---

## 8. Check-in Code Generation

### 8.1 Format
`{OUTREACH_PREFIX}-{4-digit sequence}` — e.g. `MOA-0142`.

### 8.2 Generation rules
- Sequence is per-outreach (resets to 0001 for each new outreach).
- Sequence is allocated atomically (use a DB transaction or `LOCK TABLES`) — never two visits with the same code.
- Codes are case-insensitive on lookup but stored uppercase.

### 8.3 QR code
The QR encodes only the check-in code (e.g. `MOA-0142`). No personal info in the QR — if a slip is lost, finding it doesn't expose data.

### 8.4 Slip rendering
Generate the slip as an HTML page sized for a small thermal printer (58mm or 80mm width). Contents:
- Organisation logo (uploaded in admin settings)
- Outreach name
- Date
- Beneficiary full name
- Check-in code in large text
- QR code (~150x150px)
- Footer: "Please keep this slip with you at all stations."

Provide a "Print" button. For Phase 1, browser-based printing is acceptable. Thermal printer integration is Phase 2.

---

## 9. Station UI Specifications

### 9.1 Common layout (all stations)
- **Top bar:** Logo, outreach name + date, current user name + role, logout
- **Search bar:** Below top bar, full width, with QR scan button on the right
- **Two-column body (tablet landscape):**
  - **Left rail (30%):** Station queue (auto-refresh every 10s). Each item shows name, code, time waiting. Tap to select.
  - **Main panel (70%):** Selected beneficiary's full context (vitals summary always visible at top) + station-specific form below.
- **Footer:** Sticky save button when editing.

### 9.2 Tablet-friendly rules
- Minimum tap target: 44x44px
- Form inputs: large, with clear labels above (not placeholders)
- Numeric fields use `inputmode="decimal"` or `inputmode="numeric"`
- Yes/No questions use big toggle buttons, not checkboxes
- Confirm-on-save for any action that advances a beneficiary's stage
- No hover-only interactions (touch has no hover)
- Test target: iPad 10" landscape (1080x810) and generic Android tablet 10" landscape

### 9.3 Per-station screens

#### 9.3.1 Check-in
- Two tabs: "Search Pre-Registered" and "Walk-in Registration"
- Search tab: search beneficiary by name/phone; if found, show their details; button "Check In" creates Visit, generates code, opens slip view.
- Walk-in tab: full registration form (Section 7.1 fields); submit creates Beneficiary + Visit + slip.
- After check-in, automatically show the printable slip.

#### 9.3.2 Vitals
- Queue: visits awaiting vitals.
- Main form fields: BP (sys/dia), pulse, temp, weight, height (BMI auto), blood glucose, HIV status (radio: Negative/Positive/Declined/Not Tested), notes.
- **Intervention selector:** Three large toggle cards: "General Doctor", "Eye Care", "Dental Care". One or more must be selected.
- Save creates Intervention rows in `pending` status, marks Visit `current_stage = vitals_done`.

#### 9.3.3 General Doctor
- Queue: pending general consultations, plus a tab for "Returned from Lab" (status `consultation_review`).
- Main panel: vitals summary at top, then consultation form (complaint, observations, diagnosis).
- Next-action selector: Lab / Pharmacy / Counselling / Done.
- If Lab selected: dynamic list to add test orders (test name + notes).
- If Pharmacy selected: dynamic list to add prescription items (drug, dosage, frequency, duration, quantity).
- Save advances intervention status accordingly.

#### 9.3.4 Lab
- Queue: interventions in `awaiting_lab`.
- Main panel: vitals summary + the consultation summary + list of ordered tests with inline result fields.
- Save records results and sets intervention status to `consultation_review`.

#### 9.3.5 Pharmacy
- Queue: interventions in `awaiting_pharmacy`.
- Main panel: vitals summary + prescription items in a table. Each row has availability toggle and dispensed toggle.
- Save records the dispense status and advances to `awaiting_counselling`.

#### 9.3.6 Eye Clinic
- Queue: eye_care interventions in `pending`.
- Main form: visual acuity L/R, findings, glasses prescribed (yes/no + details), drops prescribed (yes/no + Rx form), referral.
- If drops prescribed: status becomes `awaiting_pharmacy`. Else: `awaiting_counselling`.

#### 9.3.7 Dental Clinic
- Queue: dental_care interventions in `pending`.
- Main form: findings, treatment performed, referral.
- Status becomes `awaiting_counselling`.

#### 9.3.8 Counselling
- Queue: visits with any intervention in `awaiting_counselling` and no counselling session yet.
- Main form: multi-select for types (Wellness, Prayer, Missions), notes.
- Save records counselling session AND completes the visit if all interventions are also `completed` (or moves them to `completed`).

---

## 10. Admin Panel (Filament)

Built with Filament 3. Lives at `/admin`.

### 10.1 Resources
- **Outreaches** — CRUD; one can be marked active.
- **Beneficiaries** — list, search, view, edit, soft-delete. Show all visits for each.
- **Users** — invite users, assign roles, deactivate.
- **Visits** — read-only list with deep-link to full visit detail.
- **Imports** — list of past imports with row counts and error logs.

### 10.2 Settings page
- Organisation name & logo (for slips)
- Default code prefix
- Drug catalogue (Phase 2, see Section 13)
- Test catalogue (Phase 2)

### 10.3 Reports page (Filament widgets)
See Section 11.

---

## 11. Reporting Requirements

### 11.1 Stakeholder/donor dashboard
A Filament dashboard at `/admin/reports` showing, for a selected outreach (or "all outreaches"):

**Headline numbers:**
- Beneficiaries served (unique people)
- Total interventions delivered
- Drugs dispensed (count of `prescription_items` with `dispensed`)
- Lab tests completed

**Charts:**
- Interventions by type (pie chart: General / Eye / Dental)
- Beneficiaries by gender (bar)
- Beneficiaries by age band (bar: 0-12, 13-17, 18-30, 31-50, 51-65, 65+)
- Top 10 diagnoses (horizontal bar — derived from `consultations.diagnosis` field; Phase 2 can categorise these properly)
- Top 10 drugs dispensed (horizontal bar)
- HIV status breakdown (excluding "not tested" and "declined")
- BP risk band (Normal / Elevated / Stage 1 / Stage 2 / Crisis, computed from BP values)
- BMI band (Underweight / Normal / Overweight / Obese)

**Throughput:**
- Hourly check-in rate (line chart)
- Average wait time per station (Phase 2 — requires timestamp tracking at each handoff)

### 11.2 Exports
Every report and every list view must have an "Export to Excel" button (via Laravel Excel).

### 11.3 Data privacy in reports
Donor-facing reports must NEVER expose individual beneficiary names, phones, addresses, or diagnoses tied to identity. Aggregations only. A separate "Admin view" of the same dashboards can show identified data for the org's internal use.

---

## 12. Non-functional Requirements

### 12.1 Performance
- Station screens must load and respond in under 500ms on the local LAN.
- Search results must return in under 200ms for up to 10,000 beneficiaries.
- Queue polling at 10s intervals must not noticeably load the server (cache the queue counts).

### 12.2 Reliability
- Database must use InnoDB with foreign key constraints enforced.
- Every write that advances a stage must be wrapped in a transaction.
- Server should auto-restart on crash (use `supervisord` or `systemd`).
- Hourly `mysqldump` to a separate disk during active outreaches (cron job).

### 12.3 Security
- All HTTP, no HTTPS, is acceptable on the LAN (no certificates). For the cloud copy, HTTPS is mandatory.
- Login uses email + password with bcrypt (Laravel default).
- Sessions: 8-hour expiry (long enough for a full outreach day).
- Role-based authorisation enforced via Laravel policies AND Filament role guards.
- Audit log: every write to consultations, prescriptions, lab results stores `created_by` / `updated_by` user IDs.

### 12.4 Browser support
- Chrome 110+ on Android (primary)
- Safari 16+ on iPadOS
- Chrome 110+ on Windows/Mac (for admin and check-in stations)

---

## 13. Phase 2 / Out of Scope (Do Not Build Yet)

Cursor: do not build any of these without explicit instruction.

- Offline-on-tablet support (PWA + IndexedDB sync)
- Cloud sync / multi-outreach aggregation server (manual `mysqldump` is enough for Phase 1)
- SMS/WhatsApp notifications to beneficiaries
- Drug catalogue with stock levels (Phase 1 uses free-text drug names)
- Test catalogue with reference ranges
- Multi-language UI (English only for Phase 1)
- Patient photo capture
- Wait-time analytics dashboard
- Thermal printer driver integration (Phase 1 uses browser print)
- Mobile app (Phase 1 is web only)
- Two-factor auth

---

## 14. Build Order

Cursor: build in this order. Do not skip ahead. Confirm each phase works before moving on.

### Phase 1A — Foundations (target: end of week 1)
1. Fresh Laravel 11 project with Breeze (Livewire stack), Tailwind, Filament 3
2. Install: spatie/laravel-permission, maatwebsite/excel, simplesoftwareio/simple-qrcode
3. Configure MySQL connection
4. Migrations for all tables in Section 6
5. Eloquent models with all relationships
6. Seeders: one outreach, one user per role, 20 fake beneficiaries
7. Filament admin: User, Outreach, Beneficiary resources with full CRUD
8. Google Form import (Filament action on Outreach resource)

**Done when:** You can log in as admin, create an outreach, import a CSV of beneficiaries, and see them in a list.

### Phase 1B — Core station flow (target: end of week 2)
9. Auth-aware role routing (each role lands on their station after login)
10. Common beneficiary search/scan component
11. Check-in station (search pre-registered + walk-in registration + slip generation)
12. Vitals station (full form + intervention selector)
13. General Doctor station (consultation + Rx + lab order)

**Done when:** A fake beneficiary can move from check-in through vitals to a doctor's consultation with a prescription written.

### Phase 1C — Remaining stations (target: end of week 3)
14. Lab station
15. Pharmacy station
16. Eye Clinic station
17. Dental Clinic station
18. Counselling station

**Done when:** Full journey works end-to-end for all three intervention types.

### Phase 1D — Reporting + polish (target: end of week 4)
19. Filament dashboard with all charts in Section 11.1
20. Excel exports for every list and report
21. Tablet UI polish (tap targets, font sizes, queue auto-refresh)
22. Backup cron job script
23. Deployment guide for the mini-PC

### Phase 1E — Dry run + buffer (weeks 5-6)
24. Simulated outreach with 20 fake beneficiaries and real staff
25. Fix issues found in dry run
26. Documentation for staff: 1-page guide per station

---

## 15. Coding Standards (For Cursor)

- Follow Laravel conventions (Eloquent over query builder; Form Requests for validation; Policies for authorisation).
- Use PHP 8.3 typed properties and enums for all `enum` columns.
- Livewire components: one component per station; subcomponents for forms.
- Tailwind only — no custom CSS unless absolutely needed for print stylesheets.
- All user-facing strings go through `__()` for future translation.
- Tests: at minimum, a feature test per station that walks a beneficiary through that station's full action. Use Pest if you set up testing.
- Use Laravel's `whenLoaded`/eager loading to prevent N+1 in queues.
- Soft-delete everything that holds clinical data. Never hard-delete in code.
- Never log PII (name, phone, address, diagnosis) — log IDs only.

### 15.1 Folder structure conventions
- Livewire station components: `app/Livewire/Stations/CheckIn.php`, `app/Livewire/Stations/Vitals.php`, etc.
- Filament resources: `app/Filament/Resources/`
- Enums: `app/Enums/InterventionType.php`, `app/Enums/InterventionStatus.php`, etc.
- Services for non-trivial logic: `app/Services/CheckInCodeGenerator.php`, `app/Services/StageRouter.php`

### 15.2 Naming
- Routes: `/stations/check-in`, `/stations/vitals`, `/stations/doctor`, etc.
- Database: snake_case for columns; plural for tables.
- Models: singular PascalCase.
- Variables: $beneficiary, $visit, $intervention — match the domain language.

---

## 16. Open Questions / Assumptions

These are unresolved and may need clarification before building:

1. **Drug dispense partial:** If a beneficiary is prescribed 30 tablets but the pharmacy only has 20, is that "partial" with a quantity given? PRD currently has `availability: partial` — confirm logic with stakeholder.
2. **Lab result format:** Free text only for Phase 1, or structured (numeric value + unit + reference range)? PRD assumes free text.
3. **Counselling completion:** Does counselling automatically complete the visit, or can a beneficiary be counselled and still need to return to another station? PRD assumes it's the last step.
4. **Repeat visits within one outreach:** If a beneficiary completes their visit and later returns the same day (e.g. forgot to mention something), do they get a new Visit or reopen the existing one? PRD currently assumes one Visit per beneficiary per outreach.

Cursor: when you encounter any of these in code, add a `// TODO(open-question)` comment and proceed with the assumption stated.

---

## 17. Glossary

- **Beneficiary** — a person receiving services. Not a system user.
- **Visit** — one beneficiary attending one outreach event.
- **Intervention** — one type of service within a visit (general / eye / dental).
- **Station** — a physical location at the outreach (and the corresponding screen).
- **Check-in code** — short identifier issued at arrival, used by all stations.
- **Slip** — printed paper given to beneficiary with their code and QR.
- **Queue** — list of beneficiaries waiting at a specific station.

---

*End of PRD.*
