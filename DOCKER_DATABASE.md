# PostgreSQL & pgAdmin Docker Setup

This document provides comprehensive instructions for managing the PostgreSQL database and pgAdmin interface for the Accountify backend.

## 📦 Docker Services

The `docker-compose.yml` file includes two services:

### 1. PostgreSQL 16 Alpine
- **Container Name**: `accountify-postgres`
- **Image**: `postgres:16-alpine`
- **Port**: `5432`
- **Database**: `accountify`
- **Username**: `postgres`
- **Password**: `postgres`

### 2. pgAdmin 4
- **Container Name**: `accountify-pgadmin`
- **Image**: `dpage/pgadmin4:latest`
- **Port**: `5050`
- **Email**: `admin@accountify.com`
- **Password**: `admin`

## 🚀 Getting Started

### Start the Containers

```bash
# Navigate to backend directory
cd accountify-backend

# Start both PostgreSQL and pgAdmin
docker compose up -d
```

### Stop the Containers

```bash
# Stop containers (data is preserved)
docker compose down

# Stop and remove volumes (⚠️ deletes all data)
docker compose down -v
```

### Check Container Status

```bash
# View running containers
docker compose ps

# View all containers (including stopped)
docker ps -a
```

## 🌐 Accessing pgAdmin

### 1. Open pgAdmin in Browser

Navigate to: **http://localhost:5050**

### 2. Login to pgAdmin

- **Email**: `admin@accountify.com`
- **Password**: `admin`

### 3. Register PostgreSQL Server

After logging in to pgAdmin:

1. **Right-click** on "Servers" in the left sidebar
2. Select **"Register"** → **"Server"**
3. Fill in the connection details:

#### General Tab
- **Name**: `Accountify` (or any name you prefer)

#### Connection Tab
- **Host name/address**: `postgres` (container name) or `accountify-postgres`
- **Port**: `5432`
- **Maintenance database**: `accountify`
- **Username**: `postgres`
- **Password**: `postgres`

#### Advanced Tab (Optional)
- **Save password**: ✅ Check this to avoid re-entering password

4. Click **"Save"**

## 🔧 Common Operations

### View Container Logs

```bash
# PostgreSQL logs
docker logs accountify-postgres

# pgAdmin logs
docker logs accountify-pgadmin

# Follow logs in real-time
docker logs -f accountify-postgres
```

### Restart Containers

```bash
# Restart all services
docker compose restart

# Restart specific service
docker compose restart postgres
docker compose restart pgadmin
```

### Access PostgreSQL CLI

```bash
# Connect to PostgreSQL using psql
docker exec -it accountify-postgres psql -U postgres -d accountify

# Common psql commands:
# \l          - List all databases
# \dt         - List all tables
# \d table    - Describe table structure
# \q          - Quit psql
```

### Backup Database

```bash
# Create backup
docker exec accountify-postgres pg_dump -U postgres accountify > backup_$(date +%Y%m%d_%H%M%S).sql

# Or with custom format (recommended)
docker exec accountify-postgres pg_dump -U postgres -Fc accountify > backup.dump
```

### Restore Database

```bash
# From SQL file
docker exec -i accountify-postgres psql -U postgres -d accountify < backup.sql

# From custom format
docker exec -i accountify-postgres pg_restore -U postgres -d accountify backup.dump
```

## 🗄️ Data Persistence

Database data is stored in a Docker volume named `postgres_data`. This ensures:
- ✅ Data persists even when containers are stopped
- ✅ Data survives container recreation
- ⚠️ Data is deleted only when you run `docker compose down -v`

### View Volumes

```bash
# List all volumes
docker volume ls

# Inspect postgres volume
docker volume inspect accountify-backend_postgres_data
```

## 🔐 Security Notes

### For Development
The current credentials are suitable for local development:
- Simple passwords for easy access
- No SSL/TLS required
- Exposed ports on localhost

### For Production
**⚠️ IMPORTANT**: Before deploying to production:

1. **Change default passwords**:
   ```yaml
   POSTGRES_PASSWORD: use_strong_password_here
   PGADMIN_DEFAULT_PASSWORD: use_strong_password_here
   ```

2. **Use environment variables**:
   ```yaml
   POSTGRES_PASSWORD: ${DB_PASSWORD}
   ```

3. **Enable SSL/TLS** for PostgreSQL connections

4. **Restrict network access**:
   - Don't expose ports publicly
   - Use internal Docker networks only
   - Implement firewall rules

5. **Use secrets management** (Docker Swarm, Kubernetes, etc.)

## 🛠️ Troubleshooting

### pgAdmin Won't Start

**Check logs**:
```bash
docker logs accountify-pgadmin
```

**Common issues**:
- Email validation error → Use valid email format (e.g., `admin@accountify.com`)
- Port conflict → Change port in `docker-compose.yml`

### PostgreSQL Connection Failed

**Verify container is running**:
```bash
docker compose ps
```

**Test connection**:
```bash
docker exec accountify-postgres pg_isready -U postgres
```

**Check PostgreSQL logs**:
```bash
docker logs accountify-postgres
```

### Port Already in Use

If ports 5432 or 5050 are already in use:

**Option 1**: Stop conflicting service
```bash
# Windows
netstat -ano | findstr :5432
taskkill /PID <PID> /F

# Check what's using the port
Get-Process -Id (Get-NetTCPConnection -LocalPort 5432).OwningProcess
```

**Option 2**: Change port in `docker-compose.yml`
```yaml
ports:
  - "5433:5432"  # Use 5433 instead of 5432
```

### Reset Everything

```bash
# Stop and remove containers, networks, and volumes
docker compose down -v

# Remove any orphaned containers
docker container prune

# Start fresh
docker compose up -d
```

## 📊 Connecting from Laravel

The Laravel application connects to PostgreSQL using the `.env` configuration:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=accountify
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

**Note**: Use `127.0.0.1` (not `postgres`) when connecting from the host machine.

## 🔗 Useful Links

- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [pgAdmin Documentation](https://www.pgadmin.org/docs/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

## 📝 Quick Reference

| Action | Command |
|--------|---------|
| Start services | `docker compose up -d` |
| Stop services | `docker compose down` |
| View logs | `docker logs accountify-postgres` |
| Access psql | `docker exec -it accountify-postgres psql -U postgres -d accountify` |
| Restart | `docker compose restart` |
| Check status | `docker compose ps` |
| Backup DB | `docker exec accountify-postgres pg_dump -U postgres accountify > backup.sql` |

---

**Need help?** Check the troubleshooting section or review the Docker logs for error messages.

