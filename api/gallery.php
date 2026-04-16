<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        $result = $db->query("SELECT * FROM papwens_gallery_images ORDER BY id ASC");
        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['id'] = (int)$row['id'];
                $items[] = $row;
            }
        }
        sendJSON($items);
        break;

    case 'POST':
        $body = getBody();
        $title = $db->real_escape_string($body['title'] ?? 'Untitled');
        $category = $db->real_escape_string($body['category'] ?? 'Other');
        $url = $db->real_escape_string($body['url'] ?? '');
        $success = $db->query("INSERT INTO papwens_gallery_images (title, category, url) VALUES ('$title', '$category', '$url')");
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true, 'id' => $db->insert_id]);
        break;

    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $body = getBody();
        $title = $db->real_escape_string($body['title'] ?? '');
        $category = $db->real_escape_string($body['category'] ?? '');
        $url = $db->real_escape_string($body['url'] ?? '');
        $success = $db->query("UPDATE papwens_gallery_images SET title='$title', category='$category', url='$url' WHERE id=$id");
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true]);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $success = $db->query("DELETE FROM papwens_gallery_images WHERE id=$id");
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true]);
        break;

    default:
        sendJSON(['error' => 'Method not allowed'], 405);
}
$db->close();
