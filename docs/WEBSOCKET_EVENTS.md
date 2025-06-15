# WebSocket Events Reference

This document provides a comprehensive guide to all WebSocket events broadcast by the Laravel Chat backend. All events use `PrivateChannel` for secure, authenticated real-time communication.

---

## 🔐 Security & Channel Architecture

### **PrivateChannel Strategy**
All events use `PrivateChannel` to ensure:
- **Authentication Required**: Only authenticated users can access channels
- **Data Privacy**: Events are only sent to authorized participants
- **Consistent Security**: Uniform security model across all event types
- **Scalable Architecture**: Easy to manage permissions and access control

### **Event Broadcasting Pattern**
```php
// Standard pattern for all events
broadcast(new EventClass(...))->toOthers()
```
- **Method**: `broadcast()` for real-time delivery
- **Channel**: `PrivateChannel` for secure communication
- **Scope**: `->toOthers()` excludes the sender from receiving their own events

**Note**: Laravel Echo requires a dot (`.`) prefix when listening for custom events on the frontend.
---

## 📡 Event Categories

### 1. **Message Events** - Real-time message operations
### 2. **Conversation Events** - Group and conversation management  
### 3. **User Interaction Events** - Typing indicators and presence
### 4. **Friend Events** - Friend request management
### 5. **File Events** - Attachment operations

---

## 1. 📨 Message Events

### `message.sent`
**Purpose**: Notify participants when a new message is sent  
**Channel**: `PrivateChannel('user.{participant_id}')` - Broadcast to all conversation participants  
**Backend Call**: `broadcast(new MessageSent($message))->toOthers()`

**Event Data**:
```json
{
  "event": "message.sent",
  "data": {
    "message": {
      "id": 123,
      "content": "Hello everyone!",
      "sender_id": 1,
      "conversation_id": "uuid-string",
      "reply_to_id": null,
      "metadata": {},
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z",
      "sender": {
        "user_id": 1,
        "user_name": "John Doe",
        "user_email": "john@example.com"
      },
      "reactions": [],
      "attachments": []
    },
    "conversation_id": "uuid-string"
  }
}
```

**Frontend Implementation**:
```javascript
// Listen on user private channel
const userChannel = Echo.private(`user.${userId}`);
userChannel.listen('.message.sent', (data) => {
    // Add new message to conversation
    addMessageToConversation(data.message);
    
    // Update conversation last message
    updateConversationLastMessage(data.conversation_id, data.message);
    
    // Show notification if conversation is not active
    if (!isActiveConversation(data.conversation_id)) {
        showMessageNotification(data.message);
    }
});
```

### `message.updated`
**Purpose**: Notify when a message is edited  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new MessageUpdated($message))->toOthers()`

**Event Data**:
```json
{
  "event": "message.updated",
  "data": {
    "message": {
      "id": 123,
      "content": "Updated message content",
      "sender_id": 1,
      "conversation_id": "uuid-string",
      "updated_at": "2024-01-01T00:00:00.000000Z",
      "sender": {
        "user_id": 1,
        "user_name": "John Doe"
      }
    },
    "conversation_id": "uuid-string"
  }
}
```

### `message.deleted`
**Purpose**: Notify when a message is deleted  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new MessageDeleted($message, $deleteForEveryone))->toOthers()`

**Event Data**:
```json
{
  "event": "message.deleted",
  "data": {
    "message_id": 123,
    "conversation_id": "uuid-string",
    "deleted_for_everyone": true
  }
}
```

