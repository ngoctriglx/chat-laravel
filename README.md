# Laravel Chat Application

A personal project - a modern, real-time chat application backend built with Laravel 11, featuring group conversations, direct messaging, file sharing, message reactions, and presence indicators. This project provides a comprehensive RESTful API for chat functionality.

## 🚀 Features

### Core Chat Features
- **Real-time Messaging**: Instant message delivery using WebSockets
- **Group Conversations**: Create and manage group chats with multiple participants
- **Direct Messages**: One-on-one private conversations
- **Message Reactions**: React to messages with emojis
- **File Attachments**: Share files up to 10MB per file
- **Message Search**: Search through conversation history
- **Typing Indicators**: See when someone is typing
- **Read Receipts**: Track message read status
- **Message Editing & Deletion**: Edit or delete your messages

### User Management
- **User Authentication**: Secure authentication with Laravel Sanctum
- **User Profiles**: Detailed user profiles with avatars and status messages
- **Friend System**: Send, accept, and manage friend requests
- **User Search**: Find and connect with other users
- **Online Presence**: Real-time online/offline status

### Technical Features
- **RESTful API**: Comprehensive API with proper authentication
- **WebSocket Broadcasting**: Real-time updates using Laravel Reverb
- **File Upload**: Secure file handling with image processing
- **Rate Limiting**: API protection against abuse
- **Pagination**: Efficient data loading for large datasets
- **Validation**: Comprehensive input validation and error handling

## 🛠️ Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Authentication**: Laravel Sanctum
- **Real-time**: Laravel Reverb (WebSockets)
- **Database**: MySQL/PostgreSQL/SQLite
- **Cache**: Redis
- **File Storage**: Local/Cloud storage
- **Image Processing**: Intervention Image
- **Frontend Assets**: Vite, Tailwind CSS (for asset compilation only)
- **Queue System**: Laravel Queues with Redis

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM (for asset compilation)
- MySQL/PostgreSQL/SQLite
- Redis (for caching and queues)
- WebSocket server (for real-time features)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd chat-laravel
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies (for asset compilation)**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your environment variables in `.env`**
   ```env
   APP_NAME="Laravel Chat"
   APP_ENV=local
   APP_KEY=your-generated-key
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=chat_laravel
   DB_USERNAME=root
   DB_PASSWORD=

   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379

   BROADCAST_DRIVER=reverb
   REVERB_APP_ID=your-reverb-app-id
   REVERB_APP_KEY=your-reverb-app-key
   REVERB_APP_SECRET=your-reverb-app-secret
   REVERB_HOST=127.0.0.1
   REVERB_PORT=8080
   REVERB_SCHEME=http

   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   CACHE_DRIVER=redis
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Start the development server**
   ```bash
   # Start all services (Laravel, Queue, WebSocket, Vite)
   composer run dev
   
   # Or start individually:
   php artisan serve
   php artisan queue:listen
   php artisan reverb:start
   npm run dev
   ```

## 📚 API Documentation

The application provides a comprehensive RESTful API for all chat functionality. Complete API documentation is available in:

**[📖 API Documentation](./docs/API_DOCUMENTATION.md)**

### Key API Endpoints

#### Authentication
- `POST /api/v1/auth/register` - User registration
- `POST /api/v1/auth/login` - User login
- `POST /api/v1/auth/send-code` - Send verification code
- `POST /api/v1/auth/verify-code` - Verify code

#### Conversations
- `GET /api/v1/chat/conversations` - List conversations
- `POST /api/v1/chat/conversations` - Create conversation
- `GET /api/v1/chat/conversations/{id}` - Get conversation details
- `PUT /api/v1/chat/conversations/{id}` - Update conversation
- `DELETE /api/v1/chat/conversations/{id}` - Delete conversation

#### Messages
- `GET /api/v1/chat/conversations/{id}/messages` - Get messages
- `POST /api/v1/chat/conversations/{id}/messages` - Send message
- `PUT /api/v1/chat/messages/{id}` - Update message
- `DELETE /api/v1/chat/messages/{id}` - Delete message
- `POST /api/v1/chat/conversations/{id}/messages/search` - Search messages

#### User Management
- `GET /api/v1/user/me` - Get current user
- `PATCH /api/v1/user/me` - Update user profile
- `GET /api/v1/user/search` - Search users
- `GET /api/v1/friends` - Get friends list
- `POST /api/v1/friends/requests/send` - Send friend request

#### Presence
- `POST /api/v1/chat/presence/online` - Set online status
- `POST /api/v1/chat/presence/offline` - Set offline status

## 📖 Documentation

For comprehensive documentation, visit the [Documentation Directory](./docs/):

- **[📋 Installation Guide](./docs/INSTALLATION.md)** - Detailed setup instructions
- **[🔧 Configuration Guide](./docs/CONFIGURATION.md)** - All configuration options
- **[📊 Database Schema](./docs/DATABASE_SCHEMA.md)** - Database structure and relationships
- **[🚀 Deployment Guide](./docs/DEPLOYMENT.md)** - Production deployment instructions
- **[📚 API Documentation](./docs/API_DOCUMENTATION.md)** - Complete API reference

## 🔧 Configuration

### Broadcasting Setup

For real-time features, configure your broadcasting driver in `.env`:

```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

### File Upload Configuration

Configure file upload settings in `config/filesystems.php`:

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
],
```

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

## 📦 Production Deployment

1. **Optimize for production**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Set up queue workers**
   ```bash
   php artisan queue:work --daemon
   ```

3. **Configure WebSocket server**
   ```bash
   php artisan reverb:start
   ```

4. **Set up supervisor for queue workers**
   ```bash
   # Add to /etc/supervisor/conf.d/laravel-worker.conf
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=8
   redirect_stderr=true
   stdout_logfile=/path/to/your/project/storage/logs/worker.log
   ```

## 🔒 Security Features

- **Authentication**: Laravel Sanctum for API authentication
- **Rate Limiting**: API endpoints are rate-limited
- **Input Validation**: Comprehensive validation rules
- **File Upload Security**: File type and size restrictions
- **SQL Injection Protection**: Eloquent ORM with prepared statements
- **XSS Protection**: Output escaping and sanitization

## 📊 Database Schema

The application uses the following main tables:

- `users` - User accounts and basic information
- `user_details` - Extended user profile information
- `conversations` - Chat conversations (direct and group)
- `conversation_participants` - Conversation membership
- `messages` - Chat messages
- `message_attachments` - File attachments
- `message_reactions` - Message reactions
- `friends` - Friend relationships
- `friend_requests` - Friend request management

## 🤝 Contributing

This is a personal project, but if you find any issues or have suggestions:

1. Create an issue in the repository
2. Fork the repository if you want to contribute
3. Create a feature branch (`git checkout -b feature/amazing-feature`)
4. Commit your changes (`git commit -m 'Add amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

## 📝 License

This is a personal project. The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

If you encounter any issues or have questions:

1. Check the [API Documentation](./docs/API_DOCUMENTATION.md)
2. Review the Laravel documentation
3. Create an issue in the repository

## 🔄 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and updates.

---

**Personal Project - Built with ❤️ using Laravel 11**
