# Dockploy & Docker Deployment Guide

This project is completely Dockerized and ready for direct one-click deployment in **Dokploy / Dockploy**, **Docker Compose**, or standalone Docker environments.

---

## 1. Quick Deploy with Docker Compose (Local / VPS)

To build and run the complete application:

```bash
docker compose up -d --build
```

Access the application in your browser:
- **Web Portal**: [http://localhost:8000](http://localhost:8000) (or your VPS IP on port 8000)
- **API Base**: `http://localhost:8000/api`

To stop:
```bash
docker compose down
```

---

## 2. Deploying in Dokploy (Dockploy)

Dokploy supports Git-based Docker Compose or Dockerfile deployments:

### Option A: Compose Deployment (Recommended)
1. Go to your **Dokploy Dashboard** &rarr; **Projects** &rarr; **Create Service**.
2. Select **Compose**.
3. Point to your repository or paste the content of [`docker-compose.yml`](../docker-compose.yml).
4. Under **Environment Variables**, set:
   - `APP_KEY` (use your generated key or generate via `php artisan key:generate --show`)
   - `APP_URL` (`https://your-domain.com`)
   - `APP_ENV` (`production`)
   - `APP_DEBUG` (`false`)
   - `PORT` (`80` or let Dokploy route via Traefik proxy)
5. Click **Deploy**. Dokploy will pull, build, run migrations automatically, and attach persistent volumes (`hrms_database`, `hrms_storage`).

### Option B: Application (Dockerfile)
1. Create an **Application** service in Dokploy.
2. Select **GitHub / Git Provider** & select the repository.
3. Build Type: **Dockerfile**.
4. Context: `./backend`
5. Dockerfile Path: `./backend/Dockerfile`
6. Set Volumes for persistence:
   - `/var/www/html/database`
   - `/var/www/html/storage`
7. Port: `80`
8. Click **Deploy**.

---

## 3. Data Persistence
Persistent volumes ensure you never lose your SQLite database or uploaded documents/payslips/selfies:
- `hrms_database`: `/var/www/html/database` (holds `database.sqlite`)
- `hrms_storage`: `/var/www/html/storage` (holds employee photos, PAN/Aadhaar documents, uploaded policies, and logs)

---

## 4. Default Superadmin Login Credentials

| Field | Value |
| :--- | :--- |
| **Email** | `admin@hrms.in` |
| **Indian Mobile Number** | `9876543210` |
| **Default Password** | `Password@123` |
| **Role** | `superadmin` |
