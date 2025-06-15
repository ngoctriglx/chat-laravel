# Installation Guide

This guide will walk you through setting up the Laravel Chat Application on your local development environment.

## 📋 Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP 8.2 or higher**
- **Composer** (PHP package manager)
- **Node.js & NPM** (for asset compilation)
- **MySQL/PostgreSQL/SQLite** (database)
- **Redis** (for caching and queues)
- **WebSocket server** (for real-time features)

### System Requirements

- **Operating System**: Windows, macOS, or Linux
- **Memory**: Minimum 2GB RAM (4GB recommended)
- **Storage**: At least 1GB free space
- **Network**: Internet connection for package downloads

## 🚀 Step-by-Step Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd chat-laravel
```

### 2. Install PHP Dependencies

```bash
composer install
```

This will install all required PHP packages including:
- Laravel Framework
- Laravel Sanctum (authentication)
- Laravel Reverb (WebSockets)
- Intervention Image (image processing)
- Predis (Redis client)

### 3. Install Node.js Dependencies

```bash
npm install
```

**Note**: Node.js dependencies are only used for asset compilation (Vite, Tailwind CSS). No frontend is included in this project.

### 4. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure Environment Variables

Edit the `.env` file with your specific configuration:

```env
# Application
APP_NAME="Laravel Chat"
APP_ENV=local
APP_KEY=your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_laravel
DB_USERNAME=root
DB_PASSWORD=

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Broadcasting (WebSockets)
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-reverb-app-id
REVERB_APP_KEY=your-reverb-app-key
REVERB_APP_SECRET=your-reverb-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Queue and Cache
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# File Storage
FILESYSTEM_DISK=public
```

### 6. Database Setup

#### Create Database
```bash
# MySQL
mysql -u root -p
CREATE DATABASE chat_laravel;
exit;

# PostgreSQL
createdb chat_laravel

# SQLite (automatic)
# No additional setup required
```

#### Run Migrations
```bash
php artisan migrate
```

This will create all necessary database tables:
- `users` - User accounts
- `user_details` - Extended user profiles
- `conversations` - Chat conversations
- `conversation_participants` - Conversation membership
- `messages` - Chat messages
- `message_attachments` - File attachments
- `message_reactions` - Message reactions
- `friends` - Friend relationships
- `friend_requests` - Friend requests

### 7. Create Storage Link

```bash
php artisan storage:link
```

This creates a symbolic link for file uploads.

### 8. Start Development Servers

#### Option A: Start All Services (Recommended)
```bash
composer run dev
```

This command starts:
- Laravel development server
- Queue worker
- WebSocket server (Reverb)
- Vite development server

#### Option B: Start Services Individually
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:listen

# Terminal 3: WebSocket server
php artisan reverb:start

# Terminal 4: Asset compilation
npm run dev
```

## 🔧 Verification

### Check API Health
```bash
curl http://localhost:8000/api/health
```

Expected response:
```json
{
  "status": "healthy",
  "timestamp": "2024-01-01T00:00:00.000000Z",
  "version": "1.0.0"
}
```

### Test WebSocket Connection
The WebSocket server should be running on `ws://localhost:8080`

## 🐛 Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
```

#### 2. Redis Connection Issues
```bash
# Check Redis is running
redis-cli ping
# Should return: PONG
```

#### 3. Database Connection Issues
- Verify database credentials in `.env`
- Ensure database server is running
- Check database exists

#### 4. WebSocket Issues
- Ensure port 8080 is available
- Check firewall settings
- Verify Reverb configuration

### Getting Help

If you encounter issues:
1. Check the [Configuration Guide](./CONFIGURATION.md)
2. Review Laravel documentation
3. Create an issue in the repository

## 📦 Next Steps

After successful installation:

1. **Read the [API Documentation](./API_DOCUMENTATION.md)** to understand available endpoints
2. **Review the [Configuration Guide](./CONFIGURATION.md)** for advanced setup
3. **Check the [Database Schema](./DATABASE_SCHEMA.md)** to understand data structure
4. **Explore the [Deployment Guide](./DEPLOYMENT.md)** for production setup

---

**🎉 Congratulations! Your Laravel Chat Application is now running locally.** 