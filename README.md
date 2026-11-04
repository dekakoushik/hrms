# HRMS Enterprise Suite

A modern, full-stack Human Resource Management System built with **Next.js 16**, **Laravel 11**, **Tailwind CSS**, and **Redis**.

---

## 🌟 Key Features

- **Executive & Administrative Command Center**: Real-time workforce metrics, live attendance stream, 7-day trends, and department distributions.
- **Role-Based Workspaces**: Tailored self-service dashboards for standard employees (`role: employee`) and administrative suites for HR/Superadmins.
- **Geofenced Biometric & Mobile Attendance Clock**: GPS radius verification, randomized on-field punch support, selfie field audit, and regularization workflows.
- **Automated Payroll Engine**: Month-to-date calculation with automated Sunday/Holiday accrual, 5-day continuous late grace window, half-day penalty rules, and itemized PDF/HTML payslip generation.
- **Official Notice Board & Circulars**: Priority-based announcement system (`Normal`, `Important`, `Urgent`) with pinned directives and automated in-app notifications.
- **Enterprise IT Helpdesk**: Role-scoped ticketing system with status lifecycles, priority tags, and conversation threads.
- **Statutory & Compliance Document Hub**: Folder-based policy center for HR handbooks, IT security guidelines, and compliance documentation.
- **Employee Directory**: Searchable contact lookup with Employee ID authentication support.

---

## 🚀 Deploying on Dokploy (or Any Docker Platform)

This repository includes a production-ready `docker-compose.yml` configured for multi-container deployment on Dokploy, Coolify, or self-hosted Docker servers.

### 1. In Dokploy:
1. Create a new **Application** or **Compose** service.
2. Select **Git Repository** and enter:
   ```
   https://github.com/dekakoushik/hrms
   ```
3. Set the Compose Path to `./docker-compose.yml`.
4. Configure environment variables (or let Docker Compose defaults bootstrap):
   - `APP_KEY`: Base64 application key (e.g., `base64:hcQNEUm3DWV70wEgoUKEfhpbFqYyZtB+zACq9MZHdRE=`)
   - `NEXT_PUBLIC_API_URL`: Your backend API domain or `http://backend:8000/api`
5. Click **Deploy**.

---

## 🛠 Local Setup (Docker Compose)

```bash
# 1. Clone repository
git clone https://github.com/dekakoushik/hrms.git
cd hrms

# 2. Start containers
docker compose up -d --build

# 3. Access applications:
# Frontend Portal: http://localhost:3000
# Backend API:     http://localhost:8000
```

---

## 🔐 Default Credentials

| Role | Username / ID | Password | Access Scope |
|---|---|---|---|
| **Superadmin** | `superadmin@hrms.local` | `SuperAdmin@12345` | Executive Console, Payroll Engine, Staff Onboarding, Settings |
| **Employee** | `EMP-2026-0001` or `koushikdeka300@gmail.com` | `Password@123` | Digital Punch Clock, My Payslips, IT Helpdesk, Notice Board |

---

## 📄 License
Proprietary corporate enterprise software.
