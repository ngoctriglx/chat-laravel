# Chat API Documentation

## Base URL
```
/api/v1/chat
```

## Authentication
All endpoints require authentication using Laravel Sanctum. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

## 📡 Real-time Events

This API is designed to work with WebSocket events for real-time functionality. For complete WebSocket events documentation, see:

**[🌐 WebSocket Events Documentation](./WEBSOCKET_EVENTS.md)**

### Quick Event Reference
- `message.sent` - New message sent
- `message.updated` - Message edited
- `message.deleted` - Message deleted
- `user.typing` - User typing indicator
- `user.presence` - User online status
- `conversation.created` - New conversation
- `friend-event.*` - Friend request events

## WebSocket Events

All major API actions (sending messages, editing, deleting, reacting, uploading attachments, updating conversations, presence changes, etc.) trigger real-time WebSocket events. See the [WebSocket Events Reference](./WEBSOCKET_EVENTS.md) for a full list of events and payloads.

- **Every event is actively used in request flows.**
- Events are guaranteed to be broadcast as part of the corresponding API request.

### Example: Sending a Message
- **Endpoint:** `POST /api/v1/chat/conversations/{id}/messages`
- **WebSocket Event:** `message.sent` (see [WebSocket Events Reference](./WEBSOCKET_EVENTS.md))

### Example: Adding a Reaction
- **Endpoint:** `POST /api/v1/chat/messages/{id}/reactions`
- `reaction.added`

### Example: Uploading an Attachment
- **Endpoint:** `POST /api/v1/chat/conversations/{id}/messages` (with file)
- **WebSocket Event:** `attachment.added`

### Example: User Goes Online
- **Endpoint:** `POST /api/v1/chat/presence/online`
- **WebSocket Event:** `user.online`

For a complete list of events and their payloads, see [WebSocket Events Reference](./WEBSOCKET_EVENTS.md).

---

## 1. Conversations Management

### 1.1 Get All Conversations
**GET** `/conversations`

Retrieve all conversations for the authenticated user.

**Query Parameters:**
- `per_page` (optional): Number of conversations per page (default: 20)
- `type` (optional): Filter by conversation type (`direct` or `group`)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": "uuid-string",
        "name": "Group Chat",
        "type": "group",
        "created_by": 1,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "metadata": {
          "description": "Optional metadata"
        },
        "participants": [
          {
            "user_id": 1,
            "user_name": "John Doe",
            "user_email": "john@example.com",
            "first_name": "John",
            "last_name": "Doe",
            "full_name": "John Doe",
            "gender": "male",
            "picture": "https://...",
            "background_image": "https://...",
            "birth_date": "1990-01-01",
            "status_message": "Hey there!",
            "pivot": {
              "joined_at": "2024-01-01T00:00:00.000000Z",
              "is_active": true,
              "left_at": null,
              "role": "member"
            }
          }
        ],
        "creator": {
          "user_id": 1,
          "user_name": "John Doe",
          "user_email": "john@example.com"
        },
        "last_message": {
          "id": 123,
          "content": "Last message content",
          "sender_id": 1,
          "created_at": "2024-01-01T00:00:00.000000Z",
          "sender": {
            "user_id": 1,
            "user_name": "John Doe"
          }
        },
        "unread_count": 5
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

**WebSocket Events:**
- `conversation.created` - When a new conversation is created
- `conversation.updated` - When conversation details are updated
- `conversation.deleted` - When a conversation is deleted

### 1.2 Create New Conversation
**POST** `/conversations`

Create a new conversation (direct or group).

**Request Body:**
```json
{
  "participant_ids": [2, 3, 4],
  "name": "Project Discussion",
  "type": "group",
  "metadata": {
    "description": "Optional metadata",
    "avatar": "https://example.com/avatar.jpg"
  }
}
```

