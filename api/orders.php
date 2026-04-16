<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$uriParts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));

switch ($method) {
    case 'GET':
        if (in_array('track', $uriParts)) {
            $id = (int)end($uriParts);
            $result = $db->query("SELECT id, customer_name, status, created_at, completed_at FROM papwens_orders WHERE id=$id LIMIT 1");
            $row = $result->fetch_assoc();
            if (!$row) sendJSON(['error' => 'Order not found'], 404);
            sendJSON([
                'id' => (int)$row['id'],
                'customerName' => $row['customer_name'],
                'status' => $row['status'],
                'createdAt' => $row['created_at'],
                'completedAt' => $row['completed_at'],
            ]);
        }
        $result = $db->query("SELECT * FROM papwens_orders ORDER BY id DESC");
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int)$row['id'],
                'customerName' => $row['customer_name'],
                'phone' => $row['phone'],
                'serviceType' => $row['service_type'],
                'birthDate' => $row['birth_date'],
                'address' => $row['address'],
                'paymentMethod' => $row['payment_method'],
                'paymentProof' => $row['payment_proof'],
                'items' => $row['items'],
                'totalPrice' => (int)$row['total_price'],
                'status' => $row['status'],
                'createdAt' => $row['created_at'],
                'completedAt' => $row['completed_at'],
            ];
        }
        sendJSON($items);
        break;

    case 'POST':
        $body = getBody();
        if (empty($body['customerName']) || empty($body['phone']) || empty($body['items'])) {
            sendJSON(['error' => 'Missing required fields'], 400);
        }
        $customerName = $db->real_escape_string($body['customerName']);
        $phone = $db->real_escape_string($body['phone']);
        $serviceType = $db->real_escape_string($body['serviceType'] ?? 'Dine In');
        $birthDate = $db->real_escape_string($body['birthDate'] ?? '');
        $address = $db->real_escape_string($body['address'] ?? '');
        $paymentMethod = $db->real_escape_string($body['paymentMethod'] ?? 'QRIS');
        $paymentProof = $db->real_escape_string($body['paymentProof'] ?? '');
        $items = $db->real_escape_string(is_string($body['items']) ? $body['items'] : json_encode($body['items']));
        $totalPrice = (int)($body['totalPrice'] ?? 0);
        $createdAt = date('c');

        $sql = "INSERT INTO papwens_orders (customer_name, phone, service_type, birth_date, address, payment_method, payment_proof, items, total_price, status, created_at)
                VALUES ('$customerName', '$phone', '$serviceType', '$birthDate', '$address', '$paymentMethod', '$paymentProof', '$items', $totalPrice, 'Pending', '$createdAt')";
        $db->query($sql);
        sendJSON(['success' => true, 'id' => $db->insert_id]);
        break;

    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $body = getBody();
        $status = $db->real_escape_string($body['status'] ?? 'Pending');
        $completedAt = $status === 'Completed' ? ", completed_at='" . date('c') . "'" : '';
        $db->query("UPDATE papwens_orders SET status='$status'$completedAt WHERE id=$id");
        sendJSON(['success' => true]);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $db->query("DELETE FROM papwens_orders WHERE id=$id");
        sendJSON(['success' => true]);
        break;

    default:
        sendJSON(['error' => 'Method not allowed'], 405);
}
$db->close();
