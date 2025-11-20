<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/NotificationService.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->connect();

// Initialize notification service
$notificationService = new NotificationService($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Get all orders with filters
if ($method === 'GET' && $action === 'list') {
    $userId = getCurrentUserId();
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $date = $_GET['date'] ?? '';
    $groupId = $_GET['group_id'] ?? '';
    
$query = "SELECT DISTINCT o.*, g.name as group_name 
          FROM orders o 
          LEFT JOIN groups g ON o.group_id = g.id 
          LEFT JOIN group_members gm ON o.group_id = gm.group_id AND gm.user_id = ? AND gm.status = 'active'
          WHERE o.is_deleted = 0
          AND (
              o.user_id = ?
              OR g.created_by = ?
              OR gm.id IS NOT NULL
          )";
$params = [$userId, $userId, $userId];
    
    if (!empty($search)) {
        $query .= " AND o.order_number LIKE ?";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
    }
    
    if (!empty($status)) {
        $query .= " AND o.status = ?";
        $params[] = $status;
    }
    
    if (!empty($date)) {
        $query .= " AND o.order_date = ?";
        $params[] = $date;
    }
    
    if (!empty($groupId)) {
        $query .= " AND o.group_id = ?";
        $params[] = $groupId;
    }
    
    $query .= " ORDER BY o.order_number DESC";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch orders: ' . $e->getMessage()]);
    }
}

// Get single order
else if ($method === 'GET' && $action === 'get' && isset($_GET['id'])) {
    $orderId = $_GET['id'];
    $userId = getCurrentUserId();
    
    try {
        $stmt = $db->prepare("
            SELECT o.*, g.name as group_name
            FROM orders o
            LEFT JOIN groups g ON o.group_id = g.id
            LEFT JOIN group_members gm ON o.group_id = gm.group_id AND gm.user_id = ? AND gm.status = 'active'
            WHERE o.id = ?
              AND o.is_deleted = 0
              AND (
                  o.user_id = ?
                  OR g.created_by = ?
                  OR gm.id IS NOT NULL
              )
        ");
        $stmt->execute([$userId, $orderId, $userId, $userId]);
        $order = $stmt->fetch();
        
        if ($order) {
            echo json_encode(['success' => true, 'order' => $order]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch order: ' . $e->getMessage()]);
    }
}

// Create new order
else if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = getCurrentUserId();
    
    $orderNumber = trim($data['order_number'] ?? '');
    $orderDate = $data['order_date'] ?? '';
    $orderTime = $data['order_time'] ?? '';
    $status = $data['status'] ?? 'Pending';
    $groupId = !empty($data['group_id']) ? intval($data['group_id']) : null;
    
    if (empty($orderNumber) || empty($orderDate) || empty($orderTime)) {
        echo json_encode(['success' => false, 'message' => 'Order number, date and time are required']);
        exit;
    }
    
    // Validate group_id if provided
    if ($groupId !== null) {
        try {
            // Check if user has access to this group
            $stmt = $db->prepare("
                SELECT g.id FROM groups g 
                LEFT JOIN group_members gm ON g.id = gm.group_id 
                WHERE g.id = ? AND (g.created_by = ? OR (gm.user_id = ? AND gm.status = 'active'))
            ");
            $stmt->execute([$groupId, $userId, $userId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You do not have access to this group']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to validate group: ' . $e->getMessage()]);
            exit;
        }
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO orders (order_number, order_date, order_time, status, user_id, group_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$orderNumber, $orderDate, $orderTime, $status, $userId, $groupId]);
        
        $orderId = $db->lastInsertId();
        
        // Automatically process notifications for this order
        $notificationResult = $notificationService->processOrderNotifications($orderId, $userId);
        
        $response = [
            'success' => true, 
            'message' => 'Order created successfully', 
            'order_id' => $orderId
        ];
        
        // Add notification info to response
        if (isset($notificationResult['autoSent']) && $notificationResult['autoSent']) {
            $response['notification'] = [
                'sent' => true,
                'message' => $notificationResult['message'],
                'reminderNumber' => $notificationResult['reminderNumber'] ?? null
            ];
        } elseif (isset($notificationResult['scheduled']) && $notificationResult['scheduled']) {
            $response['notification'] = [
                'scheduled' => true,
                'message' => $notificationResult['message']
            ];
        }
        
        echo json_encode($response);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()]);
    }
}

