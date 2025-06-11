# Chat API Documentation - Conversations

## Base URL
```
/api/v1/chat
```

## Authentication
All endpoints require authentication using Laravel Sanctum. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

---

## 1. Conversations Management

### 1.1 Get All Conversations
**GET** `/conversations`

Retrieve all conversations for the authenticated user.

**Query Parameters:**
- `per_page` (optional): Number of conversations per page (default: 20)

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
        ]
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### 1.2 Create New Conversation
**POST** `/conversations`

Create a new conversation (direct or group).

**Request Body:**
```json
{
  "participant_ids": [2, 3, 4],
  "name": "Project Discussion", // Required for group conversations
  "type": "group", // "direct" or "group"
  "metadata": {
    "description": "Optional metadata"
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
      "description": "Optional metadata"
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

**Error Responses:**
- `403`: You are not a participant in this conversation

### 1.4 Update Conversation
**PUT** `/conversations/{conversation_id}`

Update conversation details (only creator can update).

**Request Body:**
```json
{
  "name": "Updated Group Name",
  "metadata": {
    "description": "Updated description"
  }
}
```

**Validation Rules:**
- `name`: Required, max 255 characters
- `metadata`: Optional array

**Error Responses:**
- `403`: Only the conversation creator can update it

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

**Error Responses:**
- `403`: Only the conversation creator can add participants

### 2.2 Remove Participant
**DELETE** `/conversations/{conversation_id}/participants/{user_id}`

Remove a participant from a conversation (only creator can remove).

**Error Responses:**
- `403`: Only the conversation creator can remove participants

---

## 3. Messages

### 3.1 Get Conversation Messages
**GET** `/conversations/{conversation_id}/messages`

Retrieve messages from a conversation.

**Query Parameters:**
- `cursor_id` (optional): Message ID to start pagination from
- `per_page` (optional): Number of messages per page (default: 20)

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
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z",
        "sender": {
          "user_id": 1,
          "user_name": "John Doe"
        },
        "reactions": [...],
        "attachments": [...]
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 1
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
  "metadata": {
    "reply_to": 123
  }
}
```

**Request Body (Multipart for attachments):**
```
content: "Hello everyone!"
attachments[]: [file1]
attachments[]: [file2]
metadata: {"reply_to": 123}
```

**Validation Rules:**
- `content`: Required (unless attachments are provided), max 5000 characters
- `attachments.*`: File upload, max 10MB per file
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
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z",
    "sender": {
      "user_id": 1,
      "user_name": "John Doe"
    },
    "reactions": [...],
    "attachments": [...]
  }
}
```

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

### 3.5 Mark Messages as Read
**POST** `/conversations/{conversation_id}/messages/read`

Mark all messages in a conversation as read.

**Response:**
```json
{
  "success": true,
  "message": "Messages marked as read"
}
```

### 3.6 Search Messages
**GET** `/conversations/{conversation_id}/messages/search`

Search for messages within a conversation.

**Query Parameters:**
- `query`: Required, search term (1-100 characters)
- `per_page` (optional): Number of results per page (1-50, default: 20)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [...],
    "current_page": 1,
    "per_page": 20,
    "total": 1
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

---

## 5. Presence

### 5.1 Set Online Status
**POST** `/presence/online`

Set user's online status.

**Response:**
```json
{
  "success": true,
  "message": "Online status updated"
}
```

### 5.2 Set Offline Status
**POST** `/presence/offline`

Set user's offline status.

**Response:**
```json
{
  "success": true,
  "message": "Offline status updated"
}
```

---

## 6. Broadcasting

### 6.1 Broadcasting Authentication
**POST** `/broadcasting`

Authenticate broadcasting connections (used by WebSockets).

**Headers:**
```
Authorization: Bearer {your_token}
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
- `500`: Internal Server Error

---

## 8. WebSocket Events

The following events are broadcasted via WebSockets:

- `MessageSent`: When a new message is sent
- `MessageUpdated`: When a message is updated
- `MessageDeleted`: When a message is deleted
- `MessageRead`: When messages are marked as read
- `UserTyping`: When a user starts/stops typing
- `UserPresence`: When a user's presence status changes
- `ConversationCreated`: When a new conversation is created
- `ConversationUpdated`: When a conversation is updated
- `ConversationDeleted`: When a conversation is deleted
- `ParticipantAdded`: When a participant is added to a conversation
- `ParticipantRemoved`: When a participant is removed from a conversation

---

## 9. Rate Limiting

API endpoints are subject to rate limiting. Check response headers for rate limit information:
- `X-RateLimit-Limit`: Maximum requests per window
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Time when the rate limit resets

---

## 10. File Upload Guidelines

- Maximum file size: 10MB per file
- Supported formats: All common file types
- Files are stored securely and accessible via generated URLs
- Attachments are automatically associated with messages

---

## 11. Pagination

Most list endpoints support pagination with the following parameters:
- `per_page`: Number of items per page (default varies by endpoint)
- `cursor_id`: For cursor-based pagination (used in messages)

Response includes pagination metadata:
```json
{
  "data": [...],
  "current_page": 1,
  "per_page": 20,
  "total": 100,
  "last_page": 5
}
```

---

## 12. Important Notes

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

**Participant Relationships:**
- Participants include pivot data with `joined_at`, `is_active`, `left_at`, and `role` fields
- Use `user_id` for participant identification
- Participants may include additional user profile fields (see above)

### Validation Updates
- User existence validation uses `exists:users,user_id` instead of `exists:users,id`
- All user references should use `user_id` field

### Enhanced Participant Structure
- Participant objects in conversation responses now include additional user profile fields for richer frontend display.
- Always check for the presence of these fields in the response when rendering participant/user info. 