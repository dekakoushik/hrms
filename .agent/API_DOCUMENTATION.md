# HRMS API Specification

Base URL: `http://<SERVER_IP>:8000/api`

---

## 1. Authentication Endpoints

### 1.1 Superadmin & Staff Login
- **Endpoint:** `POST /api/auth/login`
- **Headers:** `Content-Type: application/json`, `Accept: application/json`
- **Request Body:**
```json
{
  "login": "9876543210", // Or email "admin@hrms.in"
  "password": "Password@123",
  "device_name": "expo_mobile_app" // optional
}
```
- **Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "1|sanctum_plain_text_token...",
  "user": {
    "id": 1,
    "name": "Rajesh Sharma",
    "email": "admin@hrms.in",
    "mobile_number": "9876543210",
    "role": "superadmin",
    "designation": "Chief Human Resources Officer & Super Admin",
    "department": "Executive Leadership",
    "bio": "Enterprise System Administrator...",
    "address": "DLF Cyber City, Tower B, 14th Floor",
    "city": "Gurugram",
    "state": "Haryana",
    "pincode": "122002",
    "avatar_url": "https://ui-avatars.com/api/?name=Rajesh+Sharma...",
    "is_active": true
  }
}
```

### 1.2 Get Authenticated Profile
- **Endpoint:** `GET /api/auth/me`
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Response (200 OK):** Current user object.

### 1.3 Update Superadmin Profile
- **Endpoint:** `POST /api/auth/profile`
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Request Body:**
```json
{
  "name": "Rajesh Kumar Sharma",
  "email": "admin@hrms.in",
  "mobile_number": "9876543210",
  "designation": "Executive Director HR",
  "department": "Executive",
  "address": "Cyber Hub, DLF Phase 2",
  "city": "Gurugram",
  "state": "Haryana",
  "pincode": "122002",
  "bio": "Updated summary"
}
```
- **Response (200 OK):** Updated user object.

### 1.4 Change Password
- **Endpoint:** `POST /api/auth/change-password`
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Request Body:**
```json
{
  "current_password": "Password@123",
  "password": "NewPassword@456",
  "password_confirmation": "NewPassword@456"
}
```

### 1.5 Logout
- **Endpoint:** `POST /api/auth/logout`
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
