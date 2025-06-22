# Database Schema

This document provides a comprehensive overview of the database structure for the Laravel Chat Application.

## 📊 Database Overview

The application uses a relational database with the following main entities:
- **Users** - User accounts and authentication
- **User Details** - Extended user profile information
- **User Settings** - User preferences and settings
- **User Tokens** - Authentication tokens
- **Conversations** - Chat conversations (direct and group)
- **Conversation Participants** - Conversation membership and roles
- **Messages** - Individual chat messages
- **Message Attachments** - File attachments for messages
- **Message Reactions** - Message reactions (emojis, etc.)
- **Message Read Status** - Message read tracking
- **Message Visibility** - Message visibility control
- **Friends** - Friend relationships
- **Friend Requests** - Friend request workflow
- **System Tables** - Sessions, cache, jobs, personal access tokens

## 🗂️ Table Structure

### Users Table

```sql
CREATE TABLE users (
    user_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_name VARCHAR(50) UNIQUE NULL,
    user_email VARCHAR(50) UNIQUE NULL,
    user_phone VARCHAR(50) UNIQUE NULL,
    user_password VARCHAR(255) NOT NULL,
    user_account_status ENUM('active', 'pending', 'suspended', 'banned', 'deactivated') DEFAULT 'pending',
    user_banned_reason VARCHAR(255) NULL,
    user_registered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Purpose**: Stores user account information and authentication data.

**Key Fields**:
- `user_id` - Primary key (auto-incrementing)
- `user_name` - Unique username (optional)
- `user_email` - Unique email address (optional)
- `user_phone` - Unique phone number (optional)
- `user_password` - Hashed password
- `user_account_status` - Account status with enum values
- `user_banned_reason` - Reason for ban if applicable
- `user_registered` - Registration timestamp
- `deleted_at` - Soft delete timestamp

**Model**: `App\Models\User`
- Uses `user_id` as primary key
- No timestamps by default
- Soft deletes enabled
- Custom authentication methods

### User Details Table

```sql
CREATE TABLE user_details (
    detail_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    picture LONGTEXT NULL,
    gender ENUM('male', 'female') DEFAULT 'male',
    birth_date DATE NOT NULL,
    status_message VARCHAR(255) NULL,
    background_image LONGTEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

**Purpose**: Stores extended user profile information.

**Key Fields**:
- `detail_id` - Primary key
- `user_id` - Foreign key to users table
- `first_name`, `last_name` - User's real name
- `picture` - Profile picture URL/path
- `gender` - User's gender (male/female)
- `birth_date` - Date of birth
- `status_message` - User's status/bio message
- `background_image` - Background image URL/path

**Model**: `App\Models\UserDetail`
- Uses `detail_id` as primary key
- No timestamps by default
- Validation for birth date and gender

### User Settings Table

```sql
CREATE TABLE user_settings (
    setting_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    theme_mode ENUM('light', 'dark', 'system') DEFAULT 'light',
    language VARCHAR(10) NOT NULL,
    allow_friend_requests ENUM('everyone', 'friends_of_friends', 'contacts_only', 'nobody') DEFAULT 'everyone',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

**Purpose**: Stores user preferences and settings.

**Key Fields**:
- `setting_id` - Primary key
- `user_id` - Foreign key to users table
- `theme_mode` - UI theme preference
- `language` - Language preference
- `allow_friend_requests` - Friend request privacy setting

### User Tokens Table

```sql
CREATE TABLE user_tokens (
    token_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

**Purpose**: Stores authentication tokens for users.

**Key Fields**:
- `token_id` - Primary key
- `user_id` - Foreign key to users table
- `type` - Token type (email verification, password reset, etc.)
- `token` - The actual token value
- `expires_at` - Token expiration timestamp

### Conversations Table

```sql
CREATE TABLE conversations (
    id CHAR(36) PRIMARY KEY,
    created_by BIGINT UNSIGNED NULL,
    type VARCHAR(255) DEFAULT 'direct',
    name VARCHAR(255) NULL,
    metadata JSON NULL,
    last_message_id CHAR(36) NULL,
    last_message_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (last_message_id) REFERENCES messages(id) ON DELETE SET NULL
);
```

**Purpose**: Stores chat conversations (both direct and group chats).

**Key Fields**:
- `id` - UUID primary key for conversations
- `created_by` - User who created the conversation (nullable)
- `type` - Conversation type ('direct' or 'group')
- `name` - Conversation name (required for group chats)
- `metadata` - Additional conversation data (JSON)
- `last_message_id` - **Direct reference to the last message (optimized for previews)**
- `last_message_at` - Timestamp of last message
- `is_deleted` - Soft delete flag
- `deleted_at` - Soft delete timestamp

**Performance Benefits**:
- **Fast Conversation Previews**: Direct foreign key lookup instead of expensive JOINs
- **Reduced Database Load**: Eliminates `ORDER BY created_at DESC LIMIT 1` queries
- **Better Caching**: Last message ID can be cached efficiently
- **Consistency**: Always have the exact last message reference

**Model**: `App\Models\Conversation`
- Uses UUIDs for primary key
- Soft deletes enabled
- JSON casting for metadata
- Scopes for active, direct, and group conversations
- `lastMessage()` relationship for direct access to last message
- `latestMessage()` relationship for backward compatibility

### Conversation Participants Table

```sql
CREATE TABLE conversation_participants (
    conversation_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(255) DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    last_read_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

**Purpose**: Manages conversation membership and roles.

**Key Fields**:
- `conversation_id` - Foreign key to conversations (part of composite primary key)
- `user_id` - Foreign key to users (part of composite primary key)
- `role` - User's role in the conversation ('admin', 'member')
- `joined_at` - When user joined the conversation
- `left_at` - When user left (NULL if still active)
- `last_read_at` - Last time user read messages
- `is_active` - Whether user is currently active

**Model**: `App\Models\ConversationParticipant`
- Composite primary key
- Pivot table for many-to-many relationship

### Messages Table

```sql
CREATE TABLE messages (
    id CHAR(36) PRIMARY KEY,
    conversation_id CHAR(36) NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    metadata JSON NULL,
    parent_message_id CHAR(36) NULL,
    cursor_id BIGINT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES messages(id) ON DELETE SET NULL,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_created_at (created_at),
    INDEX idx_cursor_id (cursor_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_parent_message_id (parent_message_id)
);
```

**Purpose**: Stores individual chat messages.

**Key Fields**:
- `id` - UUID primary key
- `conversation_id` - Foreign key to conversations
- `sender_id` - User who sent the message
- `content` - Message text content
- `type` - Message type (text, image, file, etc.)
- `metadata` - Additional message data (JSON)
- `parent_message_id` - ID of message being replied to (for replies)
- `cursor_id` - For pagination and ordering
- `is_edited` - Whether message has been edited
- `deleted_at` - Soft delete timestamp

**Model**: `App\Models\Message`
- Uses UUIDs for primary key
- Soft deletes enabled
- JSON casting for metadata
- Scopes for message types and edited messages

### Message Visibility Table

```sql
CREATE TABLE message_visibilities (
    message_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    is_visible BOOLEAN DEFAULT TRUE,
    hidden_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

**Purpose**: Controls message visibility for individual users.

**Key Fields**:
- `message_id` - Foreign key to messages (part of composite primary key)
- `user_id` - Foreign key to users (part of composite primary key)
- `is_visible` - Whether message is visible to user
- `hidden_at` - When message was hidden

**Model**: `App\Models\MessageVisibility`
- Composite primary key
- Controls message visibility per user

### Message Read Status Table

```sql
CREATE TABLE message_read_status (
    message_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

**Purpose**: Tracks which users have read which messages.

**Key Fields**:
- `message_id` - Foreign key to messages (part of composite primary key)
- `user_id` - Foreign key to users (part of composite primary key)
- `read_at` - Timestamp when message was read

### Message Reactions Table

```sql
CREATE TABLE message_reactions (
    id CHAR(36) PRIMARY KEY,
    message_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reaction_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_reaction (message_id, user_id, reaction_type),
    INDEX idx_message_reaction (message_id, reaction_type)
);
```

**Purpose**: Stores user reactions to messages (emojis, etc.).

**Key Fields**:
- `id` - UUID primary key
- `message_id` - Foreign key to messages
- `user_id` - User who reacted
- `reaction_type` - Type of reaction (emoji, text, etc.)

**Model**: `App\Models\MessageReaction`
- Uses UUIDs for primary key
- Unique constraint prevents duplicate reactions

### Message Attachments Table

```sql
CREATE TABLE message_attachments (
    id CHAR(36) PRIMARY KEY,
    message_id CHAR(36) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size BIGINT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    INDEX idx_message_id (message_id),
    INDEX idx_file_type (file_type)
);
```

**Purpose**: Stores file attachments for messages.

**Key Fields**:
- `id` - UUID primary key
- `message_id` - Foreign key to messages
- `file_name` - Original filename
- `file_type` - File MIME type
- `file_size` - File size in bytes
- `file_path` - Storage path for the file
- `metadata` - Additional file metadata (JSON)
- `deleted_at` - Soft delete timestamp

**Model**: `App\Models\MessageAttachment`
- Uses UUIDs for primary key
- Soft deletes enabled
- JSON casting for metadata

### Friends Table

```sql
CREATE TABLE friends (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    friend_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id)
);
```

**Purpose**: Stores friend relationships between users.

**Key Fields**:
- `id` - Primary key
- `user_id` - First user in the friendship
- `friend_id` - Second user in the friendship
- `created_at` - When friendship was established

**Model**: `App\Models\Friend`
- Simple model for friend relationships

### Friend Requests Table

```sql
CREATE TABLE friend_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sender_id BIGINT UNSIGNED NOT NULL,
    receiver_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'canceled') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_request (sender_id, receiver_id)
);
```

**Purpose**: Manages friend request workflow.

**Key Fields**:
- `id` - Primary key
- `sender_id` - User sending the friend request
- `receiver_id` - User receiving the friend request
- `status` - Current status of the request

**Model**: `App\Models\FriendRequest`
- Enum casting for status field

## 🔗 Relationships

### User Relationships
```
users (1) ←→ (1) user_details
users (1) ←→ (1) user_settings
users (1) ←→ (many) user_tokens
users (1) ←→ (many) conversations (as creator)
users (many) ←→ (many) conversations (as participants)
users (1) ←→ (many) messages
users (many) ←→ (many) friends
users (1) ←→ (many) friend_requests (as sender)
users (1) ←→ (many) friend_requests (as receiver)
users (many) ←→ (many) message_visibilities
users (many) ←→ (many) message_read_status
users (1) ←→ (many) message_reactions
```

### Conversation Relationships
```
conversations (1) ←→ (many) conversation_participants
conversations (1) ←→ (many) messages
conversations (1) ←→ (1) users (as creator)
conversations (1) ←→ (1) messages (as last_message) [OPTIMIZED]
```

### Message Relationships
```
messages (1) ←→ (many) message_attachments
messages (1) ←→ (many) message_reactions
messages (1) ←→ (many) message_read_status
messages (1) ←→ (many) message_visibilities
messages (1) ←→ (1) messages (as parent_message)
messages (1) ←→ (many) messages (as replies)
```

## 🗃️ System Tables

### Sessions Table
```sql
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);
```

### Cache Tables
```sql
CREATE TABLE cache (
    `key` VARCHAR(255) PRIMARY KEY,
    value MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL
);