// Update order
else if ($method === 'POST' && $action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = getCurrentUserId();
    
    $orderId = $data['id'] ?? 0;
    $orderNumber = trim($data['order_number'] ?? '');
    $orderDate = $data['order_date'] ?? '';
    $orderTime = $data['order_time'] ?? '';
    $status = $data['status'] ?? 'Pending';
    $groupId = !empty($data['group_id']) ? intval($data['group_id']) : null;
    
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit;
    }
    
    // Validate group_id if provided
    if ($groupId !== null) {
        try {
            // Check if user has access to this group
            $stmt = $db->prepare("
                SELECT g.id FROM groups g 
                LEFT JOIN group_members gm ON g.id = gm.group_id 
                WHERE g.id = ? AND (g.created_by = ? OR (gm.user_id = ? AND gm.status = 'active'))
            ");
            $stmt->execute([$groupId, $userId, $userId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You do not have access to this group']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to validate group: ' . $e->getMessage()]);
            exit;
        }
    }
    
    try {
        $stmt = $db->prepare("UPDATE orders SET order_number = ?, order_date = ?, order_time = ?, status = ?, group_id = ? WHERE id = ? AND user_id = ? AND is_deleted = 0");
        $stmt->execute([$orderNumber, $orderDate, $orderTime, $status, $groupId, $orderId, $userId]);
        
        // Automatically process notifications for the updated order
        $notificationResult = $notificationService->processOrderNotifications($orderId, $userId);
        
        $response = [
            'success' => true, 
            'message' => 'Order updated successfully'
        ];
        
        // Add notification info to response
        if (isset($notificationResult['autoSent']) && $notificationResult['autoSent']) {
            $response['notification'] = [
                'sent' => true,
                'message' => $notificationResult['message'],
                'reminderNumber' => $notificationResult['reminderNumber'] ?? null
            ];
        } elseif (isset($notificationResult['scheduled']) && $notificationResult['scheduled']) {
            $response['notification'] = [
                'scheduled' => true,
                'message' => $notificationResult['message']
            ];
        }
        
        echo json_encode($response);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update order: ' . $e->getMessage()]);
    }
}

// Update order status only
else if ($method === 'POST' && $action === 'update-status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = getCurrentUserId();
    
    $orderId = $data['id'] ?? 0;
    $status = $data['status'] ?? '';
    
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ? AND user_id = ? AND is_deleted = 0");
        $stmt->execute([$status, $orderId, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()]);
    }
}

// Soft Delete order (set is_deleted = 1)
else if ($method === 'POST' && $action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = getCurrentUserId();
    $orderId = $data['id'] ?? 0;
    
    try {
        $stmt = $db->prepare("UPDATE orders SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        
        echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete order: ' . $e->getMessage()]);
    }
}

// Soft Delete multiple orders (set is_deleted = 1)
else if ($method === 'POST' && $action === 'delete-multiple') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = getCurrentUserId();
    $orderIds = $data['ids'] ?? [];
    
    if (empty($orderIds)) {
        echo json_encode(['success' => false, 'message' => 'No orders selected']);
        exit;
    }
    
    try {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $db->prepare("UPDATE orders SET is_deleted = 1 WHERE id IN ($placeholders) AND user_id = ?");
        $params = array_merge($orderIds, [$userId]);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Orders deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete orders: ' . $e->getMessage()]);
    }
}

// Get calendar data (orders by date)
else if ($method === 'GET' && $action === 'calendar') {
    $userId = getCurrentUserId();
    $month = $_GET['month'] ?? date('m');
    $year = $_GET['year'] ?? date('Y');
    
    try {
        $stmt = $db->prepare("SELECT order_date, COUNT(*) as count FROM orders WHERE user_id = ? AND is_deleted = 0 AND MONTH(order_date) = ? AND YEAR(order_date) = ? GROUP BY order_date");
        $stmt->execute([$userId, $month, $year]);
        $calendar = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'calendar' => $calendar]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch calendar data: ' . $e->getMessage()]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

