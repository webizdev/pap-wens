<?php
/**
 * PAPWENS GitHub Webhook Auto-Deploy
 * 
 * Skrip ini dipanggil oleh GitHub setiap kali ada kode baru yang di-push.
 * Saat dipanggil, server akan otomatis menarik kode terbaru dan
 * memaksa (Force) sinkronisasi agar Web Anda selalu up-to-date!
 */

// Kunci Keamanan Sederhana
$secret_key = 'papwens_rahasia_123';

// Validasi
if (!isset($_GET['secret']) || $_GET['secret'] !== $secret_key) {
    http_response_code(403);
    die('Akses Ditolak: Kunci Secret Salah.');
}

// Proses Bekerja
$output = [];

echo "<pre>";
echo "Memulai Sinkronisasi Paksa (Force Update) dengan GitHub...\n\n";

// 1. Membersihkan modifikasi liar di lokal cPanel (yang menyebabkan gagal Pull)
echo exec('git reset --hard origin/main 2>&1') . "\n";

// 2. Menarik meta terbaru dari Remote GitHub
echo exec('git fetch origin 2>&1') . "\n";

// 3. Menimpa secara paksa semua data lokal dengan branch main 
echo exec('git pull origin main --force 2>&1') . "\n";

echo "\nSemua proses selesai! Sila cek website Anda.";
echo "</pre>";

?>