CREATE TABLE cache_locks (
    `key` VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL
);
```

### Personal Access Tokens Table
```sql
CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_tokenable (tokenable_type, tokenable_id)
);
```

### Jobs Tables
```sql
CREATE TABLE jobs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_queue (queue)
);

CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 📈 Indexes

### Primary Indexes
- All tables have primary key indexes
- Foreign key columns are automatically indexed

### Unique Indexes
- `users.user_name` - Ensures unique usernames
- `users.user_email` - Ensures unique email addresses
- `users.user_phone` - Ensures unique phone numbers
- `conversation_participants.primary` - Composite primary key
- `message_reactions.unique_reaction` - Prevents duplicate reactions
- `message_read_status.primary` - Composite primary key
- `message_visibilities.primary` - Composite primary key
- `friends.unique_friendship` - Prevents duplicate friendships
- `friend_requests.unique_request` - Prevents duplicate requests
- `personal_access_tokens.token` - Ensures unique tokens

### Performance Indexes
```sql
-- Message performance indexes
CREATE INDEX idx_messages_conversation_created ON messages(conversation_id, created_at DESC);
CREATE INDEX idx_messages_cursor_id ON messages(cursor_id);
CREATE INDEX idx_messages_sender_id ON messages(sender_id);
CREATE INDEX idx_messages_parent_message_id ON messages(parent_message_id);

-- Message reactions
CREATE INDEX idx_message_reactions_message_type ON message_reactions(message_id, reaction_type);

-- Message attachments
CREATE INDEX idx_message_attachments_message_id ON message_attachments(message_id);
CREATE INDEX idx_message_attachments_file_type ON message_attachments(file_type);

-- Message read status
CREATE INDEX idx_message_read_status_user_id ON message_read_status(user_id);

-- Sessions
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- Jobs
CREATE INDEX idx_jobs_queue ON jobs(queue);

-- Personal access tokens
CREATE INDEX idx_personal_access_tokens_tokenable ON personal_access_tokens(tokenable_type, tokenable_id);
```

