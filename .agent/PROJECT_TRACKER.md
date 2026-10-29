# HRMS Project Tracker & Module Master Record

**Project Name:** Enterprise Human Resource Management System (HRMS)  
**Region:** India (IST / UTC+5:30, Currency INR ₹, Indian Mobile Format +91 XXXXXXXXXX)  
**Theme:** Professional, Clean & Corporate Light Theme (Not Dark Theme)  
**Maintainer / Agent Continuity:** Managed via `.agent/` folder for continuous incremental upgrades.

---

## 1. System Architecture Overview

| Component | Technology | Directory | Status |
| :--- | :--- | :--- | :--- |
| **Backend API & Web Admin** | Laravel 11/13 (PHP 8.3), Laravel Sanctum, SQLite / MySQL | `/backend` | ✅ Active & Tested (Modules 1, 2, 3 Complete) |
| **Mobile App (Android & iOS)** | React Native (Expo SDK 57, Expo Go ready) | `/mobileapp` | ✅ Active & Tested (Modules 1, 2, 3 Complete) |
| **Documentation & State** | Architectural Specs, Schemas & Module Tracker | `/.agent` | ✅ Active |

---

## 2. Module Roadmap & Progress

- [x] **Setup & Tooling Baseline**:
  - PHP 8.3 CLI + Composer 2.x configured in environment PATH
  - Node.js & npm / npx configured
  - `.agent/` tracking & architecture documentation established

- [x] **Module 1: Superadmin Authentication & Profile Management** *(COMPLETED)*
  - Dual-identifier login (10-digit Indian Mobile `+91` OR Email + Password)
  - Sanctum token authentication for Mobile & API + Session/CSRF for Web
  - Responsive Web Portal with clean corporate light theme
  - Profile Upgrade under Settings page dropdown
  - React Native Expo app runnable in Expo Go

- [x] **Module 2: Settings Module (Shifts & SMTP)** *(COMPLETED)*
  - **Shift Manager** (`/settings/shifts`): Day/Night shifts, grace period, full/half day hours
  - **SMTP Mail Configuration** (`/settings/smtp`): Outbound mail gateway credentials & test mail dispatch
  - Dedicated API endpoints for Mobile app sync

