<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'redirect' => '/login']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();
$userId = $user['id'];
$db = getDB();

if ($action === 'connect') {
    $targetId = (int)($_POST['target_id'] ?? 0);
    
    if ($targetId === $userId) {
        echo json_encode(['success' => false, 'message' => 'You cannot connect with yourself']);
        exit;
    }

    // Check if profile is public
    $stmt = $db->prepare("SELECT is_public FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    if (!$stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Profile is not public']);
        exit;
    }

    // Check existing connection
    $stmt = $db->prepare("SELECT status FROM connections WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)");
    $stmt->execute([$userId, $targetId, $targetId, $userId]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'Connection request already pending']);
        } elseif ($existing['status'] === 'accepted') {
            echo json_encode(['success' => false, 'message' => 'You are already connected']);
        } else {
            // If rejected, maybe allow requesting again or just say rejected. Let's update to pending.
            $stmt = $db->prepare("UPDATE connections SET status = 'pending', requester_id = ?, receiver_id = ? WHERE (requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?)");
            $stmt->execute([$userId, $targetId, $userId, $targetId, $targetId, $userId]);
            echo json_encode(['success' => true, 'message' => 'Request sent']);
        }
        exit;
    }

    $stmt = $db->prepare("INSERT INTO connections (requester_id, receiver_id) VALUES (?, ?)");
    if ($stmt->execute([$userId, $targetId])) {
        echo json_encode(['success' => true, 'message' => 'Connection request sent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send request']);
    }
    exit;
}

if ($action === 'respond_connection') {
    $connId = (int)($_POST['connection_id'] ?? 0);
    $response = $_POST['response'] ?? ''; // 'accepted' or 'rejected'
    
    if (!in_array($response, ['accepted', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid response']);
        exit;
    }

    $stmt = $db->prepare("UPDATE connections SET status = ? WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    if ($stmt->execute([$response, $connId, $userId])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update connection']);
    }
    exit;
}

if ($action === 'disconnect') {
    $targetId = (int)($_POST['target_id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM connections WHERE status = 'accepted' AND ((requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?))");
    if ($stmt->execute([$userId, $targetId, $targetId, $userId])) {
        // Also delete messages between them? Optional, but typical to keep them or delete them. We'll leave messages for now or delete them based on foreign key? No, messages have sender/receiver. If we want full disconnect, maybe delete messages too. Actually, just deleting the connection is enough to stop messaging.
        echo json_encode(['success' => true, 'message' => 'Disconnected successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to disconnect']);
    }
    exit;
}

// Ensure typing columns exist (Auto-recovery)
try {
    $db->query("SELECT typing_to FROM users LIMIT 1");
} catch (Exception $e) {
    try {
        $db->query("ALTER TABLE users ADD COLUMN typing_to INT DEFAULT NULL, ADD COLUMN typing_at DATETIME DEFAULT NULL");
    } catch (Exception $e2) {}
}

if ($action === 'set_typing') {
    $targetId = (int)($_POST['target_id'] ?? 0);
    $stmt = $db->prepare("UPDATE users SET typing_to = ?, typing_at = NOW() WHERE id = ?");
    $stmt->execute([$targetId, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'send_message') {
    $targetId = (int)($_POST['target_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }

    // Clear typing status on send
    $db->prepare("UPDATE users SET typing_to = NULL WHERE id = ?")->execute([$userId]);

    // Check if connected
    $stmt = $db->prepare("SELECT id FROM connections WHERE status = 'accepted' AND ((requester_id = ? AND receiver_id = ?) OR (requester_id = ? AND receiver_id = ?))");
    $stmt->execute([$userId, $targetId, $targetId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You must be connected to send messages']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$userId, $targetId, $message])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
    exit;
}

if ($action === 'delete_message') {
    $msgId = (int)($_POST['msg_id'] ?? 0);
    // Delete only if current user is sender
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
    if ($stmt->execute([$msgId, $userId])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
    }
    exit;
}

if ($action === 'clear_chat') {
    $targetId = (int)($_POST['target_id'] ?? 0);
    // Delete all messages between current user and target user
    $stmt = $db->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    if ($stmt->execute([$userId, $targetId, $targetId, $userId])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear chat']);
    }
    exit;
}

if ($action === 'get_messages') {
    $targetId = (int)($_GET['target_id'] ?? 0);
    
    // Mark as read only if there are unread messages
    $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")->execute([$targetId, $userId]);

    // Check if target is typing to me (within last 4 seconds)
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND typing_to = ? AND typing_at > DATE_SUB(NOW(), INTERVAL 4 SECOND)");
    $stmt->execute([$targetId, $userId]);
    $isTyping = (bool)$stmt->fetch();

    // Fetch latest messages (limit to 100 for speed)
    $stmt = $db->prepare("SELECT * FROM (SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at DESC LIMIT 100) sub ORDER BY created_at ASC");
    $stmt->execute([$userId, $targetId, $targetId, $userId]);
    $messages = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'messages' => $messages, 'current_user_id' => $userId, 'is_typing' => $isTyping]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
