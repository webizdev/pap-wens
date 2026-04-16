<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['error' => 'Method not allowed'], 405);
}

// Fix path: api/ is inside public_html/api/, so uploads/ is one level up (../uploads/)
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (empty($_FILES['file'])) {
    sendJSON(['error' => 'No file uploaded'], 400);
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
    sendJSON(['error' => 'File type not allowed. Only JPG, PNG, GIF, WEBP allowed.'], 400);
}

// Prepare destination (always .webp for images)
$isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
$targetExt = $isImage ? 'webp' : $ext;
$filename = time() . '-' . mt_rand(100000, 999999) . '.' . $targetExt;
$destination = $uploadDir . $filename;

if ($isImage && extension_loaded('gd')) {
    // ---------------------------------------------------------
    // PHP IMAGE OPTIMIZATION (Convert to WebP + Compress)
    // ---------------------------------------------------------
    
    // 1. Create image resource from temp file
    $img = null;
    if ($ext === 'jpg' || $ext === 'jpeg') $img = imagecreatefromjpeg($file['tmp_name']);
    elseif ($ext === 'png') {
        $img = imagecreatefrompng($file['tmp_name']);
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);
    }
    elseif ($ext === 'webp') $img = imagecreatefromwebp($file['tmp_name']);
    
    if ($img) {
        $width = imagesx($img);
        $height = imagesy($img);
        
        // 2. Resize if too large (Max 1920px width)
        $maxSize = 1920;
        if ($width > $maxSize) {
            $newWidth = $maxSize;
            $newHeight = floor($height * ($maxSize / $width));
            $tmpImg = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency for PNG conversion
            imagealphablending($tmpImg, false);
            imagesavealpha($tmpImg, true);
            
            imagecopyresampled($tmpImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($img);
            $img = $tmpImg;
        }

        // 3. Save as WebP with quality 80
        // This will significantly reduce size, almost always < 1MB
        if (imagewebp($img, $destination, 80)) {
            imagedestroy($img);
            $url = '/uploads/' . $filename;
            sendJSON(['success' => true, 'url' => $url, 'optimized' => true]);
        }
        imagedestroy($img);
    }
}

// Fallback for non-images or if GD fails
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    sendJSON(['error' => 'Failed to save file. Check folder permissions.', 'path' => $uploadDir], 500);
}

$url = '/uploads/' . $filename;
sendJSON(['success' => true, 'url' => $url]);
