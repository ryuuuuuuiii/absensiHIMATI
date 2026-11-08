<?php

/**
 * Backend CRUD for Event Management
 * Absensi HIMA TI Politeknik Hasnur
 */

require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../includes/functions.php';

// Cek autentikasi admin
if (! isAdmin()) {
    setFlash('danger', 'Akses ditolak.');
    redirect(BASE_URL.'/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL.'/admin/events.php');
}

// Validasi CSRF
if (! validateCSRF($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Token keamanan tidak valid.');
    redirect(BASE_URL.'/admin/events.php');
}

$db = getDB();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        // ==========================================
        // EVENTS CRUD
        // ==========================================
        case 'add_event':
            $nama = sanitize($_POST['nama_event']);
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');

            if (empty($nama)) {
                throw new Exception('Nama event wajib diisi.');
            }

            $stmt = $db->prepare("INSERT INTO events (nama_event, deskripsi, status) VALUES (?, ?, 'active')");
            $stmt->execute([$nama, $deskripsi]);

            setFlash('success', 'Event baru berhasil ditambahkan.');
            redirect(BASE_URL.'/admin/events.php');
            break;

        case 'edit_event':
            $id = intval($_POST['id']);
            $nama = sanitize($_POST['nama_event']);
            $deskripsi = sanitize($_POST['deskripsi'] ?? '');

            if (empty($nama)) {
                throw new Exception('Nama event wajib diisi.');
            }

            $stmt = $db->prepare('UPDATE events SET nama_event = ?, deskripsi = ? WHERE id = ?');
            $stmt->execute([$nama, $deskripsi, $id]);

            setFlash('success', 'Detail event berhasil diperbarui.');
            redirect(BASE_URL.'/admin/events.php');
            break;

        case 'archive_event':
            $id = intval($_POST['id']);
            $stmt = $db->prepare("UPDATE events SET status = 'archived' WHERE id = ?");
            $stmt->execute([$id]);

            setFlash('success', 'Event berhasil diarsipkan.');
            redirect(BASE_URL.'/admin/events.php');
            break;

        case 'restore_event':
            $id = intval($_POST['id']);
            $stmt = $db->prepare("UPDATE events SET status = 'active' WHERE id = ?");
            $stmt->execute([$id]);

            setFlash('success', 'Event berhasil diaktifkan kembali.');
            redirect(BASE_URL.'/admin/events.php');
            break;

        case 'delete_event':
            $id = intval($_POST['id']);
            $stmt = $db->prepare('DELETE FROM events WHERE id = ?');
            $stmt->execute([$id]);

            setFlash('success', 'Event berhasil dihapus permanen.');
            redirect(BASE_URL.'/admin/events.php');
            break;

            // ==========================================
            // DIVISIONS CRUD
            // ==========================================
        case 'add_division':
            $eventId = intval($_POST['event_id']);
            $nama = sanitize($_POST['nama_divisi']);

            if (empty($nama)) {
                throw new Exception('Nama divisi wajib diisi.');
            }

            $stmt = $db->prepare('INSERT INTO event_divisions (event_id, nama_divisi) VALUES (?, ?)');
            $stmt->execute([$eventId, $nama]);

            setFlash('success', 'Divisi berhasil ditambahkan.');
            redirect(BASE_URL.'/admin/event_detail.php?id='.$eventId);
            break;

        case 'edit_division':
            $id = intval($_POST['id']);
            $eventId = intval($_POST['event_id']);
            $nama = sanitize($_POST['nama_divisi']);

            if (empty($nama)) {
                throw new Exception('Nama divisi wajib diisi.');
            }

            $stmt = $db->prepare('UPDATE event_divisions SET nama_divisi = ? WHERE id = ? AND event_id = ?');
            $stmt->execute([$nama, $id, $eventId]);

            setFlash('success', 'Divisi berhasil diperbarui.');
            redirect(BASE_URL.'/admin/event_detail.php?id='.$eventId);
            break;

        case 'delete_division':
            $id = intval($_POST['id']);
            $eventId = intval($_POST['event_id']);

            $stmt = $db->prepare('DELETE FROM event_divisions WHERE id = ? AND event_id = ?');
            $stmt->execute([$id, $eventId]);

            setFlash('success', 'Divisi berhasil dihapus.');
            redirect(BASE_URL.'/admin/event_detail.php?id='.$eventId);
            break;

            // ==========================================
            // MEMBERS CRUD
            // ==========================================
        case 'add_member':
            $eventId = intval($_POST['event_id']);
            $divId = intval($_POST['division_id']);
            $userIds = $_POST['user_ids'] ?? [];
            $jabatan = sanitize($_POST['jabatan_panitia'] ?? 'Anggota');

            if (empty($userIds) || ! is_array($userIds)) {
                throw new Exception('Pilih minimal 1 anggota.');
            }
            if (empty($jabatan)) {
                $jabatan = 'Anggota';
            }

            // Begin transaction
            $db->beginTransaction();

            $stmt = $db->prepare('INSERT IGNORE INTO event_members (event_id, division_id, user_id, jabatan_panitia) VALUES (?, ?, ?, ?)');

            $addedCount = 0;
            foreach ($userIds as $uId) {
                $uId = intval($uId);
                if ($uId > 0) {
                    $stmt->execute([$eventId, $divId, $uId, $jabatan]);
                    if ($stmt->rowCount() > 0) {
                        $addedCount++;
                    }
                }
            }

            $db->commit();

            if ($addedCount > 0) {
                setFlash('success', "$addedCount panitia berhasil ditambahkan.");
            } else {
                setFlash('warning', 'Anggota tersebut sudah ada di divisi ini.');
            }

            redirect(BASE_URL.'/admin/event_detail.php?id='.$eventId);
            break;

        case 'remove_member':
            $id = intval($_POST['id']);
            $eventId = intval($_POST['event_id']);

            $stmt = $db->prepare('DELETE FROM event_members WHERE id = ? AND event_id = ?');
            $stmt->execute([$id, $eventId]);

            setFlash('success', 'Panitia berhasil dikeluarkan dari divisi.');
            redirect(BASE_URL.'/admin/event_detail.php?id='.$eventId);
            break;

        default:
            throw new Exception('Action tidak dikenal.');
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    setFlash('danger', $e->getMessage());

    // Redirect back to appropriate page
    if (isset($_POST['event_id'])) {
        redirect(BASE_URL.'/admin/event_detail.php?id='.intval($_POST['event_id']));
    } else {
        redirect(BASE_URL.'/admin/events.php');
    }
}
