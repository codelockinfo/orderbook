<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = getCurrentUserId();

// Get all groups for current user (groups they created or are members of)
if ($method === 'GET' && $action === 'list') {
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT g.*, 
                   u.username as creator_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as member_count,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'pending') as pending_count,
                   (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as user_role
            FROM groups g
            LEFT JOIN users u ON g.created_by = u.id
            LEFT JOIN group_members gm ON g.id = gm.group_id
            WHERE g.created_by = ? OR (gm.user_id = ? AND gm.status = 'active')
            ORDER BY g.created_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId]);
        $groups = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'groups' => $groups]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch groups: ' . $e->getMessage()]);
    }
}

// Get single group details
else if ($method === 'GET' && $action === 'get' && isset($_GET['id'])) {
    $groupId = $_GET['id'];
    
    try {
        // Get group info
        $stmt = $db->prepare("
            SELECT g.*, u.username as creator_name,
                   (SELECT role FROM group_members WHERE group_id = g.id AND user_id = ?) as user_role
            FROM groups g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE g.id = ?
        ");
        $stmt->execute([$userId, $groupId]);
        $group = $stmt->fetch();
        
        if (!$group) {
            echo json_encode(['success' => false, 'message' => 'Group not found']);
            exit;
        }
        
        // Get members
        $stmt = $db->prepare("
            SELECT gm.*, u.username, u.email
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
            ORDER BY gm.role DESC, gm.joined_at ASC
        ");
        $stmt->execute([$groupId]);
        $members = $stmt->fetchAll();
        
        // Get pending requests (only if user is admin)
        // Only show user-initiated requests (not admin invitations)
        $pendingRequests = [];
        if ($group['user_role'] === 'ADMIN') {
            $stmt = $db->prepare("
                SELECT gjr.*, u.username, u.email
                FROM group_join_requests gjr
                JOIN users u ON gjr.user_id = u.id
                WHERE gjr.group_id = ? AND gjr.status = 'pending' AND gjr.requested_by IS NULL
                ORDER BY gjr.requested_at DESC
            ");
            $stmt->execute([$groupId]);
            $pendingRequests = $stmt->fetchAll();
        }
        
        $group['members'] = $members;
        $group['pending_requests'] = $pendingRequests;
        
        echo json_encode(['success' => true, 'group' => $group]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch group: ' . $e->getMessage()]);
    }
}

// Create new group
else if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Group name is required']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Create group
        $stmt = $db->prepare("INSERT INTO groups (name, description, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $userId]);
        $groupId = $db->lastInsertId();
        
        // Add creator as admin member
        $stmt = $db->prepare("INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, 'ADMIN', 'active')");
        $stmt->execute([$groupId, $userId]);
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Group created successfully', 'group_id' => $groupId]);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to create group: ' . $e->getMessage()]);
    }
}

// Send join request
else if ($method === 'POST' && $action === 'join-request') {
    $data = json_decode(file_get_contents('php://input'), true);
    $groupId = $data['group_id'] ?? 0;
    
    if ($groupId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        exit;
    }
    
    try {
        // Check if group exists
        $stmt = $db->prepare("SELECT id FROM groups WHERE id = ?");
        $stmt->execute([$groupId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Group not found']);
            exit;
        }
        
        // Check if user is already a member
        $stmt = $db->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You are already a member of this group']);
            exit;
        }
        
        // Check if there's already a pending request
        $stmt = $db->prepare("SELECT id FROM group_join_requests WHERE group_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$groupId, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You already have a pending request for this group']);
            exit;
        }
        
        // Create join request (user-initiated, so requested_by is NULL)
        $stmt = $db->prepare("INSERT INTO group_join_requests (group_id, user_id, status, requested_by) VALUES (?, ?, 'pending', NULL)");
        $stmt->execute([$groupId, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Join request sent successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send join request: ' . $e->getMessage()]);
    }
}

// Accept or reject join request
else if ($method === 'POST' && $action === 'respond-request') {
    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = $data['request_id'] ?? 0;
    $response = $data['response'] ?? ''; // 'accepted' or 'rejected'
    
    if ($requestId <= 0 || !in_array($response, ['accepted', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Get request details and verify user is admin
        $stmt = $db->prepare("
            SELECT gjr.*, g.id as group_id
            FROM group_join_requests gjr
            JOIN groups g ON gjr.group_id = g.id
            WHERE gjr.id = ? AND gjr.status = 'pending'
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            $db->rollBack();
            exit;
        }
        
        // Check if current user is admin of the group
        $stmt = $db->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'ADMIN'");
        $stmt->execute([$request['group_id'], $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Only group admins can respond to requests']);
            $db->rollBack();
            exit;
        }
        
        $status = $response === 'accepted' ? 'accepted' : 'rejected';
        
        // Update request status
        $stmt = $db->prepare("UPDATE group_join_requests SET status = ?, responded_at = NOW(), responded_by = ? WHERE id = ?");
        $stmt->execute([$status, $userId, $requestId]);
        
        // If accepted, add user to group members
        if ($response === 'accepted') {
            // Check if already a member (shouldn't happen, but safety check)
            $stmt = $db->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$request['group_id'], $request['user_id']]);
            if (!$stmt->fetch()) {
                $stmt = $db->prepare("INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, 'MEMBER', 'active')");
                $stmt->execute([$request['group_id'], $request['user_id']]);
            }
        }
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Request ' . $response . ' successfully']);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to process request: ' . $e->getMessage()]);
    }
}

// Delete group (only by creator/admin)
else if ($method === 'POST' && $action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $groupId = $data['group_id'] ?? 0;
    
    if ($groupId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
        exit;
    }
    
    try {
        // Check if user is the creator or admin
        $stmt = $db->prepare("
            SELECT id FROM groups 
            WHERE id = ? AND (created_by = ? OR id IN (SELECT group_id FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'ADMIN'))
        ");
        $stmt->execute([$groupId, $userId, $groupId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this group']);
            exit;
        }
        
        // Delete group (cascade will handle members and requests)
        $stmt = $db->prepare("DELETE FROM groups WHERE id = ?");
        $stmt->execute([$groupId]);
        
        echo json_encode(['success' => true, 'message' => 'Group deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete group: ' . $e->getMessage()]);
    }
}

// Get available groups to join (groups user is not a member of)
else if ($method === 'GET' && $action === 'available') {
    try {
        $stmt = $db->prepare("
            SELECT g.*, u.username as creator_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND status = 'active') as member_count
            FROM groups g
            LEFT JOIN users u ON g.created_by = u.id
            WHERE g.id NOT IN (
                SELECT group_id FROM group_members WHERE user_id = ?
            )
            AND g.id NOT IN (
                SELECT group_id FROM group_join_requests WHERE user_id = ? AND status = 'pending'
            )
            ORDER BY g.created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        $groups = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'groups' => $groups]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch available groups: ' . $e->getMessage()]);
    }
}

// Get available users to invite (users not in group and no pending request)
else if ($method === 'GET' && $action === 'available-users' && isset($_GET['group_id'])) {
    $groupId = $_GET['group_id'];
    
    try {
        // Check if current user is admin
        $stmt = $db->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'ADMIN'");
        $stmt->execute([$groupId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Only group admins can view available users']);
            exit;
        }
        
        // Get users who are not members and don't have pending requests
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email
            FROM users u
            WHERE u.id != ?
            AND u.id NOT IN (
                SELECT user_id FROM group_members WHERE group_id = ?
            )
            AND u.id NOT IN (
                SELECT user_id FROM group_join_requests WHERE group_id = ? AND status = 'pending'
            )
            ORDER BY u.username ASC
        ");
        $stmt->execute([$userId, $groupId, $groupId]);
        $users = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'users' => $users]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch available users: ' . $e->getMessage()]);
    }
}

// Invite user by email/username or user_id (creates a join request on behalf of the user)
else if ($method === 'POST' && $action === 'invite') {
    $data = json_decode(file_get_contents('php://input'), true);
    $groupId = $data['group_id'] ?? 0;
    $userIdentifier = trim($data['user_identifier'] ?? '');
    $targetUserId = $data['user_id'] ?? 0;
    
    if ($groupId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Group ID is required']);
        exit;
    }
    
    try {
        // Check if current user is admin
        $stmt = $db->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'ADMIN'");
        $stmt->execute([$groupId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Only group admins can invite users']);
            exit;
        }
        
        // If user_id is provided, use it directly; otherwise find by email/username
        if ($targetUserId > 0) {
            // Validate user exists
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
        } else if (!empty($userIdentifier)) {
            // Find user by email or username
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$userIdentifier, $userIdentifier]);
            $targetUser = $stmt->fetch();
            
            if (!$targetUser) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            $targetUserId = $targetUser['id'];
        } else {
            echo json_encode(['success' => false, 'message' => 'User ID or user identifier is required']);
            exit;
        }
        
        // Check if already a member
        $stmt = $db->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $targetUserId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'User is already a member of this group']);
            exit;
        }
        
        // Check if there's already a pending request
        $stmt = $db->prepare("SELECT id FROM group_join_requests WHERE group_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$groupId, $targetUserId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'User already has a pending request']);
            exit;
        }
        
        // Create join request (invitation by admin, so requested_by is the admin's user_id)
        $stmt = $db->prepare("INSERT INTO group_join_requests (group_id, user_id, status, requested_by) VALUES (?, ?, 'pending', ?)");
        $stmt->execute([$groupId, $targetUserId, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'User invited successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to invite user: ' . $e->getMessage()]);
    }
}

