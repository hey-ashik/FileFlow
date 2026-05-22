<?php
$currentPage = 'messages';
$pageTitle = 'Messages & Connections - ' . APP_NAME;
require_once __DIR__ . '/../includes/header.php';

$user = getCurrentUser();
$userId = $user['id'];
$db = getDB();

// Fetch connections (accepted)
$stmt = $db->prepare("
    SELECT c.id as conn_id, u.id, u.full_name, u.avatar_path, u.avatar_color, u.profile_slug,
           (SELECT COUNT(id) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM connections c
    JOIN users u ON (u.id = c.requester_id OR u.id = c.receiver_id)
    WHERE (c.requester_id = ? OR c.receiver_id = ?) AND c.status = 'accepted' AND u.id != ?
");
$stmt->execute([$userId, $userId, $userId, $userId]);
$connections = $stmt->fetchAll();

// Fetch pending requests (received)
$stmt = $db->prepare("
    SELECT c.id as conn_id, u.id, u.full_name, u.avatar_path, u.avatar_color, u.profile_slug
    FROM connections c
    JOIN users u ON u.id = c.requester_id
    WHERE c.receiver_id = ? AND c.status = 'pending'
");
$stmt->execute([$userId]);
$pendingRequests = $stmt->fetchAll();

$chatTarget = (int) ($_GET['chat'] ?? 0);
?>

<div style="position: fixed; inset: 0; z-index: -1; background: linear-gradient(135deg, var(--green-50) 0%, var(--white) 50%, var(--green-50) 100%); overflow: hidden; pointer-events: none;">
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
</div>

<div class="container" style="max-width: 1200px; padding: 2rem 1rem; position: relative; z-index: 1;">
    <div style="display: flex; flex-direction: row; gap: 0; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; height: 80vh; overflow: hidden;"
        class="messages-layout">

        <!-- Sidebar -->
        <div style="width: 320px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #f8fafc;"
            class="messages-sidebar <?php echo ($chatTarget > 0) ? 'mobile-hidden' : ''; ?>">
            <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                <h2 style="font-size: 1.25rem; font-weight: 600; color: #0f172a; margin: 0;">Network</h2>
            </div>

            <div style="flex: 1; overflow-y: auto; padding: 1rem;">
                <?php if (!empty($pendingRequests)): ?>
                    <h3
                        style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.75rem;">
                        Pending Requests</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.5rem;">
                        <?php foreach ($pendingRequests as $req): ?>
                            <div
                                style="background: white; padding: 1rem; border-radius: 8px; border: 1px solid #cbd5e1; display: flex; flex-direction: column; gap: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div
                                        style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo htmlspecialchars($req['avatar_color']); ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; overflow: hidden;">
                                        <?php if (!empty($req['avatar_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($req['avatar_path']); ?>"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($req['full_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.95rem; color: #0f172a;">
                                        <?php echo htmlspecialchars($req['full_name']); ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button onclick="respondConnection(<?php echo $req['conn_id']; ?>, 'accepted')"
                                        style="flex: 1; padding: 0.4rem; background: #16a34a; color: white; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">Accept</button>
                                    <button onclick="respondConnection(<?php echo $req['conn_id']; ?>, 'rejected')"
                                        style="flex: 1; padding: 0.4rem; background: #f1f5f9; color: #64748b; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">Decline</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h3
                    style="font-size: 0.85rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.75rem;">
                    Connections</h3>
                <?php if (empty($connections)): ?>
                    <div style="text-align: center; color: #94a3b8; font-size: 0.9rem; padding: 1rem;">No connections yet.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php foreach ($connections as $conn): ?>
                            <a href="/messages?chat=<?php echo $conn['id']; ?>"
                                ondblclick="confirmDisconnect(<?php echo $conn['id']; ?>, '<?php echo addslashes($conn['full_name']); ?>')"
                                title="Double-click to disconnect"
                                style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-radius: 8px; text-decoration: none; background: <?php echo $chatTarget === (int) $conn['id'] ? '#e0f2fe' : 'transparent'; ?>; transition: all 0.2s; color: #0f172a; cursor: pointer;">
                                <div
                                    style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo htmlspecialchars($conn['avatar_color']); ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; overflow: hidden; flex-shrink: 0;">
                                    <?php if (!empty($conn['avatar_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($conn['avatar_path']); ?>"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($conn['full_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div
                                        style="font-weight: 600; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($conn['full_name']); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #64748b;">
                                        @<?php echo htmlspecialchars($conn['profile_slug']); ?></div>
                                </div>
                                <?php if ($conn['unread_count'] > 0 && $chatTarget !== (int) $conn['id']): ?>
                                    <div style="background: #16a34a; color: white; font-size: 0.75rem; font-weight: bold; border-radius: 50%; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; padding: 0 6px; margin-left: 8px;">
                                        <?php echo $conn['unread_count']; ?>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div style="flex: 1; display: flex; flex-direction: column; background: white;"
            class="messages-main <?php echo ($chatTarget === 0) ? 'mobile-hidden' : ''; ?>">
            <?php if ($chatTarget > 0):
                $targetUser = null;
                foreach ($connections as $c) {
                    if ((int) $c['id'] === $chatTarget) {
                        $targetUser = $c;
                        break;
                    }
                }
                ?>
                <?php if ($targetUser): ?>
                    <div
                        style="padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem;">
                        <a href="/messages" class="mobile-back-btn"
                            style="display: none; padding: 0.5rem; color: #64748b; text-decoration: none;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>
                        </a>
                        <div
                            style="width: 44px; height: 44px; border-radius: 50%; background: <?php echo htmlspecialchars($targetUser['avatar_color']); ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; overflow: hidden; flex-shrink: 0;">
                            <?php if (!empty($targetUser['avatar_path'])): ?>
                                <img src="<?php echo htmlspecialchars($targetUser['avatar_path']); ?>"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($targetUser['full_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div style="flex: 1; min-width: 0; display: flex; flex-direction: column;">
                            <h2
                                style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex-shrink: 1;">
                                <?php echo htmlspecialchars($targetUser['full_name']); ?>
                            </h2>
                            <a href="/u/<?php echo htmlspecialchars($targetUser['profile_slug']); ?>" target="_blank"
                                style="font-size: 0.8rem; color: var(--primary); text-decoration: none; width: fit-content;">View
                                Profile</a>
                        </div>
                        <button onclick="clearChat(<?php echo $chatTarget; ?>)" style="background: none; border: none; cursor: pointer; color: #ef4444; display: flex; align-items: center; justify-content: center; padding: 0.5rem; border-radius: 6px; transition: background 0.2s;" title="Clear Chat" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>

                    <div id="chat-messages"
                        style="flex: 1; padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem; background: #fdfdfd; scroll-behavior: smooth;">
                        <?php
                        $initialMessages = [];
                        $lastId = 0;
                        try {
                            $stmt = $db->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
                            $stmt->execute([$userId, $chatTarget, $chatTarget, $userId]);
                            $initialMessages = $stmt->fetchAll();
                            if (!empty($initialMessages)) {
                                foreach ($initialMessages as $msg) {
                                    $isMine = ($msg['sender_id'] == $userId);
                                    $lastId = max($lastId, $msg['id']);
                                    ?>
                                    <div data-msg-id="<?php echo $msg['id']; ?>" class="message-bubble" onclick="toggleMsgActions(this)"
                                        style="max-width: 75%; padding: 0.75rem 1rem; border-radius: 18px; font-size: 0.95rem; line-height: 1.4; word-break: break-word; margin-bottom: 0.5rem; align-self: <?php echo $isMine ? 'flex-end' : 'flex-start'; ?>; background: <?php echo $isMine ? '#16a34a' : '#f1f5f9'; ?>; color: <?php echo $isMine ? 'white' : '#0f172a'; ?>; border-bottom-<?php echo $isMine ? 'right' : 'left'; ?>-radius: 4px; cursor: pointer;">
                                        <?php echo htmlspecialchars($msg['message']); ?>
                                        <div class="msg-actions" style="display: none; font-size: 0.7rem; margin-top: 4px; opacity: 0.9; text-align: right; justify-content: flex-end; gap: 8px; align-items: center;">
                                            <span><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                                            <?php if ($isMine): ?>
                                                <button onclick="event.stopPropagation(); deleteMessage(<?php echo $msg['id']; ?>)" style="background:none; border:none; color:inherit; cursor:pointer; padding:0; display: flex; align-items: center;" title="Delete message">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div id="messages-placeholder" style="text-align: center; color: #94a3b8; font-size: 0.9rem; margin-top: 2rem;">Start the conversation! 👋</div>';
                            }
                        } catch (Exception $e) {
                        }
                        ?>
                    </div>

                    <div style="padding: 1.25rem; border-top: 1px solid #e2e8f0; background: white;">
                        <form id="chat-form" method="POST" action="/api/network"
                            onsubmit="event.preventDefault(); if(window.handleChatSubmit) handleChatSubmit(event); return false;"
                            style="display: flex; gap: 0.75rem; align-items: center;">
                            <input type="hidden" name="action" value="send_message">
                            <input type="hidden" name="target_id" value="<?php echo $chatTarget; ?>">
                            <input type="text" id="chat-input" name="message" placeholder="Type a message..."
                                style="flex: 1; padding: 0.75rem 1.25rem; border: 1px solid #e2e8f0; border-radius: 24px; font-size: 0.95rem; outline: none; background: #f8fafc;"
                                autocomplete="off">
                            <button type="submit" id="send-btn" class="send-btn"
                                style="background: #16a34a; color: white; border: none; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; flex-shrink: 0; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.2);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <script>
                        // SPA Safety: Clear any existing interval from a previous chat load
                        if (window.chatInterval) clearInterval(window.chatInterval);
                        if (window.chatAbortController) window.chatAbortController.abort();

                        window.chatAbortController = new AbortController();
                        const targetId = <?php echo $chatTarget; ?>;
                        const messagesContainer = document.getElementById('chat-messages');
                        let lastMessageId = <?php echo $lastId; ?>;
                        let isInitialLoad = false;
                        let isFetching = false;

                        // Scroll to bottom immediately
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;

                        function appendMessage(msg, isMine, isOptimistic = false) {
                            const placeholder = document.getElementById('messages-placeholder');
                            if (placeholder) placeholder.remove();

                            const msgId = typeof msg === 'object' ? msg.id : null;
                            if (msgId && document.querySelector(`[data-msg-id="${msgId}"]`)) return;

                            const div = document.createElement('div');
                            if (msgId) div.setAttribute('data-msg-id', msgId);
                            if (isOptimistic) {
                                div.setAttribute('data-optimistic', 'true');
                                div.setAttribute('data-msg-text', typeof msg === 'string' ? msg : msg.message);
                            }

                            div.style.maxWidth = '75%';
                            div.style.padding = '0.75rem 1rem';
                            div.style.borderRadius = '18px';
                            div.style.fontSize = '0.95rem';
                            div.style.lineHeight = '1.4';
                            div.style.wordBreak = 'break-word';
                            div.style.marginBottom = '0.5rem';
                            div.style.alignSelf = isMine ? 'flex-end' : 'flex-start';
                            div.style.background = isMine ? '#16a34a' : '#f1f5f9';
                            div.style.color = isMine ? 'white' : '#0f172a';
                            div.style.borderBottomRightRadius = isMine ? '4px' : '18px';
                            div.style.borderBottomLeftRadius = isMine ? '18px' : '4px';
                            div.style.cursor = 'pointer';
                            div.onclick = function() { toggleMsgActions(this); };
                            if (isOptimistic) div.style.opacity = '0.7';

                            let msgText = typeof msg === 'string' ? msg : msg.message;
                            let timeStr = typeof msg === 'object' && msg.created_at ? new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            
                            // Escape text for safety
                            const textNode = document.createTextNode(msgText);
                            const p = document.createElement('div');
                            p.appendChild(textNode);
                            let safeText = p.innerHTML;

                            let timeHtml = `<div class="msg-actions" style="display: none; font-size: 0.7rem; margin-top: 4px; opacity: 0.9; text-align: right; justify-content: flex-end; gap: 8px; align-items: center;">
                                <span>${timeStr}</span>`;
                            
                            if (isMine && msgId) {
                                timeHtml += `<button onclick="event.stopPropagation(); deleteMessage(${msgId})" style="background:none; border:none; color:inherit; cursor:pointer; padding:0; display: flex; align-items: center;" title="Delete message">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>`;
                            }
                            timeHtml += `</div>`;
                            
                            div.innerHTML = safeText + timeHtml;

                            const typingIndicator = document.getElementById('typing-indicator');
                            if (typingIndicator) {
                                messagesContainer.insertBefore(div, typingIndicator);
                            } else {
                                messagesContainer.appendChild(div);
                            }

                            messagesContainer.scrollTop = messagesContainer.scrollHeight;

                            if (!isOptimistic && msgId && msgId > lastMessageId) {
                                lastMessageId = msgId;
                            }
                        }

                        function toggleTyping(isTyping) {
                            let indicator = document.getElementById('typing-indicator');
                            if (isTyping) {
                                if (!indicator) {
                                    indicator = document.createElement('div');
                                    indicator.id = 'typing-indicator';
                                    indicator.className = 'typing-indicator';
                                    indicator.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
                                    messagesContainer.appendChild(indicator);
                                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                }
                            } else {
                                if (indicator) indicator.remove();
                            }
                        }

                        async function loadMessages() {
                            if (isFetching || document.hidden) return;
                            isFetching = true;
                            try {
                                const res = await fetch(`/api/network?action=get_messages&target_id=${targetId}`, {
                                    signal: window.chatAbortController.signal
                                });
                                const data = await res.json();
                                if (data.success) {
                                    // Update typing status
                                    toggleTyping(data.is_typing);

                                    // Remove optimistic messages that are now confirmed by server
                                    const optimisticMsgs = document.querySelectorAll('[data-optimistic="true"]');

                                    data.messages.forEach(msg => {
                                        const isMine = msg.sender_id == data.current_user_id;
                                        if (isMine) {
                                            optimisticMsgs.forEach(opt => {
                                                if (opt.getAttribute('data-msg-text') === msg.message) opt.remove();
                                            });
                                        }
                                        appendMessage(msg, isMine);
                                    });
                                }
                            } catch (e) {
                                if (e.name !== 'AbortError') console.error(e);
                            } finally {
                                isFetching = false;
                            }
                        }

                        const chatInput = document.getElementById('chat-input');
                        chatInput.addEventListener('input', () => {
                            fetch('/api/network?action=set_typing', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `target_id=${targetId}`
                            });
                        });

                        window.handleChatSubmit = async (e) => {
                            e.preventDefault();
                            const msg = chatInput.value.trim();
                            if (!msg) return false;

                            const formData = new FormData(document.getElementById('chat-form'));

                            chatInput.value = '';
                            appendMessage(msg, true, true);

                            try {
                                const res = await fetch('/api/network', {
                                    method: 'POST',
                                    body: formData
                                });
                                const data = await res.json();
                                if (!data.success) {
                                    showToast(data.message || 'Failed to send message', 'error');
                                }
                                loadMessages();
                            } catch (e) {
                                console.error(e);
                                showToast('Network error', 'error');
                            }
                            return false;
                        };

                        window.chatInterval = setInterval(loadMessages, 1000);
                        // Immediate load if not already rendered by PHP
                        if (lastMessageId === 0) loadMessages();
                    </script>
                <?php else: ?>
                    <div
                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #64748b;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                            style="margin-bottom: 1rem; opacity: 0.5;">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <p style="font-size: 1.1rem;">Connection not found.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div
                    style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #64748b;">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        style="margin-bottom: 1rem; opacity: 0.5;">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <p style="font-size: 1.1rem;">Select a connection to start messaging</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .typing-indicator {
        display: flex;
        gap: 4px;
        padding: 10px 15px;
        background: #f1f5f9;
        border-radius: 18px;
        border-bottom-left-radius: 4px;
        width: fit-content;
        margin-bottom: 0.5rem;
        align-self: flex-start;
    }

    .typing-dot {
        width: 6px;
        height: 6px;
        background: #64748b;
        border-radius: 50%;
        animation: typing-anim 1.4s infinite ease-in-out both;
    }

    .typing-dot:nth-child(1) {
        animation-delay: -0.32s;
    }

    .typing-dot:nth-child(2) {
        animation-delay: -0.16s;
    }

    @keyframes typing-anim {

        0%,
        80%,
        100% {
            transform: scale(0);
        }

        40% {
            transform: scale(1);
        }
    }

    .send-btn:active {
        transform: scale(0.9);
    }

    .send-btn:hover {
        background: #15803d !important;
    }

    @media (max-width: 768px) {
        .container {
            padding: 0 !important;
        }

        .messages-layout {
            height: calc(100vh - 80px) !important;
            border-radius: 0 !important;
            border: none !important;
            margin-top: 0 !important;
        }

        .mobile-hidden {
            display: none !important;
        }

        .messages-sidebar {
            width: 100% !important;
            border-right: none !important;
        }

        .messages-main {
            width: 100% !important;
            height: 100% !important;
        }

        .mobile-back-btn {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
    }
</style>

<script>
    function toggleMsgActions(el) {
        const actions = el.querySelector('.msg-actions');
        if (!actions) return;
        
        if (actions.style.display === 'flex') {
            actions.style.display = 'none';
            if (el._hideTimeout) clearTimeout(el._hideTimeout);
        } else {
            // Hide others
            document.querySelectorAll('.msg-actions').forEach(a => {
                a.style.display = 'none';
            });
            actions.style.display = 'flex';
            if (el._hideTimeout) clearTimeout(el._hideTimeout);
            el._hideTimeout = setTimeout(() => {
                actions.style.display = 'none';
            }, 3000);
        }
    }

    async function clearChat(targetId) {
        if (!confirm('Are you sure you want to delete this entire chat? This action cannot be undone.')) return;

        const formData = new FormData();
        formData.append('target_id', targetId);
        formData.append('action', 'clear_chat');

        try {
            const res = await fetch('/api/network', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                const messagesContainer = document.getElementById('chat-messages');
                if (messagesContainer) messagesContainer.innerHTML = '<div id="messages-placeholder" style="text-align: center; color: #94a3b8; font-size: 0.9rem; margin-top: 2rem;">Start the conversation! 👋</div>';
                showToast('Chat cleared successfully');
            } else {
                showToast(data.message || 'Failed to clear chat', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    }

    async function deleteMessage(msgId) {
        if (!confirm('Delete this message?')) return;
        
        const formData = new FormData();
        formData.append('msg_id', msgId);
        formData.append('action', 'delete_message');

        try {
            const res = await fetch('/api/network', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                const msgDiv = document.querySelector(`[data-msg-id="${msgId}"]`);
                if (msgDiv) msgDiv.remove();
            } else {
                showToast(data.message || 'Failed to delete message', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    }

    async function respondConnection(connId, response) {
        const formData = new FormData();
        formData.append('connection_id', connId);
        formData.append('response', response);
        formData.append('action', 'respond_connection'); // Redundant but safe

        try {
            const res = await fetch('/api/network', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                showToast(data.message || 'Error responding to request', 'error');
            }
        } catch (e) {
            showToast('Network error', 'error');
        }
    }

    async function confirmDisconnect(userId, name) {
        if (confirm(`Are you sure you want to disconnect and unfriend ${name}?`)) {
            const formData = new FormData();
            formData.append('target_id', userId);
            formData.append('action', 'disconnect');

            try {
                const res = await fetch('/api/network', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Disconnected successfully');
                    setTimeout(() => location.href = '/messages', 1000);
                } else {
                    showToast(data.message || 'Error disconnecting', 'error');
                }
            } catch (e) {
                showToast('Network error', 'error');
            }
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>