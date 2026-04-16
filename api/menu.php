<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        $result = $db->query("SELECT * FROM papwens_menu_items ORDER BY id ASC");
        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = [
                    'id'           => (int)$row['id'],
                    'name'         => $row['name'],
                    'description'  => $row['description'],
                    'price'        => $row['price'],
                    'numericPrice' => (int)$row['numeric_price'],
                    'category'     => $row['category'],
                    'image'        => $row['image'],
                    'badge'        => $row['badge'],
                    'stock'        => (int)$row['stock'],
                ];
            }
        }
        sendJSON($items);
        break;

    case 'POST':
        $body = getBody();
        $name = $db->real_escape_string(isset($body['name']) ? $body['name'] : '');
        $desc = $db->real_escape_string(isset($body['description']) ? $body['description'] : '');
        $numericPrice = (int)(isset($body['numericPrice']) ? $body['numericPrice'] : 0);
        $price = 'Rp ' . number_format($numericPrice, 0, ',', '.');
        $category = $db->real_escape_string(isset($body['category']) ? $body['category'] : '');
        $image = $db->real_escape_string(isset($body['image']) ? $body['image'] : '');
        $badge = $db->real_escape_string(isset($body['badge']) ? $body['badge'] : '');
        $stock = (int)(isset($body['stock']) ? $body['stock'] : 100);

        $sql = "INSERT INTO papwens_menu_items (name, description, price, numeric_price, category, image, badge, stock)
                VALUES ('$name', '$desc', '$price', $numericPrice, '$category', '$image', " . ($badge ? "'$badge'" : "NULL") . ", $stock)";
        $success = $db->query($sql);
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true, 'id' => $db->insert_id]);
        break;

    case 'PUT':
        $id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $body = getBody();
        $name = $db->real_escape_string(isset($body['name']) ? $body['name'] : '');
        $desc = $db->real_escape_string(isset($body['description']) ? $body['description'] : '');
        $numericPrice = (int)(isset($body['numericPrice']) ? $body['numericPrice'] : 0);
        $price = 'Rp ' . number_format($numericPrice, 0, ',', '.');
        $category = $db->real_escape_string(isset($body['category']) ? $body['category'] : '');
        $image = $db->real_escape_string(isset($body['image']) ? $body['image'] : '');
        $badge = $db->real_escape_string(isset($body['badge']) ? $body['badge'] : '');
        $stock = (int)(isset($body['stock']) ? $body['stock'] : 100);

        // Handle toggle sold out (only stock sent)
        if (!$name && isset($body['stock'])) {
            $success = $db->query("UPDATE papwens_menu_items SET stock=$stock WHERE id=$id");
            if (!$success) {
                sendJSON(['error' => 'Database error: ' . $db->error], 500);
            }
            sendJSON(['success' => true]);
        }

        $sql = "UPDATE papwens_menu_items SET
                    name='$name', description='$desc', price='$price',
                    numeric_price=$numericPrice, category='$category',
                    image='$image', badge=" . ($badge ? "'$badge'" : "NULL") . ", stock=$stock
                WHERE id=$id";
        $success = $db->query($sql);
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true]);
        break;

    case 'DELETE':
        $id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $success = $db->query("DELETE FROM papwens_menu_items WHERE id=$id");
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true]);
        break;

    default:
        sendJSON(['error' => 'Method not allowed'], 405);
}
$db->close();