// Get count of user's pending requests
else if ($method === 'GET' && $action === 'my-requests-count') {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM group_join_requests
            WHERE user_id = ? AND status = 'pending' AND requested_by IS NOT NULL
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        echo json_encode(['success' => true, 'count' => (int)$result['count']]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch request count: ' . $e->getMessage()]);
    }
}

// Get user's pending requests (requests sent to user)
else if ($method === 'GET' && $action === 'my-requests') {
    try {
        // Get requests where current user is the target (invited by admin)
        // Only show requests where requested_by IS NOT NULL (admin invitations)
        $stmt = $db->prepare("
            SELECT gjr.*, g.name as group_name, g.description as group_description,
                   u.username as inviter_name
            FROM group_join_requests gjr
            JOIN groups g ON gjr.group_id = g.id
            LEFT JOIN users u ON gjr.requested_by = u.id
            WHERE gjr.user_id = ? AND gjr.status = 'pending' AND gjr.requested_by IS NOT NULL
            ORDER BY gjr.requested_at DESC
        ");
        $stmt->execute([$userId]);
        $requests = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'requests' => $requests]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch requests: ' . $e->getMessage()]);
    }
}

// Accept or reject a request (when user accepts an invitation)
else if ($method === 'POST' && $action === 'accept-invitation') {
    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = $data['request_id'] ?? 0;
    
    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Get request details and verify it belongs to current user
        $stmt = $db->prepare("
            SELECT gjr.*, g.id as group_id
            FROM group_join_requests gjr
            JOIN groups g ON gjr.group_id = g.id
            WHERE gjr.id = ? AND gjr.user_id = ? AND gjr.status = 'pending'
        ");
        $stmt->execute([$requestId, $userId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            $db->rollBack();
            exit;
        }
        
        // Update request status
        $stmt = $db->prepare("UPDATE group_join_requests SET status = 'accepted', responded_at = NOW(), responded_by = ? WHERE id = ?");
        $stmt->execute([$userId, $requestId]);
        
        // Add user to group members
        $stmt = $db->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$request['group_id'], $userId]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, 'MEMBER', 'active')");
            $stmt->execute([$request['group_id'], $userId]);
        }
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Invitation accepted successfully']);
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to accept invitation: ' . $e->getMessage()]);
    }
}

// Reject an invitation
else if ($method === 'POST' && $action === 'reject-invitation') {
    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = $data['request_id'] ?? 0;
    
    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit;
    }
    
    try {
        // Get request and verify it belongs to current user
        $stmt = $db->prepare("
            SELECT id FROM group_join_requests 
            WHERE id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt->execute([$requestId, $userId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            exit;
        }
        
        // Update request status
        $stmt = $db->prepare("UPDATE group_join_requests SET status = 'rejected', responded_at = NOW(), responded_by = ? WHERE id = ?");
        $stmt->execute([$userId, $requestId]);
        
        echo json_encode(['success' => true, 'message' => 'Invitation rejected']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to reject invitation: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

