<?php
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ── /api/settings/store-status ──────────────────────────────
if (strpos($uri, 'store-status') !== false) {
    if ($method === 'GET') {
        $result = $db->query("SELECT value FROM papwens_settings WHERE `key`='store_status' LIMIT 1");
        $row = $result ? $result->fetch_assoc() : null;
        sendJSON(['status' => (isset($row['value']) && $row['value']) ? $row['value'] : 'open']);
    } elseif ($method === 'PUT' || $method === 'POST') {
        $body = getBody();
        $status = (isset($body['status']) && in_array($body['status'], ['open', 'close'])) ? $body['status'] : 'open';
        $success = $db->query("INSERT INTO papwens_settings (`key`, value) VALUES ('store_status', '$status') ON DUPLICATE KEY UPDATE value='$status'");
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true, 'status' => $status]);
    }
    sendJSON(['error' => 'Method not allowed'], 405);
}

// ── /api/settings/contact ───────────────────────────────────
if (strpos($uri, 'contact') !== false) {
    if ($method === 'GET') {
        $result = $db->query("SELECT * FROM papwens_contacts WHERE id=1 LIMIT 1");
        $row = $result ? $result->fetch_assoc() : null;
        
        if (!$row) {
            sendJSON([
                'address' => 'Jl. Brigadir Jend. Katamso No.31, Cihaur Geulis, Kec. Cibeunying Kidul, Kota Bandung, Jawa Barat 40122',
                'whatsapp' => '6281323331212',
                'email' => 'hello@papwens.com',
                'maps_url' => 'https://maps.app.goo.gl/1v9oEcqmj1yagZEw8',
                'maps_embed' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5219.292621651095!2d107.63164189999999!3d-6.904221899999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e7003e82462b%3A0xec8b572841de1d7d!2sPAPWENS!5e1!3m2!1sen!2sid!4v1776318257633!5m2!1sen!2sid" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
                'socialMedia' => [
                    ['id' => '1', 'platform' => 'Instagram', 'url' => 'https://www.instagram.com/papwens/'],
                    ['id' => '2', 'platform' => 'Facebook', 'url' => 'https://www.facebook.com/#'],
                    ['id' => '1776282817965', 'platform' => 'TikTok', 'url' => 'https://www.tiktok.com/#']
                ]
            ]);
        }
        
        $socialMedia = (isset($row['social_media']) && $row['social_media']) ? json_decode($row['social_media'], true) : [];
        sendJSON([
            'address'     => $row['address'],
            'whatsapp'    => $row['whatsapp'],
            'email'       => $row['email'],
            'mapsUrl'     => isset($row['maps_url']) ? $row['maps_url'] : '',
            'mapsEmbed'   => isset($row['maps_embed']) ? $row['maps_embed'] : '',
            'socialMedia' => $socialMedia,
        ]);
    } elseif ($method === 'PUT' || $method === 'POST') {
        $body = getBody();
        $address     = $db->real_escape_string(isset($body['address']) ? $body['address'] : '');
        $whatsapp    = $db->real_escape_string(isset($body['whatsapp']) ? $body['whatsapp'] : '');
        $email       = $db->real_escape_string(isset($body['email']) ? $body['email'] : '');
        $mapsUrl     = $db->real_escape_string(isset($body['mapsUrl']) ? $body['mapsUrl'] : (isset($body['maps_url']) ? $body['maps_url'] : ''));
        $mapsEmbed   = $db->real_escape_string(isset($body['mapsEmbed']) ? $body['mapsEmbed'] : (isset($body['maps_embed']) ? $body['maps_embed'] : ''));
        $socialMedia = $db->real_escape_string(json_encode(isset($body['socialMedia']) ? $body['socialMedia'] : []));

        $check = $db->query("SELECT id FROM papwens_contacts WHERE id=1 LIMIT 1");
        $success = false;
        if ($check && $check->num_rows > 0) {
            $success = $db->query("UPDATE papwens_contacts SET address='$address', whatsapp='$whatsapp', email='$email', maps_url='$mapsUrl', maps_embed='$mapsEmbed', social_media='$socialMedia' WHERE id=1");
        } else {
            $success = $db->query("INSERT INTO papwens_contacts (id, address, whatsapp, email, maps_url, maps_embed, social_media) VALUES (1, '$address', '$whatsapp', '$email', '$mapsUrl', '$mapsEmbed', '$socialMedia')");
        }

        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true]);
    }
    sendJSON(['error' => 'Method not allowed'], 405);
}

// ── /api/settings/web ───────────────────────────────────────
if (strpos($uri, 'web') !== false) {
    if ($method === 'GET') {
        $result = $db->query("SELECT value FROM papwens_settings WHERE `key`='web_settings' LIMIT 1");
        $row = $result ? $result->fetch_assoc() : null;
        
        if (!$row) {
            sendJSON([
                'siteName' => 'PAPWENS.COM',
                'siteLogo' => '',
                'contactImage' => '',
                'contactTitle' => 'Craving Something Delicious?',
                'contactSubtitle' => 'Pesan langsung via WhatsApp atau kunjungi kami di Jl. Katamso No.31. Kami siap menyajikan yang terbaik untuk Anda.',
                'footerQuote' => 'Good food is the foundation of genuine happiness.',
                'theme' => 'orange-gray'
            ]);
        }
        
        $settings = json_decode($row['value'], true);

        // --- Dynamic Content Injection ---
        // Fetch current contact info to keep web settings in sync
        $contactRes = $db->query("SELECT * FROM papwens_contacts WHERE id=1 LIMIT 1");
        $contact = $contactRes ? $contactRes->fetch_assoc() : null;
        if ($contact) {
            if (isset($settings['contactSubtitle'])) {
                $addrShort = explode(',', $contact['address'])[0];
                $settings['contactSubtitle'] = "Pesan langsung via WhatsApp (" . $contact['whatsapp'] . ") atau kunjungi kami di " . $addrShort . ". Kami siap menyajikan yang terbaik untuk Anda.";
            }
            // Add dynamic contacts to the web settings response for high compatibility
            $settings['dynamic_contact'] = [
                'whatsapp' => $contact['whatsapp'],
                'address'  => $contact['address'],
                'email'    => $contact['email'],
                'mapsUrl'  => isset($contact['maps_url']) ? $contact['maps_url'] : ''
            ];
        }

        sendJSON($settings);
    } elseif ($method === 'PUT' || $method === 'POST') {
        $body = getBody();
        $value = $db->real_escape_string(json_encode($body));
        $success = $db->query("INSERT INTO papwens_settings (`key`, value) VALUES ('web_settings', '$value') ON DUPLICATE KEY UPDATE value='$value'");
        
        if (!$success) {
            sendJSON(['error' => 'Database error: ' . $db->error], 500);
        }
        sendJSON(['success' => true]);
    }
    sendJSON(['error' => 'Method not allowed'], 405);
}

sendJSON(['error' => 'Unknown settings endpoint'], 404);
$db->close();