## 🔄 Data Flow

### Message Creation Flow
1. User sends message → `messages` table
2. If attachments → `message_attachments` table
3. Create message visibility entries → `message_visibilities` table
4. **Update conversation's `last_message_id` and `last_message_at`** → `conversations` table
5. Broadcast to participants via WebSockets
6. Participants mark as read → `message_read_status` table

### Conversation Creation Flow
1. Create conversation → `conversations` table
2. Add participants → `conversation_participants` table
3. Broadcast conversation creation event

### Friend Request Flow
1. Send request → `friend_requests` table (status: pending)
2. Receiver accepts/rejects → Update status
3. If accepted → Create entries in `friends` table

### User Registration Flow
1. Create user → `users` table
2. Create user details → `user_details` table
3. Create user settings → `user_settings` table
4. Send verification email → `user_tokens` table

## 🗃️ Data Retention

### Message Retention
- Messages are kept indefinitely by default
- Soft deletes for message deletion (keeps data for audit)
- Message visibility controls individual user access

### File Storage
- Attachments stored in `storage/app/public/attachments/`
- File paths stored in `message_attachments.file_path`
- Automatic cleanup of orphaned files

### Session Management
- Sessions stored in database for persistence
- Automatic cleanup of expired sessions
- IP address and user agent tracking

