# Configuration Guide

This guide covers all configuration options for the Laravel Chat Application, including environment variables, broadcasting setup, file uploads, and advanced configurations.

## 🔧 Environment Configuration

### Basic Application Settings

```env
# Application
APP_NAME="Laravel Chat"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC
APP_LOCALE=en
```

### Database Configuration

#### MySQL
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chat_laravel
DB_USERNAME=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

#### PostgreSQL
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=chat_laravel
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

#### SQLite
```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite
```

### Redis Configuration

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### Broadcasting Configuration

#### Laravel Reverb (Recommended)
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

#### Pusher (Alternative)
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-pusher-app-id
PUSHER_APP_KEY=your-pusher-app-key
PUSHER_APP_SECRET=your-pusher-app-secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1
```

### Queue Configuration

```env
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database-uuids
```

### Cache Configuration

```env
CACHE_DRIVER=redis
CACHE_PREFIX=chat_laravel_
```

### Session Configuration

```env
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
```

## 📁 File Upload Configuration

### File System Settings

```env
FILESYSTEM_DISK=public
```

### File Upload Limits

Configure in `config/app.php`:

```php
'max_upload_size' => env('MAX_UPLOAD_SIZE', 10485760), // 10MB
'allowed_file_types' => [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'application/pdf',
    'text/plain',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
],
```

### Storage Configuration

Edit `config/filesystems.php`:

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
    
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'throw' => false,
    ],
],
```

## 🔐 Security Configuration

### Rate Limiting

Configure in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

### CORS Configuration

Edit `config/cors.php`:

```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Sanctum Configuration

Edit `config/sanctum.php`:

```php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),
    
    'guard' => ['web'],
    
    'expiration' => null,
    
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

## 🌐 WebSocket Configuration

### Laravel Reverb Setup

1. **Install Reverb** (already included in composer.json)
2. **Configure broadcasting** in `.env`
3. **Start Reverb server**:

```bash
php artisan reverb:start
```

### Broadcasting Channels

Configure in `routes/channels.php`:

```php
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()->where('conversation_id', $conversationId)->exists();
});
```

## 📧 Mail Configuration

### SMTP Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Mail Templates

Create mail templates in `resources/views/emails/`:

```php
// Example: User verification email
php artisan make:mail UserVerificationMail
```

## 🔍 Logging Configuration

### Log Channels

Edit `config/logging.php`:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single'],
        'ignore_exceptions' => false,
    ],
    
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
    ],
],
```

## 🧪 Testing Configuration

### PHPUnit Configuration

Edit `phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="BCRYPT_ROUNDS" value="4"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="TELESCOPE_ENABLED" value="false"/>
</php>
```

## 🚀 Performance Configuration

### Cache Configuration

```env
# Enable route caching in production
php artisan route:cache
php artisan config:cache
php artisan view:cache
```

### Database Optimization

```env
# Enable query logging in development
DB_LOGGING=true

# Connection pooling
DB_POOL_SIZE=10
```

## 🔧 Custom Configuration

### Custom Config Files

Create custom configuration files in `config/`:

```php
// config/chat.php
return [
    'max_message_length' => env('MAX_MESSAGE_LENGTH', 5000),
    'max_attachments_per_message' => env('MAX_ATTACHMENTS_PER_MESSAGE', 5),
    'message_retention_days' => env('MESSAGE_RETENTION_DAYS', 365),
];
```

### Environment-Specific Settings

Use different `.env` files for different environments:

- `.env.local` - Local development
- `.env.staging` - Staging environment
- `.env.production` - Production environment

## 📊 Monitoring Configuration

### Health Checks

Configure health check endpoints in `routes/api.php`:

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::ping() === '+PONG' ? 'connected' : 'disconnected',
    ]);
});
```

## 🔄 Configuration Validation

### Validate Configuration

Create a command to validate configuration:

```bash
php artisan config:validate
```

Example validation command:

```php
// app/Console/Commands/ValidateConfig.php
public function handle()
{
    $this->info('Validating configuration...');
    
    // Check database connection
    try {
        DB::connection()->getPdo();
        $this->info('✓ Database connection: OK');
    } catch (\Exception $e) {
        $this->error('✗ Database connection: FAILED');
    }
    
    // Check Redis connection
    try {
        Redis::ping();
        $this->info('✓ Redis connection: OK');
    } catch (\Exception $e) {
        $this->error('✗ Redis connection: FAILED');
    }
    
    // Check storage permissions
    if (is_writable(storage_path())) {
        $this->info('✓ Storage permissions: OK');
    } else {
        $this->error('✗ Storage permissions: FAILED');
    }
}
```

---

## 📝 Configuration Checklist

Before deploying, ensure you have configured:

- [ ] Database connection
- [ ] Redis connection
- [ ] Broadcasting (WebSockets)
- [ ] File storage
- [ ] Mail settings
- [ ] Security settings
- [ ] Rate limiting
- [ ] CORS policies
- [ ] Logging
- [ ] Cache settings

For more information, see the [Installation Guide](./INSTALLATION.md) and [Deployment Guide](./DEPLOYMENT.md). 