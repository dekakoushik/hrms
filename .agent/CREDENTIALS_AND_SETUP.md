# Default Credentials & Setup Guide

## 1. Default Superadmin Account

| Field | Value |
| :--- | :--- |
| **Email Address** | `admin@hrms.in` |
| **Indian Mobile Number** | `9876543210` (or `+91 9876543210`) |
| **Default Password** | `Password@123` |
| **Role** | `superadmin` |
| **Name** | Rajesh Sharma |
| **Designation** | Chief Human Resources Officer & Super Admin |
| **Location** | Gurugram, Haryana, India |

---

## 2. Access Points

- **Web Admin Portal:** [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login)
- **API Base URL:** `http://127.0.0.1:8000/api` (or `http://192.168.2.48:8000/api` on LAN)
- **Settings Dropdown Profile Path:** [http://127.0.0.1:8000/settings/profile](http://127.0.0.1:8000/settings/profile)
- **Mobile App Folder:** `/mobileapp`

---

## 3. Running the Projects

### Running Laravel Backend (Web + API)
The Laravel backend runs on port 8000:
```powershell
cd D:\HRMS\backend
php artisan serve --host=0.0.0.0 --port=8000
```

### Running Expo Mobile App (Expo Go on Android / iOS)
```powershell
cd D:\HRMS\mobileapp
npx expo start
```
Open **Expo Go** on your Android or iPhone device and scan the terminal QR code.
*(Ensure your phone is on the same local network `192.168.2.48`)*
