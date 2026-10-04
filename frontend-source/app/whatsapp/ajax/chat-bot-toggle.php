<?php
// The Bot switch on the Chats thread. Pausing opens a handover on the chat —
// the same row live-chats.php shows — which is what silences the bot; resuming
// resolves it. Reusing the handover machinery rather than a second "muted"
// flag means there is exactly one source of truth for "is a person handling
// this", and a pause made here is visible on Live chats and vice versa.
require_once dirname(__DIR__, 2) . '/config/init.php';

header('Content-Type: application/json');

// A session cookie proves a login happened; suspension, deactivation and
// password changes must bite on the next request, so the check is the active
// user guard, not the bare session flag (#3).
requireActiveUserJson();

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (!csrfTokenValid($input['csrf_token'] ?? '')) {
    echo json_encode(['ok' => false, 'error' => 'Invalid request. Reload the page and try again.']);
    exit;
}

$sessionId = $input['session_id'] ?? '';
$chatId = $input['chat_id'] ?? '';
$botOn = !empty($input['bot']);

if (empty($sessionId) || empty($chatId)) {
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}
if (str_ends_with($chatId, '@g.us')) {
    echo json_encode(['ok' => false, 'error' => 'The bot switch is not available for groups.']);
    exit;
}

[$accountId, $tenantId, $userId] = requireOwnedAccount($conn, $sessionId);

if (!$botOn) {
    // Pause: open a handover and take it, since the tenant pausing the bot *is*
    // the person now handling the chat — leaving it 'waiting' would page the
    // queue for something already claimed. No customer acknowledgement and no
    // staff notification: nobody asked for a person, the tenant did this
    // themselves.
    [$id, $isNew] = handoffOpen($conn, $userId, [
        'account_id' => $accountId,
        'chat_id' => $chatId,
        'customer_phone' => chatbotPhoneFromJid($chatId),
        'reason' => 'paused from Chats',
        'topic' => null,
    ]);
    $handoff = handoffById($conn, $userId, $id);
    if ($handoff && $handoff['status'] === 'waiting') {
        handoffClaim($conn, $userId, $id, $userId);
        $handoff['status'] = 'claimed';
    }
    if ($isNew) {
        logAudit($conn, 'handoff.paused', 'chat_handoff', (string)$id, ['via' => 'chats'], $userId);
    }
    echo json_encode(['ok' => true, 'handoff' => ['id' => (int)$id, 'status' => $handoff['status'] ?? 'claimed']]);
    exit;
}

// Resume: resolving the open handover is what hands the conversation back to
// the bot. No open row is a fine answer — the switch was already effectively
// on (another tab resolved it, or the handover predates this page loading).
$open = handoffOpenForChat($conn, $userId, $chatId);
if (!$open) {
    echo json_encode(['ok' => true, 'handoff' => null]);
    exit;
}

if (!handoffClose($conn, $userId, (int)$open['id'], 'resolved')) {
    // Already closed by someone else — the end state the caller wanted.
    echo json_encode(['ok' => true, 'handoff' => null]);
    exit;
}
logAudit($conn, 'handoff.resolved', 'chat_handoff', (string)$open['id'], ['via' => 'chats'], $userId);

// Tell the customer the bot is back, if the tenant wants that — the same
// message and the same send as resolving from Live chats.
$config = chatbotConfig($conn, $userId);
$resume = trim((string)($config['handoff_resume_message'] ?? ''));
if ($resume !== '') {
    chatbotSendReply($conn, $userId, $tenantId, $sessionId, $chatId, $resume);
}

echo json_encode(['ok' => true, 'handoff' => null]);
