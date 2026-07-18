<?php
/**
 * FILE: api/api_bookings.php
 * FUNGSI: API publik untuk mengambil data reservasi/booking aktif hari ini dan mendatang
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    
    // Ambil reservasi yang aktif (status 'confirmed' atau 'pending')
    // Mulai dari hari ini, diurutkan berdasarkan tanggal dan waktu_mulai secara menaik (ascending)
    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.tanggal,
            r.waktu_mulai,
            r.waktu_selesai,
            r.durasi_jam,
            r.status,
            l.nama AS lapangan_nama,
            c.nama AS cabang_nama,
            u.nama AS user_nama
        FROM reservasi r
        JOIN lapangan l ON r.lapangan_id = l.id
        JOIN cabang c ON l.cabang_id = c.id
        JOIN users u ON r.user_id = u.id
        WHERE r.status IN ('confirmed', 'pending')
          AND r.tanggal >= CURDATE()
        ORDER BY r.tanggal ASC, r.waktu_mulai ASC
        LIMIT 10
    ");
    
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format tanggal dan nama user untuk disamarkan/dipersingkat demi privasi
    $formattedBookings = [];
    foreach ($bookings as $b) {
        // Samarkan nama belakang untuk privasi
        $namaParts = explode(' ', trim($b['user_nama']));
        $maskedNama = $namaParts[0];
        if (count($namaParts) > 1) {
            $maskedNama .= ' ' . substr($namaParts[1], 0, 1) . '.';
        }
        
        // Format tanggal (misal: "18 Jul 2026")
        $date = new DateTime($b['tanggal']);
        $formattedDate = $date->format('d M Y');
        
        $formattedBookings[] = [
            'id' => $b['id'],
            'tanggal_raw' => $b['tanggal'],
            'tanggal_formatted' => $formattedDate,
            'waktu_mulai' => substr($b['waktu_mulai'], 0, 5),
            'waktu_selesai' => substr($b['waktu_selesai'], 0, 5),
            'durasi_jam' => $b['durasi_jam'],
            'status' => $b['status'],
            'lapangan_nama' => $b['lapangan_nama'],
            'cabang_nama' => $b['cabang_nama'],
            'user_nama' => $maskedNama
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'bookings' => $formattedBookings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengambil data booking: ' . $e->getMessage()
    ]);
}
?>
