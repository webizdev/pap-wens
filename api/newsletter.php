<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        $result = $db->query("SELECT * FROM papwens_newsletter ORDER BY id DESC");
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $items[] = $row;
        }
        sendJSON($items);
        break;

    case 'POST':
        $body = getBody();
        $email = $db->real_escape_string($body['email'] ?? '');
        if (!$email) sendJSON(['error' => 'Email is required'], 400);

        $existing = $db->query("SELECT id FROM papwens_newsletter WHERE email='$email' LIMIT 1");
        if ($existing->num_rows > 0) {
            sendJSON(['success' => false, 'message' => 'Email sudah terdaftar.'], 400);
        }

        $createdAt = date('c');
        $db->query("INSERT INTO papwens_newsletter (email, created_at) VALUES ('$email', '$createdAt')");
        sendJSON(['success' => true, 'message' => 'Berhasil mendaftar newsletter!']);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) sendJSON(['error' => 'ID required'], 400);
        $db->query("DELETE FROM papwens_newsletter WHERE id=$id");
        sendJSON(['success' => true]);
        break;

    default:
        sendJSON(['error' => 'Method not allowed'], 405);
}
$db->close();