- [x] **Module 3: Employee Onboarding & Directory** *(COMPLETED)*
  - **User Type Selection**: `admin` or `user`
  - **Searchable Dropdowns**:
    - Department (with inline quick-add modal)
    - Designation (with inline quick-add modal)
    - Manager selection from employee database
  - **Section 1: Personal Details**:
    - Full Name, DOB, Gender, Marital Status, Blood Group, Father's/Husband's Name
    - Personal Email, Official Email, Contact Number (+91), Alternate Number
    - Present Address (Street, City, State, PIN)
    - Permanent Address with 1-click "Same as Present Address" copy
  - **Section 2: Primary Emergency Contact**:
    - Contact Name, Relationship, Phone (+91), Residential Address
  - **Section 3: Statutory & Identity Details (India)**:
    - PAN Number + Document Upload
    - Aadhaar Number + Document Upload
    - EPFO UAN Number (if available)
    - ESIC IP Number (if available)
  - **Section 4: Bank Account Details (Salary Credit)**:
    - Account Holder Name, Bank Name, Branch, Account Number, IFSC Code, Account Type
    - Cancelled Cheque / Front Page of Passbook upload
  - **Section 5: Educational Qualifications**:
    - Dynamic qualification table (Degree, Institute, Year of Passing, CGPA/Score)
  - **Section 6: Salary Part (Indian Statutory Real-Time Engine)**:
    - Basic Salary (user input)
    - DA: 20% of Basic
    - HRA: 60% of Basic
    - CA: 20% of Basic
    - EA: 8% of Basic
    - SA: 20% of Basic
    - Gross Salary: Basic + DA + HRA + CA + EA + SA
    - Gratuity: (Basic + DA) * 4.81%
    - PF: (Basic + DA) * 12% capped at max ₹3,000 (with toggle enable/disable)
    - ESIC Employer: Gross * 3.25% (if Gross <= ₹21,000)
    - ESIC Employee: Gross * 0.75% (if Gross <= ₹21,000)
    - Total Deductions: PF + Employee ESIC
    - Cost to Company (CTC): Gross + Gratuity + PF + Employer ESIC
    - Tax (TDS input)
    - **Take-Home Pay**: Gross - Total Deductions - Tax
  - **Automated Credentials Dispatch**:
    - Auto-generates unique Employee ID (e.g. `EMP-0001`) and temporary secure password
    - Sends Welcome Email via configured Mail gateway (`EmployeeOnboardedMail`)
  - **Password Governance**:
    - Admin/Superadmin can reset password directly from Employee Directory
    - User can reset password from own profile
  - **Multi-Company & Legal Entity Master**:
    - Default seeded entities: **Riangdo Venners Pvt. Ltd.** & **North East Veneers & Plywood LLP**.
    - Full Organizations Master module (`/companies`) with unit statistics and add company modal.
    - Global Company Selector dropdown in top navigation bar (`All Companies (Combined)` or specific company filter) with session persistence across all modules.
    - Employee Onboarding Company dropdown with instant inline `+ Add Company` AJAX modal.
    - Company-level filtering in Employee Directory and Dashboard metrics.
    - Mobile app company filter tabs and company badge integration.
  - **Mobile & Email Uniqueness Governance**:
    - Pre-normalizes Indian 10-digit mobile numbers prior to validation.
    - Prevents raw database integrity errors by validating uniqueness against both `users` and `employees` tables with friendly user alerts.
  - **Testing**: 100% test pass rate across 24 PHPUnit feature tests (80 assertions)
  - **Probation & Employment Lifecycle Governance**:
    - Automatic probation date calculation on onboarding (default 6 months, configurable 1-24 months).
    - Real-time days remaining countdown badge on employee profile.
    - Admin/Superadmin can update employment type (`Probation`, `Permanent`, `Contract`, `Trainee`, `Intern`) and record confirmation dates.
    - 1-Click Active / Inactive status toggle: immediately suspends or re-activates portal login access (`users.is_active`).
    - Full profile editing form (`/employees/{employee}/edit`) covering all personal, statutory, emergency, address, and compensation parameters.
    - Built-in secure document viewer/streamer (`/employees/{employee}/document/{type}`) for attached PAN, Aadhaar, and Bank proof verification documents.
    - Superadmin-exclusive employee deletion (`DELETE /employees/{employee}`) with cascading account purge, token revocation, and strict 403 authorization guard for regular admins.
  - **Testing**: 100% test pass rate across 28 PHPUnit feature tests (96 assertions)

- [x] **Module 4: Attendance & Geo-tracking** *(COMPLETED)*
  - **Work Location & Geofence Manager** (`/settings/locations`):
    - Configurable multi-location master with Latitude, Longitude, and 200m radius geofence.
    - Haversine mathematical distance calculation.
    - Quick-add location modal integration.
  - **Location Type Assignment**:
    - **Fixed Office Employees**: Assigned to specific office location; punch in/out strictly restricted to within 200 meters.
    - **Random / Field Employees**: Outside office employees; no geofence restriction, punches captured with live device GPS.
    - Managed during onboarding (`/employees/create`) and editable anytime (`/employees/{id}/edit`).
  - **Daily Punch Governance**:
    - **Punch In**: Allowed strictly **once per day** per employee.
    - **Punch Out**: Allowed multiple times on the same day till 12:00 PM next day; tracks cumulative working hours and punch out counter.
  - **Shift & Grace Period Engine**:
    - Compares arrival time against assigned shift start time.
    - Up to **5 days grace period per month** permitted.
    - Late arrival past the grace period window, or once the monthly 5-day quota is exhausted, automatically marks attendance status as **Half Day**.
  - **Interactive Dashboard & Timesheets**:
    - Live Punch Station on Dashboard (`/dashboard`) with real-time browser GPS detection.
    - Dedicated Attendance Records page (`/attendance`) with date, status, search, and grace period counters.
  - **Testing**: 100% test pass rate across 36 PHPUnit feature tests (128 assertions).

