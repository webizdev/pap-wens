<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        $result = $db->query("SELECT * FROM papwens_testimonials ORDER BY id ASC");
        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['id'] = (int)$row['id'];
                $row['stars'] = (int)$row['stars'];
                $items[] = $row;
            }
        }
        sendJSON($items);
        break;

    case 'POST':
        $body = getBody();
        $name = $db->real_escape_string($body['name'] ?? '');
        $text = $db->real_escape_string($body['text'] ?? '');
        $stars = (int)($body['stars'] ?? 5);
        $success = $db->query("INSERT INTO papwens_testimonials (name, text, stars) VALUES ('$name', '$text', $stars)");
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true, 'id' => $db->insert_id]);
        break;

    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $body = getBody();
        $name = $db->real_escape_string($body['name'] ?? '');
        $text = $db->real_escape_string($body['text'] ?? '');
        $stars = (int)($body['stars'] ?? 5);
        $success = $db->query("UPDATE papwens_testimonials SET name='$name', text='$text', stars=$stars WHERE id=$id");
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true]);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $success = $db->query("DELETE FROM papwens_testimonials WHERE id=$id");
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true]);
        break;

    default:
        sendJSON(['error' => 'Method not allowed'], 405);
}
$db->close();