**Validation Rules:**
- `participant_ids`: Required array with at least 1 user ID
- `participant_ids.*`: Must exist in users table (user_id field)
- `name`: Required if type is "group", max 255 characters
- `type`: Required, must be "direct" or "group"
- `metadata`: Optional array

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid-string",
    "name": "Project Discussion",
    "type": "group",
    "created_by": 1,
    "metadata": {
      "description": "Optional metadata",
      "avatar": "https://example.com/avatar.jpg"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "participants": [...],
    "creator": {
      "user_id": 1,
      "user_name": "John Doe",
      "user_email": "john@example.com"
    }
  }
}
```

**WebSocket Events:**
- `conversation.created` - Broadcasted to all participants

### 1.3 Get Single Conversation
**GET** `/conversations/{conversation_id}`

Retrieve details of a specific conversation.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid-string",
    "name": "Project Discussion",
    "type": "group",
    "created_by": 1,
    "metadata": {
      "description": "Optional metadata",
      "avatar": "https://example.com/avatar.jpg"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "participants": [...],
    "creator": {
      "user_id": 1,
      "user_name": "John Doe",
      "user_email": "john@example.com"
    },
    "message_count": 150,
    "participant_count": 5
  }
}
```

**Error Responses:**
- `403`: You are not a participant in this conversation
- `404`: Conversation not found

### 1.4 Update Conversation
**PUT** `/conversations/{conversation_id}`

Update conversation details (only creator can update).

**Request Body:**
```json
{
  "name": "Updated Group Name",
  "metadata": {
    "description": "Updated description",
    "avatar": "https://example.com/new-avatar.jpg"
  }
}
```

**Validation Rules:**
- `name`: Required, max 255 characters
- `metadata`: Optional array

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid-string",
    "name": "Updated Group Name",
    "metadata": {
      "description": "Updated description",
      "avatar": "https://example.com/new-avatar.jpg"
    },
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**Error Responses:**
- `403`: Only the conversation creator can update it
- `404`: Conversation not found

**WebSocket Events:**
- `conversation.updated` - Broadcasted to all participants

### 1.5 Delete Conversation
**DELETE** `/conversations/{conversation_id}`

Delete a conversation.

**Response:**
```json
{
  "success": true,
  "message": "Conversation deleted successfully"
}
```

**Error Responses:**
- `403`: Only the conversation creator can delete it
- `404`: Conversation not found

**WebSocket Events:**
- `conversation.deleted` - Broadcasted to all participants

---

## 2. Conversation Participants

### 2.1 Add Participants
**POST** `/conversations/{conversation_id}/participants`

Add new participants to a conversation (only creator can add).

**Request Body:**
```json
{
  "participant_ids": [5, 6, 7]
}
```

**Validation Rules:**
- `participant_ids`: Required array with at least 1 user ID
- `participant_ids.*`: Must exist in users table (user_id field)

**Response:**
```json
{
  "success": true,
  "data": {
    "added_participants": [
      {
        "user_id": 5,
        "user_name": "Jane Smith",
        "joined_at": "2024-01-01T00:00:00.000000Z"
      }
    ]
  }
}
```

**Error Responses:**
- `403`: Only the conversation creator can add participants
- `404`: Conversation not found

**WebSocket Events:**
- `participant.added` - Broadcasted to all existing participants

### 2.2 Remove Participant
**DELETE** `/conversations/{conversation_id}/participants/{user_id}`

Remove a participant from a conversation (only creator can remove).

**Response:**
```json
{
  "success": true,
  "message": "Participant removed successfully"
}
```

**Error Responses:**
- `403`: Only the conversation creator can remove participants
- `404`: Conversation or participant not found

**WebSocket Events:**
- `participant.removed` - Broadcasted to all remaining participants

---

## 3. Messages

### 3.1 Get Conversation Messages
**GET** `/conversations/{conversation_id}/messages`

Retrieve messages from a conversation.

**Query Parameters:**
- `cursor_id` (optional): Message ID to start pagination from
- `per_page` (optional): Number of messages per page (default: 20)
- `search` (optional): Search term to filter messages

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "content": "Hello everyone!",
        "sender_id": 1,
        "conversation_id": "uuid-string",
        "reply_to_id": null,
        "metadata": {
          "reply_to_message": null,
          "edited_at": null
        },
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "sender": {
          "user_id": 1,
          "user_name": "John Doe",
          "user_email": "john@example.com",
          "picture": "https://..."
        },
        "reactions": [
          {
            "id": 1,
            "reaction_type": "👍",
            "user_id": 2,
            "created_at": "2024-01-01T00:00:00.000000Z"
          }
        ],
        "attachments": [
          {
            "id": 1,
            "file_name": "document.pdf",
            "file_path": "attachments/document.pdf",
            "file_size": 1024000,
            "mime_type": "application/pdf",
            "url": "https://example.com/storage/attachments/document.pdf"
          }
        ],
        "read_by": [
          {
            "user_id": 2,
            "read_at": "2024-01-01T00:00:00.000000Z"
          }
        ]
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