## 🔒 Security Considerations

### Data Protection
- Passwords are hashed using Laravel's Hash facade
- Sensitive data (emails, names) should be encrypted in production
- File uploads are validated for type and size
- Personal access tokens for API authentication

### Access Control
- User can only access conversations they're participants in
- Message visibility controlled by conversation membership
- Friend requests require mutual consent
- Session-based authentication with database storage

### Account Management
- Multiple account statuses (active, pending, suspended, banned, deactivated)
- Ban reason tracking
- Soft deletes for data retention

## 📊 Performance Optimizations

### Query Optimization
- Use eager loading for related data
- Implement pagination for large datasets
- Cache frequently accessed data
- Use cursor-based pagination for messages

### Database Optimization
- Regular database maintenance
- Monitor slow queries
- Optimize indexes based on usage patterns
- Use composite indexes for common query patterns

### Caching Strategy
- Database cache for application data
- Cache locks for concurrent operations
- Session data in database for persistence

## 🚀 Last Message Optimization

### Overview
The `last_message_id` column provides a direct reference to the most recent message in each conversation, significantly improving performance for conversation previews and listings.

### Benefits
- **Performance**: Direct foreign key lookup instead of expensive JOINs
- **Scalability**: Reduces database load as message volume grows
- **Caching**: Efficient caching of last message references
- **Consistency**: Always have the exact last message reference

