<?php
/**
 * API: Get Event Divisions and Participant Count
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$eventId = intval($_GET['event_id'] ?? 0);
if (!$eventId) {
    jsonResponse(['success' => false, 'message' => 'Event ID is required'], 400);
}

$db = getDB();

try {
    // Check if event exists
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if (!$event) {
        jsonResponse(['success' => false, 'message' => 'Event not found'], 404);
    }
    
    // Get total members in event
    $stmtTotal = $db->prepare("SELECT COUNT(DISTINCT user_id) as total FROM event_members WHERE event_id = ?");
    $stmtTotal->execute([$eventId]);
    $totalMembers = $stmtTotal->fetch()['total'];
    
    // Get divisions and member count per division
    $stmtDivs = $db->prepare("
        SELECT d.id, d.nama_divisi, 
               (SELECT COUNT(*) FROM event_members WHERE division_id = d.id) as total_member
        FROM event_divisions d
        WHERE d.event_id = ?
        ORDER BY d.nama_divisi ASC
    ");
    $stmtDivs->execute([$eventId]);
    $divisions = $stmtDivs->fetchAll();
    
    jsonResponse([
        'success' => true,
        'data' => [
            'event' => [
                'id' => $event['id'],
                'nama_event' => $event['nama_event'],
                'total_members' => $totalMembers
            ],
            'divisions' => $divisions
        ]
    ]);
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
