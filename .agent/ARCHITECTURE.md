# HRMS System Architecture & Standards

## 1. Design & UI Guidelines (Corporate Light Theme)
- **Palette**: Clean slate background (`#F8FAFC` to `#F1F5F9`), pure white cards (`#FFFFFF`), subtle slate borders (`#E2E8F0`), deep slate text (`#0F172A`), primary accent indigo (`#4F46E5` / `#4338CA`), secondary emerald (`#10B981`) for success/active states.
- **Dark Mode**: Explicitly disabled as requested. The UI must remain a luminous, professional light enterprise aesthetic.
- **Responsiveness**: Mobile-first design for web (320px up to 4K ultra-wide), collapsible sidebar / responsive topbar with mobile drawer.

## 2. Authentication Protocol
- **Identifiers**: The user may provide either an Email (`user@company.com`) or a 10-digit Indian Mobile Number (`9876543210` or `+919876543210`).
- **Sanctum API**:
  - `POST /api/auth/login` -> returns `{ token, user: { id, name, email, mobile_number, role, avatar_url, designation, ... } }`
  - `GET /api/auth/me` (Bearer token header) -> returns current authenticated user profile
  - `POST /api/auth/profile` -> updates profile fields & optional avatar upload
  - `POST /api/auth/change-password` -> verifies `current_password` and updates `new_password`
  - `POST /api/auth/logout` -> revokes current access token

## 3. Database Schema (Module 1 - Users & Superadmin)
- Table: `users`
  - `id`: bigint unsigned primary key
  - `name`: string
  - `email`: string, unique
  - `mobile_number`: string(20), unique, indexed
  - `password`: string (hashed)
  - `role`: enum ('superadmin', 'admin', 'hr', 'manager', 'employee'), default 'employee'
  - `designation`: string nullable
  - `department`: string nullable
  - `avatar`: string nullable (path to uploaded image or default avatar URL)
  - `address`: string nullable
  - `city`: string nullable
  - `state`: string nullable
  - `pincode`: string(10) nullable
  - `is_active`: boolean default true
  - `remember_token`: string nullable
  - `timestamps`

## 4. Mobile Application Standards (Expo Go / React Native)
- Standard Expo managed workflow compatible with Expo Go on Android & iOS.
- Clean typography and native light styling matching the web interface.
- Secure storage via AsyncStorage for bearer tokens.
- Cross-platform safe area handling (`react-native-safe-area-context`).