### Usage Examples

#### Before Optimization (Expensive)
```php
// Slow query - requires JOIN and ORDER BY
$conversations = Conversation::with(['messages' => function($query) {
    $query->latest()->limit(1);
}])->get();

// Or even slower - N+1 problem
foreach ($conversations as $conversation) {
    $lastMessage = $conversation->messages()->latest()->first();
}
```

#### After Optimization (Fast)
```php
// Fast query - direct foreign key lookup
$conversations = Conversation::with('lastMessage.sender')->get();

// Direct access to last message
foreach ($conversations as $conversation) {
    $lastMessage = $conversation->lastMessage;
}
```

#### Conversation Listing with Previews
```php
// Efficient conversation listing with last message preview
$conversations = Conversation::with([
    'lastMessage.sender',
    'lastMessage.attachments',
    'participants'
])->whereHas('participants', function($query) use ($userId) {
    $query->where('user_id', $userId);
})->orderBy('last_message_at', 'desc')->get();
```

#### API Response Example
```json
{
    "conversations": [
        {
            "id": "uuid-1",
            "name": "Group Chat",
            "type": "group",
            "last_message_at": "2024-01-15T10:30:00Z",
            "last_message": {
                "id": "msg-uuid-1",
                "content": "Hello everyone!",
                "type": "text",
                "created_at": "2024-01-15T10:30:00Z",
                "sender": {
                    "user_id": 123,
                    "user_name": "john_doe"
                }
            }
        }
    ]
}
```

### Maintenance
The `last_message_id` is automatically maintained by the `MessageService`:
- **On Message Send**: Updates `last_message_id` and `last_message_at`
- **On Message Delete**: Recalculates and updates if deleted message was the last
- **Data Migration**: Use `php artisan conversations:populate-last-message-id` for existing data

---

## 🔧 Migration Commands

### Create New Migration
```bash
php artisan make:migration create_new_table_name
```

### Run Migrations
```bash
php artisan migrate
```

### Rollback Migrations
```bash
php artisan migrate:rollback
```

### Reset Database
```bash
php artisan migrate:fresh
```

### Seed Database
```bash
php artisan db:seed
```

## 📋 Model Relationships Summary

### User Model Relationships
- `userDetail()` - HasOne relationship with UserDetail
- `conversations()` - BelongsToMany relationship with Conversation through conversation_participants

### Conversation Model Relationships
- `creator()` - BelongsTo relationship with User
- `participants()` - BelongsToMany relationship with User through conversation_participants
- `activeParticipants()` - BelongsToMany relationship with active users
- `messages()` - HasMany relationship with Message
- `latestMessage()` - HasOne relationship with latest Message (backward compatibility)
- `lastMessage()` - BelongsTo relationship with Message (optimized direct reference)

### Message Model Relationships
- `conversation()` - BelongsTo relationship with Conversation
- `sender()` - BelongsTo relationship with User
- `parentMessage()` - BelongsTo relationship with Message (for replies)
- `replies()` - HasMany relationship with Message (for replies)
- `reactions()` - HasMany relationship with MessageReaction
- `attachments()` - HasMany relationship with MessageAttachment
- `readBy()` - BelongsToMany relationship with User through message_read_status
- `visibility()` - HasMany relationship with MessageVisibility

For more information, see the [Installation Guide](./INSTALLATION.md) and [Configuration Guide](./CONFIGURATION.md). 