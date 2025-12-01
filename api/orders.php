<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/NotificationService.php';

// Clear any output that might have been generated
ob_clean();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    ob_clean();
    http_response_code(401);
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
    // Include the username of the user who created the order
    $query = "SELECT DISTINCT o.*, g.name as group_name, u.username AS added_by 
          FROM orders o 
          LEFT JOIN groups g ON o.group_id = g.id 
          LEFT JOIN group_members gm ON o.group_id = gm.group_id AND gm.user_id = ? AND gm.status = 'active'
          LEFT JOIN users u ON o.user_id = u.id
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
            SELECT o.*, g.name as group_name, u.username AS added_by
            FROM orders o
            LEFT JOIN groups g ON o.group_id = g.id
            LEFT JOIN group_members gm ON o.group_id = gm.group_id AND gm.user_id = ? AND gm.status = 'active'
            LEFT JOIN users u ON o.user_id = u.id
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
    $tags = $data['tags'] ?? [];
    
    if (empty($orderNumber) || empty($orderDate) || empty($orderTime)) {
        ob_clean();
        http_response_code(400);
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
                ob_clean();
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have access to this group']);
                exit;
            }
        } catch (PDOException $e) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to validate group: ' . $e->getMessage()]);
            exit;
        }
    }
    
    try {
        // Check if tags column exists, if not, use NULL
        $tagsValue = null;
        if (is_array($tags) && count($tags) > 0) {
            $tagsValue = json_encode($tags, JSON_UNESCAPED_UNICODE);
        }
        
        // Try to insert with tags column first
        try {
            $stmt = $db->prepare("INSERT INTO orders (order_number, order_date, order_time, status, user_id, group_id, tags) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$orderNumber, $orderDate, $orderTime, $status, $userId, $groupId, $tagsValue]);
        } catch (PDOException $e) {
            // If tags column doesn't exist, try without it
            if (strpos($e->getMessage(), 'tags') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $db->prepare("INSERT INTO orders (order_number, order_date, order_time, status, user_id, group_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$orderNumber, $orderDate, $orderTime, $status, $userId, $groupId]);
            } else {
                throw $e;
            }
        }
        
        $orderId = $db->lastInsertId();
        
        // Automatically process notifications for this order
        // Wrap in try-catch to prevent notification errors from breaking order creation
        $notificationResult = null;
        try {
            $notificationResult = $notificationService->processOrderNotifications($orderId, $userId);
        } catch (Exception $notifError) {
            // Log error but don't fail order creation
            error_log("Notification processing error: " . $notifError->getMessage());
        }
        
        $response = [
            'success' => true, 
            'message' => 'Order created successfully', 
            'order_id' => $orderId
        ];
        
        // Add notification info to response if available
        if ($notificationResult && isset($notificationResult['autoSent']) && $notificationResult['autoSent']) {
            $response['notification'] = [
                'sent' => true,
                'message' => $notificationResult['message'] ?? 'Notification sent',
                'reminderNumber' => $notificationResult['reminderNumber'] ?? null
            ];
        } elseif ($notificationResult && isset($notificationResult['scheduled']) && $notificationResult['scheduled']) {
            $response['notification'] = [
                'scheduled' => true,
                'message' => $notificationResult['message'] ?? 'Notification scheduled'
            ];
        }
        
        // Clear output buffer and send JSON
        ob_clean();
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        exit;
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
    $tags = $data['tags'] ?? [];
    
    if ($orderId <= 0) {
        ob_clean();
        http_response_code(400);
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
                ob_clean();
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have access to this group']);
                exit;
            }
        } catch (PDOException $e) {
            ob_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to validate group: ' . $e->getMessage()]);
            exit;
        }
    }
    
    try {
        // Check if tags column exists, if not, use NULL
        $tagsValue = null;
        if (is_array($tags) && count($tags) > 0) {
            $tagsValue = json_encode($tags, JSON_UNESCAPED_UNICODE);
        }
        
        // Try to update with tags column first
        try {
            $stmt = $db->prepare("UPDATE orders SET order_number = ?, order_date = ?, order_time = ?, status = ?, group_id = ?, tags = ? WHERE id = ? AND user_id = ? AND is_deleted = 0");
            $stmt->execute([$orderNumber, $orderDate, $orderTime, $status, $groupId, $tagsValue, $orderId, $userId]);
        } catch (PDOException $e) {
            // If tags column doesn't exist, try without it
            if (strpos($e->getMessage(), 'tags') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $db->prepare("UPDATE orders SET order_number = ?, order_date = ?, order_time = ?, status = ?, group_id = ? WHERE id = ? AND user_id = ? AND is_deleted = 0");
                $stmt->execute([$orderNumber, $orderDate, $orderTime, $status, $groupId, $orderId, $userId]);
            } else {
                throw $e;
            }
        }
        
        // Automatically process notifications for the updated order
        // Wrap in try-catch to prevent notification errors from breaking order update
        $notificationResult = null;
        try {
            $notificationResult = $notificationService->processOrderNotifications($orderId, $userId);
        } catch (Exception $notifError) {
            // Log error but don't fail order update
            error_log("Notification processing error: " . $notifError->getMessage());
        }
        
        $response = [
            'success' => true, 
            'message' => 'Order updated successfully'
        ];
        
        // Add notification info to response if available
        if ($notificationResult && isset($notificationResult['autoSent']) && $notificationResult['autoSent']) {
            $response['notification'] = [
                'sent' => true,
                'message' => $notificationResult['message'] ?? 'Notification sent',
                'reminderNumber' => $notificationResult['reminderNumber'] ?? null
            ];
        } elseif ($notificationResult && isset($notificationResult['scheduled']) && $notificationResult['scheduled']) {
            $response['notification'] = [
                'scheduled' => true,
                'message' => $notificationResult['message'] ?? 'Notification scheduled'
            ];
        }
        
        // Clear output buffer and send JSON
        ob_clean();
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update order: ' . $e->getMessage()]);
        exit;
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        exit;
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
    $groupId = $_GET['group_id'] ?? '';
    
    try {
        // Build query to get orders with tags
        $query = "SELECT o.order_date, o.tags
                  FROM orders o 
                  LEFT JOIN groups g ON o.group_id = g.id 
                  LEFT JOIN group_members gm ON o.group_id = gm.group_id AND gm.user_id = ? AND gm.status = 'active'
                  WHERE o.is_deleted = 0
                  AND MONTH(o.order_date) = ?
                  AND YEAR(o.order_date) = ?
                  AND (
                      o.user_id = ?
                      OR g.created_by = ?
                      OR gm.id IS NOT NULL
                  )";
        
        $params = [$userId, $month, $year, $userId, $userId];
        
        // Add group filter if specified
        if (!empty($groupId)) {
            $query .= " AND o.group_id = ?";
            $params[] = $groupId;
        }
        
        $query .= " ORDER BY o.order_date";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        
        // Group by date and collect tags (max 2 per date)
        $calendar = [];
        $dateTags = [];
        
        foreach ($orders as $order) {
            $date = $order['order_date'];
            
            // Initialize date entry if not exists
            if (!isset($calendar[$date])) {
                $calendar[$date] = ['order_date' => $date, 'count' => 0, 'tags' => []];
            }
            
            $calendar[$date]['count']++;
            
            // Collect tags (max 2 per date)
            if (!empty($order['tags']) && count($calendar[$date]['tags']) < 2) {
                try {
                    $tags = json_decode($order['tags'], true);
                    if (is_array($tags)) {
                        foreach ($tags as $tag) {
                            if (count($calendar[$date]['tags']) >= 2) break;
                            
                            $tagName = is_array($tag) && isset($tag['name']) ? $tag['name'] : (is_string($tag) ? $tag : null);
                            $tagColor = is_array($tag) && isset($tag['color']) ? $tag['color'] : '#4CAF50';
                            
                            if ($tagName) {
                                // Check if tag already exists for this date
                                $exists = false;
                                foreach ($calendar[$date]['tags'] as $existingTag) {
                                    if ($existingTag['name'] === $tagName) {
                                        $exists = true;
                                        break;
                                    }
                                }
                                
                                if (!$exists) {
                                    $calendar[$date]['tags'][] = [
                                        'name' => $tagName,
                                        'color' => $tagColor
                                    ];
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Ignore JSON decode errors
                }
            }
        }
        
        // Convert to indexed array
        $calendar = array_values($calendar);
        
        ob_clean();
        echo json_encode(['success' => true, 'calendar' => $calendar]);
        exit;
    } catch (PDOException $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch calendar data: ' . $e->getMessage()]);
        exit;
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

