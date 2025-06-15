# Database Schema

This document provides a comprehensive overview of the database structure for the Laravel Chat Application.

## 📊 Database Overview

The application uses a relational database with the following main entities:
- **Users** - User accounts and authentication
- **Conversations** - Chat conversations (direct and group)
- **Messages** - Individual chat messages
- **Friends** - Friend relationships and requests
- **Attachments** - File attachments for messages
- **Reactions** - Message reactions

## 🗂️ Table Structure

### Users Table

```sql
CREATE TABLE users (
    user_id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Purpose**: Stores user account information and authentication data.

**Key Fields**:
- `user_id` - Primary key (note: uses `user_id` instead of `id`)
- `user_name` - Display name for the user
- `user_email` - Unique email address
- `password` - Hashed password
- `email_verified_at` - Email verification timestamp

### User Details Table

```sql
CREATE TABLE user_details (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(255) NULL,
    last_name VARCHAR(255) NULL,
    gender ENUM('male', 'female', 'other') NULL,
    picture VARCHAR(255) NULL,
    background_image VARCHAR(255) NULL,
    birth_date DATE NULL,
    status_message TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

**Purpose**: Stores extended user profile information.

**Key Fields**:
- `user_id` - Foreign key to users table
- `first_name`, `last_name` - User's real name
- `gender` - User's gender preference
- `picture` - Profile picture URL
- `background_image` - Background image URL
- `birth_date` - Date of birth
- `status_message` - User's status/bio message

### Conversations Table

```sql
CREATE TABLE conversations (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NULL,
    type ENUM('direct', 'group') NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);
```

**Purpose**: Stores chat conversations (both direct and group chats).

**Key Fields**:
- `id` - UUID primary key for conversations
- `name` - Conversation name (required for group chats)
- `type` - Either 'direct' or 'group'
- `created_by` - User who created the conversation
- `metadata` - Additional conversation data (JSON)

### Conversation Participants Table

```sql
CREATE TABLE conversation_participants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    conversation_id CHAR(36) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('member', 'admin', 'owner') DEFAULT 'member',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (conversation_id, user_id)
);
```

**Purpose**: Manages conversation membership and roles.

**Key Fields**:
- `conversation_id` - Foreign key to conversations
- `user_id` - Foreign key to users
- `joined_at` - When user joined the conversation
- `left_at` - When user left (NULL if still active)
- `is_active` - Whether user is currently active
- `role` - User's role in the conversation

### Messages Table

```sql
CREATE TABLE messages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    content TEXT NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    conversation_id CHAR(36) NOT NULL,
    reply_to_id BIGINT UNSIGNED NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE SET NULL
);
```

**Purpose**: Stores individual chat messages.

**Key Fields**:
- `content` - Message text content
- `sender_id` - User who sent the message
- `conversation_id` - Conversation the message belongs to
- `reply_to_id` - ID of message being replied to (for replies)
- `metadata` - Additional message data (JSON)

### Message Attachments Table

```sql
CREATE TABLE message_attachments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    message_id BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);
```

**Purpose**: Stores file attachments for messages.

**Key Fields**:
- `message_id` - Foreign key to messages
- `file_name` - Original filename
- `file_path` - Storage path for the file
- `file_size` - File size in bytes
- `mime_type` - File MIME type

### Message Reactions Table

```sql
CREATE TABLE message_reactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    message_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reaction_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_reaction (message_id, user_id, reaction_type)
);
```

**Purpose**: Stores user reactions to messages (emojis, etc.).

**Key Fields**:
- `message_id` - Foreign key to messages
- `user_id` - User who reacted
- `reaction_type` - Type of reaction (emoji, text, etc.)

### Message Read Status Table

```sql
CREATE TABLE message_read_status (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    message_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_read_status (message_id, user_id)
);
```

**Purpose**: Tracks which users have read which messages.

**Key Fields**:
- `message_id` - Foreign key to messages
- `user_id` - User who read the message
- `read_at` - Timestamp when message was read

### Friends Table

```sql
CREATE TABLE friends (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    friend_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id)
);
```

**Purpose**: Stores friend relationships between users.

**Key Fields**:
- `user_id` - First user in the friendship
- `friend_id` - Second user in the friendship

### Friend Requests Table

```sql
CREATE TABLE friend_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sender_id BIGINT UNSIGNED NOT NULL,
    receiver_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_request (sender_id, receiver_id)
);
```

**Purpose**: Manages friend request workflow.

**Key Fields**:
- `sender_id` - User sending the friend request
- `receiver_id` - User receiving the friend request
- `status` - Current status of the request

## 🔗 Relationships

### User Relationships
```
users (1) ←→ (1) user_details
users (1) ←→ (many) conversations (as creator)
users (many) ←→ (many) conversations (as participants)
users (1) ←→ (many) messages
users (many) ←→ (many) friends
users (1) ←→ (many) friend_requests (as sender)
users (1) ←→ (many) friend_requests (as receiver)
```

### Conversation Relationships
```
conversations (1) ←→ (many) conversation_participants
conversations (1) ←→ (many) messages
conversations (1) ←→ (1) users (as creator)
```

### Message Relationships
```
messages (1) ←→ (many) message_attachments
messages (1) ←→ (many) message_reactions
messages (1) ←→ (many) message_read_status
messages (1) ←→ (1) messages (as reply_to)
```

## 📈 Indexes

### Primary Indexes
- All tables have primary key indexes
- Foreign key columns are automatically indexed

### Unique Indexes
- `users.user_email` - Ensures unique email addresses
- `conversation_participants.unique_participant` - Prevents duplicate participants
- `message_reactions.unique_reaction` - Prevents duplicate reactions
- `message_read_status.unique_read_status` - Prevents duplicate read status
- `friends.unique_friendship` - Prevents duplicate friendships
- `friend_requests.unique_request` - Prevents duplicate requests

### Performance Indexes
```sql
-- For message queries by conversation
CREATE INDEX idx_messages_conversation_created ON messages(conversation_id, created_at DESC);

-- For user search
CREATE INDEX idx_users_name_email ON users(user_name, user_email);

-- For conversation participants
CREATE INDEX idx_participants_user_active ON conversation_participants(user_id, is_active);

-- For friend requests
CREATE INDEX idx_friend_requests_receiver_status ON friend_requests(receiver_id, status);
```

## 🔄 Data Flow

### Message Creation Flow
1. User sends message → `messages` table
2. If attachments → `message_attachments` table
3. Broadcast to participants via WebSockets
4. Participants mark as read → `message_read_status` table

### Conversation Creation Flow
1. Create conversation → `conversations` table
2. Add participants → `conversation_participants` table
3. Broadcast conversation creation event

### Friend Request Flow
1. Send request → `friend_requests` table (status: pending)
2. Receiver accepts/rejects → Update status
3. If accepted → Create entries in `friends` table

## 🗃️ Data Retention

### Message Retention
- Messages are kept indefinitely by default
- Can be configured via `MESSAGE_RETENTION_DAYS` environment variable
- Soft deletes for message deletion (keeps data for audit)

### File Storage
- Attachments stored in `storage/app/public/attachments/`
- File paths stored in `message_attachments.file_path`
- Automatic cleanup of orphaned files

## 🔒 Security Considerations

### Data Protection
- Passwords are hashed using Laravel's Hash facade
- Sensitive data (emails, names) should be encrypted in production
- File uploads are validated for type and size

### Access Control
- User can only access conversations they're participants in
- Message visibility controlled by conversation membership
- Friend requests require mutual consent

## 📊 Performance Optimizations

### Query Optimization
- Use eager loading for related data
- Implement pagination for large datasets
- Cache frequently accessed data

### Database Optimization
- Regular database maintenance
- Monitor slow queries
- Optimize indexes based on usage patterns

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

For more information, see the [Installation Guide](./INSTALLATION.md) and [Configuration Guide](./CONFIGURATION.md). 