### `message.read`
**Purpose**: Notify when messages are marked as read  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new MessageRead($conversation, $user))->toOthers()`

**Event Data**:
```json
{
  "event": "message.read",
  "data": {
    "conversation_id": "uuid-string",
    "user_id": 2,
    "last_read_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

## 2. 🎯 Reaction Events

### `reaction.added`
**Purpose**: Notify when a user reacts to a message  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new ReactionAdded($message, $user, $reactionType))->toOthers()`

**Event Data**:
```json
{
  "event": "reaction.added",
  "data": {
    "message_id": 123,
    "conversation_id": "uuid-string",
    "user_id": 1,
    "reaction_type": "👍",
    "user": {
      "user_id": 1,
      "user_name": "John Doe"
    }
  }
}
```

### `reaction.removed`
**Purpose**: Notify when a user removes their reaction  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new ReactionRemoved($message, $user, $reactionType))->toOthers()`

**Event Data**:
```json
{
  "event": "reaction.removed",
  "data": {
    "message_id": 123,
    "conversation_id": "uuid-string",
    "user_id": 1,
    "reaction_type": "👍",
    "user": {
      "user_id": 1,
      "user_name": "John Doe"
    }
  }
}
```

---

## 3. 📎 File Attachment Events

### `attachment.added`
**Purpose**: Notify when a file is attached to a message  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new AttachmentAdded($message, $attachment))->toOthers()`

**Event Data**:
```json
{
  "event": "attachment.added",
  "data": {
    "message_id": 123,
    "conversation_id": "uuid-string",
    "attachment": {
      "id": 1,
      "file_name": "photo.jpg",
      "file_path": "conversations/456/files/photo.jpg",
      "file_size": 204800,
      "mime_type": "image/jpeg",
      "url": "https://yourdomain.com/storage/conversations/456/files/photo.jpg",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

### `attachment.removed`
**Purpose**: Notify when a file attachment is removed  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new AttachmentRemoved($message, $attachment))->toOthers()`

**Event Data**:
```json
{
  "event": "attachment.removed",
  "data": {
    "message_id": 123,
    "conversation_id": "uuid-string",
    "attachment_id": 1,
    "file_name": "photo.jpg"
  }
}
```

---

## 4. 💬 Conversation Events

### `conversation.created`
**Purpose**: Notify when a new conversation is created  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new ConversationCreated($conversation))->toOthers()`

**Event Data**:
```json
{
  "event": "conversation.created",
  "data": {
    "conversation": {
      "id": "uuid-string",
      "name": "New Group Chat",
      "type": "group",
      "created_by": 1,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "participants": [
        {
          "user_id": 1,
          "user_name": "John Doe",
          "role": "owner"
        }
      ]
    }
  }
}
```

### `conversation.updated`
**Purpose**: Notify when conversation details are updated  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new ConversationUpdated($conversation))->toOthers()`

**Event Data**:
```json
{
  "event": "conversation.updated",
  "data": {
    "conversation": {
      "id": "uuid-string",
      "name": "Updated Group Name",
      "type": "group",
      "metadata": {
        "description": "Updated description"
      },
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

### `conversation.deleted`
**Purpose**: Notify when a conversation is deleted  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new ConversationDeleted($conversation, $user))->toOthers()`

**Event Data**:
```json
{
  "event": "conversation.deleted",
  "data": {
    "conversation_id": "uuid-string",
    "deleted_by": {
      "user_id": 1,
      "user_name": "John Doe"
    }
  }
}
```

### `participant.added`
**Purpose**: Notify when a new participant is added to a conversation  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new ParticipantAdded($conversation, $participantId, $user))->toOthers()`

**Event Data**:
```json
{
  "event": "participant.added",
  "data": {
    "conversation_id": "uuid-string",
    "participant_id": 3,
    "added_by": {
      "user_id": 1,
      "user_name": "John Doe"
    }
  }
}
```

### `participant.removed`
**Purpose**: Notify when a participant is removed from a conversation  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new ParticipantRemoved($conversation, $userId, $user))->toOthers()`

**Event Data**:
```json
{
  "event": "participant.removed",
  "data": {
    "conversation_id": "uuid-string",
    "user_id": 3,
    "removed_by": {
      "user_id": 1,
      "user_name": "John Doe"
    }
  }
}
```

---

## 5. 👤 User Interaction Events

### `user.typing`
**Triggered when**: A user starts or stops typing  
**Channel**: `PrivateChannel('user.{participant_id}')` - Excludes sender  
**Backend Call**: `broadcast(new UserTyping($conversation, $user, $isTyping))->toOthers()`

**Event Data**:
```json
{
  "event": "user.typing",
  "data": {
    "conversation_id": "uuid-string",
    "user_id": 1,
    "is_typing": true
  }
}
```

### `user.presence`
**Triggered when**: A user's online status changes  
**Channel**: `PrivateChannel('conversation.{conversation_id}')`  
**Backend Call**: `broadcast(new UserPresence($user, $status, $lastSeen))`

**Event Data**:
```json
{
  "event": "user.presence",
  "data": {
    "user_id": 1,
    "status": "online",
    "last_seen": "2024-01-01T00:00:00.000000Z"
  }
}
```

### `user.online`
**Purpose**: Notify when a user comes online  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new UserOnline($user, $conversation))->toOthers()`

**Event Data**:
```json
{
  "event": "user.online",
  "data": {
    "user_id": 1,
    "user_name": "John Doe",
    "status": "online",
    "conversation_id": "uuid-string"
  }
}
```

### `user.offline`
**Purpose**: Notify when a user goes offline  
**Channel**: `PrivateChannel('user.{participant_id}')`  
**Backend Call**: `broadcast(new UserOffline($user, $conversation))->toOthers()`

**Event Data**:
```json
{
  "event": "user.offline",
  "data": {
    "user_id": 1,
    "user_name": "John Doe",
    "status": "offline",
    "last_seen": "2024-01-01T00:00:00.000000Z",
    "conversation_id": "uuid-string"
  }
}
```

---

## 6. 👥 Friend Events

**Note**: Laravel Echo requires a dot (`.`) prefix when listening for custom events on the frontend.

### `friend.request.sent`
**Purpose**: Notify when a friend request is sent  
**Channel**: `PrivateChannel('user.{receiver_id}')`  
**Backend Call**: `broadcast(new FriendRequestSent($receiverId, $senderId))->toOthers()`

**Event Data**:
```json
{
  "event": "friend.request.sent",
  "data": {
    "sender_id": 1
  }
}
```

### `friend-event.revoked.{receiver_id}`
**Purpose**: Notify when a friend request is revoked  
**Channel**: `PrivateChannel('user.{receiver_id}')`  
**Backend Call**: `broadcast(new FriendRequestRevoked($receiverId, $senderId))->toOthers()`

### `friend-event.rejected.{sender_id}`
**Purpose**: Notify when a friend request is rejected  
**Channel**: `PrivateChannel('user.{sender_id}')`  
**Backend Call**: `broadcast(new FriendRequestRejected($senderId, $receiverId))->toOthers()`

### `friend-event.accepted.{user_id}`
**Purpose**: Notify when a friend request is accepted  
**Channel**: `PrivateChannel('user.{user_id}')`  
**Backend Call**: `broadcast(new FriendRequestAccepted($userId, $friendId))->toOthers()`

### `friend-event.removed.{user_id}`
**Purpose**: Notify when a friend is removed  
**Channel**: `PrivateChannel('user.{user_id}')`  
**Backend Call**: `broadcast(new FriendRemoved($userId, $friendId))->toOthers()`

---

## 🔧 Frontend Implementation Guide

### Channel Setup
```javascript
// For conversation events (PrivateChannel)
const userChannel = Echo.private(`user.${userId}`);

// For friend events (PrivateChannel) - Now uses user-specific channels
const userChannel = Echo.private(`user.${userId}`);

// For conversation presence (PrivateChannel)
const conversationChannel = Echo.private(`conversation.${conversationId}`);
```

### Event Listening
```javascript
// Listen to conversation events
userChannel.listen('.message.sent', (data) => {
    console.log('New message:', data.message);
    addMessageToConversation(data.message);
});

// Listen to friend events (REQUIRED: dot prefix for custom events)
// Now using the same user channel for friend events
userChannel.listen(`.friend-event.request.${userId}`, (data) => {
    console.log('Friend request received:', data);
    showFriendRequestNotification(data.sender_id);
});

// Listen to presence events
conversationChannel.listen('.user.presence', (data) => {
    console.log('User presence changed:', data);
    updateUserStatus(data.user_id, data.status);
});
```

### Error Handling
```javascript
userChannel.error((error) => {
    console.error('Channel error:', error);
    // Handle reconnection or fallback
});
```

---

## 📊 Event Summary Table

| Event | Purpose | Channel | Backend Method |
|-------|---------|---------|----------------|
| `message.sent` | New message notification | `PrivateChannel('user.{id}')` | `broadcast(new MessageSent($message))->toOthers()` |
| `message.updated` | Message edit notification | `PrivateChannel('user.{id}')` | `broadcast(new MessageUpdated($message))->toOthers()` |
| `message.deleted` | Message deletion notification | `PrivateChannel('user.{id}')` | `broadcast(new MessageDeleted($message, $deleteForEveryone))->toOthers()` |
| `message.read` | Read receipt notification | `PrivateChannel('user.{id}')` | `broadcast(new MessageRead($conversation, $user))->toOthers()` |
| `reaction.added` | Reaction notification | `PrivateChannel('user.{id}')` | `broadcast(new ReactionAdded($message, $user, $reactionType))->toOthers()` |
| `reaction.removed` | Reaction removal notification | `PrivateChannel('user.{id}')` | `broadcast(new ReactionRemoved($message, $user, $reactionType))->toOthers()` |
| `attachment.added` | File attachment notification | `PrivateChannel('user.{id}')` | `broadcast(new AttachmentAdded($message, $attachment))->toOthers()` |
| `attachment.removed` | File removal notification | `PrivateChannel('user.{id}')` | `broadcast(new AttachmentRemoved($message, $attachment))->toOthers()` |
| `conversation.created` | New conversation notification | `PrivateChannel('user.{id}')` | `broadcast(new ConversationCreated($conversation))->toOthers()` |
| `conversation.updated` | Conversation update notification | `PrivateChannel('user.{id}')` | `broadcast(new ConversationUpdated($conversation))->toOthers()` |
| `conversation.deleted` | Conversation deletion notification | `PrivateChannel('user.{id}')` | `broadcast(new ConversationDeleted($conversation, $user))->toOthers()` |
| `participant.added` | Participant addition notification | `PrivateChannel('user.{id}')` | `broadcast(new ParticipantAdded($conversation, $participantId, $user))->toOthers()` |
| `participant.removed` | Participant removal notification | `PrivateChannel('user.{id}')` | `broadcast(new ParticipantRemoved($conversation, $userId, $user))->toOthers()` |
| `user.typing` | Typing indicator | `PrivateChannel('user.{id}')` | `broadcast(new UserTyping($conversation, $user, $isTyping))->toOthers()` |
| `user.presence` | Presence status update | `PrivateChannel('conversation.{id}')` | `broadcast(new UserPresence($user, $status, $lastSeen))` |
| `user.online` | Online status notification | `PrivateChannel('user.{participant_id}')` | `broadcast(new UserOnline($user, $conversation))->toOthers()` |
| `user.offline` | Offline status notification | `PrivateChannel('user.{participant_id}')` | `broadcast(new UserOffline($user, $conversation))->toOthers()` |
| `friend.request.sent` | Friend request sent | `PrivateChannel('user.{receiver_id}')` | `broadcast(new FriendRequestSent($receiverId, $senderId))->toOthers()` |
| `friend.request.accepted` | Friend request accepted | `PrivateChannel('user.{user_id}')` | `broadcast(new FriendRequestAccepted($userId, $friendId))->toOthers()` |
| `friend.request.rejected` | Friend request rejected | `PrivateChannel('user.{sender_id}')` | `broadcast(new FriendRequestRejected($senderId, $receiverId))->toOthers()` |
| `friend.request.revoked` | Friend request revoked | `PrivateChannel('user.{receiver_id}')` | `broadcast(new FriendRequestRevoked($receiverId, $senderId))->toOthers()` |
| `friend.removed` | Friend removed | `PrivateChannel('user.{user_id}')` | `broadcast(new FriendRemoved($userId, $friendId))->toOthers()` |

---

## 🚀 Best Practices

### **Security**
1. **Always use PrivateChannel** for secure, authenticated communication
2. **Implement proper authentication** for all channel access
3. **Validate user permissions** before broadcasting events

### **Performance**
1. **Use `->toOthers()`** to prevent senders from receiving their own events
2. **Implement connection pooling** for better resource management
3. **Handle reconnection gracefully** to maintain user experience

### **Development**
1. **Handle connection errors** and implement fallback mechanisms
2. **Validate event data** before processing on the frontend
3. **Use consistent event naming** across the application
4. **Remember the dot prefix** for custom events in frontend listeners

### **Testing**
```bash
# Test specific events
php artisan test:websocket friend-request --user-id=1
php artisan test:websocket message-sent --user-id=1
php artisan test:websocket typing --user-id=1
```

---

## 📝 Recent Updates

### **v2.0 - PrivateChannel Migration**
- **Security Enhancement**: All events now use `PrivateChannel` for authenticated communication
- **Consistent Architecture**: Uniform security model across all event types
- **Parameter Optimization**: Corrected event constructor parameters for better reliability
- **Recipient Targeting**: All events properly target conversation participants
- **Friend Event Security**: Friend events now use user-specific channels instead of generic channels
- **Separate Friend Events**: Friend events now use individual classes for better type safety and consistency
- **Simplified Event Names**: Friend events now use static, descriptive names following Laravel conventions

### **Key Improvements**
- ✅ **Enhanced Security**: PrivateChannel requires authentication for all events
- ✅ **Parameter Fixes**: Corrected event constructor parameters for better reliability
- ✅ **Recipient Targeting**: All events now properly target conversation participants
- ✅ **Consistent Pattern**: Standardized broadcasting method across all events
- ✅ **Friend Event Channels**: Friend events now use `PrivateChannel('user.{receiver_id}')` for better security
- ✅ **Separate Event Classes**: Friend events now use individual classes (`FriendRequestSent`, `FriendRequestAccepted`, etc.) for better type safety and consistency with message events
- ✅ **Simplified Event Names**: Friend events now use static names like `friend.request.sent` instead of dynamic names with user IDs

For questions or issues, see the main README or contact the project maintainer. 