### 3.2 Send Message
**POST** `/conversations/{conversation_id}/messages`

Send a new message to a conversation.

**Request Body (JSON):**
```json
{
  "content": "Hello everyone!",
  "reply_to_id": 123,
  "metadata": {
    "reply_to_message": {
      "id": 123,
      "content": "Original message"
    }
  }
}
```

**Request Body (Multipart for attachments):**
```
content: "Hello everyone!"
attachments[]: [file1]
attachments[]: [file2]
reply_to_id: 123
metadata: {"reply_to_message": {...}}
```

**Validation Rules:**
- `content`: Required (unless attachments are provided), max 5000 characters
- `reply_to_id`: Optional, must be a valid message ID in the conversation
- `attachments.*`: File upload, max 10MB per file, supported types: images, documents, videos
- `metadata`: Optional array

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "content": "Hello everyone!",
    "sender_id": 1,
    "conversation_id": "uuid-string",
    "reply_to_id": 123,
    "metadata": {
      "reply_to_message": {
        "id": 123,
        "content": "Original message"
      }
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "sender": {
      "user_id": 1,
      "user_name": "John Doe"
    },
    "reactions": [],
    "attachments": [],
    "read_by": []
  }
}
```

**WebSocket Events:**
- `message.sent` - Broadcasted to all conversation participants

### 3.3 Update Message
**PUT** `/messages/{message_id}`

Update an existing message.

**Request Body:**
```json
{
  "content": "Updated message content"
}
```

**Validation Rules:**
- `content`: Required, max 5000 characters

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "content": "Updated message content",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "metadata": {
      "edited_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

**Error Responses:**
- `403`: Only the message sender can update it
- `404`: Message not found

**WebSocket Events:**
- `message.updated` - Broadcasted to all conversation participants

### 3.4 Delete Message
**DELETE** `/messages/{message_id}`

Delete a message.

**Query Parameters:**
- `delete_for_everyone` (optional): Boolean, delete for all participants (default: false)

**Response:**
```json
{
  "success": true,
  "message": "Message deleted successfully"
}
```

**Error Responses:**
- `403`: Only the message sender can delete it
- `404`: Message not found

**WebSocket Events:**
- `message.deleted` - Broadcasted to all conversation participants

### 3.5 Mark Messages as Read
**POST** `/conversations/{conversation_id}/messages/read`

Mark all messages in a conversation as read.

**Response:**
```json
{
  "success": true,
  "data": {
    "read_count": 15,
    "read_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

**WebSocket Events:**
- `message.read` - Broadcasted to all conversation participants

### 3.6 Search Messages
**GET** `/conversations/{conversation_id}/messages/search`

Search for messages within a conversation.

**Query Parameters:**
- `query`: Required, search term (1-100 characters)
- `per_page` (optional): Number of results per page (1-50, default: 20)
- `date_from` (optional): Search from date (YYYY-MM-DD)
- `date_to` (optional): Search to date (YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [...],
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

### 3.7 Typing Indicator
**POST** `/conversations/{conversation_id}/messages/typing`

Send typing indicator to conversation participants.

**Request Body:**
```json
{
  "is_typing": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Typing status updated"
}
```

**WebSocket Events:**
- `user.typing` - Broadcasted to all conversation participants

---

## 4. Message Reactions

### 4.1 Add Reaction
**POST** `/messages/{message_id}/reactions`

Add a reaction to a message.

**Request Body:**
```json
{
  "reaction_type": "👍"
}
```

**Validation Rules:**
- `reaction_type`: Required, max 50 characters

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "reaction_type": "👍",
    "user_id": 1,
    "message_id": 123,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

### 4.2 Remove Reaction
**DELETE** `/messages/{message_id}/reactions`

Remove a reaction from a message.

**Request Body:**
```json
{
  "reaction_type": "👍"
}
```

**Validation Rules:**
- `reaction_type`: Required, max 50 characters

**Response:**
```json
{
  "success": true,
  "message": "Reaction removed successfully"
}
```

---

## 5. Presence

### 5.1 Set Online Status
**POST** `/presence/online`

Set user's online status.

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "online",
    "last_seen": "2024-01-01T00:00:00.000000Z"
  }
}
```

**WebSocket Events:**
- `user.presence` - Broadcasted to all user's conversations

### 5.2 Set Offline Status
**POST** `/presence/offline`

Set user's offline status.

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "offline",
    "last_seen": "2024-01-01T00:00:00.000000Z"
  }
}
```

**WebSocket Events:**
- `user.presence` - Broadcasted to all user's conversations

---

## 6. Broadcasting

### 6.1 Broadcasting Authentication
**POST** `/broadcasting`

Authenticate broadcasting connections (used by WebSockets).

**Headers:**
```
Authorization: Bearer {your_token}
```

**Request Body:**
```json
{
  "socket_id": "socket-id",
  "channel_name": "user.1"
}
```

**Response:**
```json
{
  "auth": "auth-signature"
}
```

---

## 7. Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

**Common HTTP Status Codes:**
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `429`: Too Many Requests (Rate Limited)
- `500`: Internal Server Error

---

## 8. Rate Limiting

API endpoints are subject to rate limiting. Check response headers for rate limit information:
- `X-RateLimit-Limit`: Maximum requests per window
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Time when the rate limit resets

**Default Rate Limits:**
- Authentication endpoints: 5 requests per minute
- Message endpoints: 60 requests per minute
- Other endpoints: 100 requests per minute

---

## 9. File Upload Guidelines

### Supported File Types
- **Images**: JPEG, PNG, GIF, WebP (max 5MB)
- **Documents**: PDF, DOC, DOCX, TXT (max 10MB)
- **Videos**: MP4, AVI, MOV (max 50MB)
- **Audio**: MP3, WAV, AAC (max 10MB)

### File Storage
- Files are stored securely in `storage/app/public/attachments/`
- Accessible via generated URLs
- Automatic cleanup of orphaned files
- Virus scanning (if configured)

### Upload Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "file_name": "document.pdf",
    "file_path": "attachments/document.pdf",
    "file_size": 1024000,
    "mime_type": "application/pdf",
    "url": "https://example.com/storage/attachments/document.pdf",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

## 10. Pagination

Most list endpoints support pagination with the following parameters:
- `per_page`: Number of items per page (default varies by endpoint)
- `cursor_id`: For cursor-based pagination (used in messages)
- `page`: For traditional pagination

Response includes pagination metadata:
```json
{
  "data": [...],
  "current_page": 1,
  "per_page": 20,
  "total": 100,
  "last_page": 5,
  "from": 1,
  "to": 20
}
```

---

## 11. Important Notes

### Field Name Changes
The API uses the following field names that may differ from standard Laravel conventions:

**User Model:**
- Primary key: `user_id` (instead of `id`)
- Name field: `user_name` (instead of `name`)
- Email field: `user_email` (instead of `email`)
- Additional fields: `first_name`, `last_name`, `gender`, `picture`, `background_image`, `birth_date`, `status_message`

**Conversation Model:**
- Creator field: `created_by` (instead of `creator_id`)
- Uses UUID for conversation IDs
- Includes `metadata` field for additional data

**Message Model:**
- Includes `reply_to_id` for message replies
- Includes `metadata` field for additional data
- Includes `read_by` array for read receipts

### Enhanced Participant Structure
- Participant objects in conversation responses include additional user profile fields
- Pivot data includes `joined_at`, `is_active`, `left_at`, and `role` fields
- Use `user_id` for participant identification

### Real-time Features
- All message operations trigger WebSocket events
- User presence is automatically tracked
- Typing indicators are real-time
- Read receipts are updated in real-time

---

## 12. SDK Examples

### JavaScript/TypeScript
```javascript
// Using fetch API
const response = await fetch('/api/v1/chat/conversations', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// Using axios
const response = await axios.get('/api/v1/chat/conversations', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

### PHP
```php
// Using Guzzle
$response = $client->get('/api/v1/chat/conversations', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token
    ]
]);
```

### Python
```python
// Using requests
response = requests.get(
    'https://api.example.com/api/v1/chat/conversations',
    headers={'Authorization': f'Bearer {token}'}
)
```

---

## 🔗 Related Documentation

- [WebSocket Events Documentation](./WEBSOCKET_EVENTS.md) - Real-time events
- [Installation Guide](./INSTALLATION.md) - Setup instructions
- [Configuration Guide](./CONFIGURATION.md) - Configuration options
- [Database Schema](./DATABASE_SCHEMA.md) - Database structure

For more information about Laravel API development, see the [Laravel documentation](https://laravel.com/docs). 