- [x] **Module 5: Leave Management & Notification Centre** *(COMPLETED)*
  - **Leave Policy Master & Balances**:
    - Seeded standard Indian leave types: **Casual Leave (CL)**, **Sick / Medical Leave (SL)**, and **Earned / Privilege Leave (EL)**.
    - Global annual quotas configurable via Superadmin/Admin modal with 1-click employee balance synchronization.
    - Real-time remaining and pending leave quotas per employee per calendar year.
  - **Leave Application & Approval Workflow**:
    - Employee portal leave application with start/end date pickers, half-day toggles (First/Second half), and quota validation.
    - Superadmin / Admin pending approvals queue with 1-click **Approve** and **Reject** (with mandatory reason log).
    - Automatic deduction from remaining leave balance on approval.
  - **Live In-App Notification Centre**:
    - Real-time activity alerts for late arrivals (with late minutes), early exits, leave submissions, approvals, and regularizations.
    - Interactive bell icon dropdown in top navbar with unread count badge and AJAX background polling.
    - Dedicated alerts audit feed page (`/notifications`) with mark-as-read controls.
  - **Testing**: 100% test pass rate across 5 dedicated PHPUnit feature tests.

- [x] **Module 6: Payroll & Indian Statutory Compliance** *(COMPLETED)*
  - **Salary Cycle**: Strictly 1st of every month to the last calendar day of the month (`01 M Y - t M Y`).
  - **Leave Salary Deduction Rule**:
    - Deduction formula: `(TakeHome / 30) × (FullDays + (HalfDays × 0.5))`.
    - Full day leave: 1.0 deductible day.
    - Half day leave / attendance: 0.5 deductible day.
    - Daily rate: `TakeHome / 30`.
    - Net Salary: `TakeHome - LeaveDeduction`.
  - **Batch Payroll Runs & Digital Payslips**:
    - Monthly batch processing engine (`/payroll/process`) for active staff.
    - Batch register (`/payroll/runs/{id}`) showing total gross, statutory (PF/ESIC/Tax) deductions, leave deductions, and net payout.
    - 1-Click "Mark Batch as Paid" disbursement workflow.
    - Official printable Payslips (`/payroll/payslips/{id}`) formatted for Indian corporate standards.
  - **Testing**: 100% test pass rate across 3 dedicated PHPUnit feature tests.

- [x] **Module 7: Role-Based Access Control (RBAC) & Exit / Resignation Management** *(COMPLETED)*
  - **Comprehensive RBAC Differentiation**:
    - **Superadmin / Admin Access**: Full control over Organizations, Employees onboarding/editing/deleting, Shifts, SMTP Mail, Geofence Locations, Global Leave Policies, Leave Approvals, Batch Payroll Runs, and Resignation decisions.
    - **Regular User / Employee (`role === 'user'`) Experience**:
      - **Dashboard Personal Punch Station**: Live interactive punch in/out with geolocation check, grace day quota display, and punch logs.
      - **Dashboard Monthly Attendance Statistics**: 4 dedicated cards showing **Present Days**, **Half Days**, **Approved Leaves**, and **Late Arrivals** (with 5-day grace quota reminder).
      - **Confidential Payslips**: Employee dashboard displays recent payslips list with download links. Access is strictly guarded so employees can view and download **only their own payslips** (403 forbidden on other records).
      - **Leave Self-Service**: Direct modal on dashboard and Leave Manager to apply for leaves and view personal leave quotas/balances.
      - **Profile Access**: Direct link to view and edit personal profile and change password.
  - **Employee Exit & Resignation Management (`/resignations`)**:
    - **Employee Exit Submission**: Employee can submit resignation specifying **Reason**, **Requested Last Working Day (LWD)**, and **Notice Period Days** (default 30 days). Triggers automatic admin alert in the Notification Centre.
    - **Admin Decision Workflow (`/resignations/{id}/action`)**:
      - **Approve**: Confirm resignation and set official Last Working Day (LWD). Updates employee profile with `exit_date` and `exit_reason`, and notifies employee.
      - **Extend / Adjust Notice Period**: Admin can extend or customize notice period days and revise official Last Working Day. Updates employee's `exit_date` and dispatches notice update alert.
      - **Reject**: Admin can reject resignation with mandatory remarks and handover feedback.
    - **Dashboard Quick Exit Status Widget**: Shows real-time separation review status (Pending Review, Approved with LWD countdown, or Rejected).
  - **Testing**: 100% test pass rate across 49 PHPUnit feature tests (207 assertions).

---

## 3. How to Run

### Web Backend:
```bash
cd backend
php artisan serve --host=0.0.0.0 --port=8000
# Access in browser: http://127.0.0.1:8000/login
```

### Expo Mobile App:
```bash
cd mobileapp
npx expo start
# Scan the QR code in Expo Go app on Android or iOS
```
