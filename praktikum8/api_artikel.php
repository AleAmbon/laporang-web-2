<?php
require_once 'config.php'; // Sertakan konfigurasi database
$pdo = connectDB(); // Buat koneksi database

// Set header untuk memberitahu browser bahwa responsnya adalah JSON
header('Content-Type: application/json');

// Ambil action dari parameter URL atau POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'getData':
        // Logika untuk mengambil semua data artikel (sesuai modul)
        try {
            $stmt = $pdo->prepare("SELECT artikel.id, artikel.judul, artikel.status, kategori.nama_kategori FROM artikel JOIN kategori ON artikel.id_kategori = kategori.id_kategori ORDER BY artikel.id DESC");
            $stmt->execute();
            $data = $stmt->fetchAll();
            echo json_encode($data); // Kirim data dalam format JSON [cite: 447]
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
        break;
        
    case 'getKategori':
        try {
            $stmt = $pdo->prepare("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
            $stmt->execute();
            $data = $stmt->fetchAll();
            echo json_encode($data);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Gagal mengambil kategori: ' . $e->getMessage()]);
        }
        break;

    case 'delete':
        // Logika untuk menghapus artikel berdasarkan ID (sesuai modul)
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM artikel WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'success', 'message' => 'Artikel berhasil dihapus.']); // [cite: 453]
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus artikel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID artikel tidak valid.']);
        }
        break;

    case 'add':
        // Logika untuk menambah artikel (Improvisasi/Tugas)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $judul = trim($_POST['judul'] ?? '');
            $isi = trim($_POST['isi'] ?? '');
            $id_kategori = (int)($_POST['id_kategori'] ?? 0);
            $status = 'published'; // Default status
            $slug = generateSlug($judul);

            if (empty($judul) || $id_kategori <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Judul dan Kategori wajib diisi.']);
                exit();
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO artikel (judul, isi, status, slug, id_kategori) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$judul, $isi, $status, $slug, $id_kategori]);
                echo json_encode(['status' => 'success', 'message' => 'Artikel berhasil ditambahkan.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan artikel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
        }
        break;

    case 'getArtikelById':
        // Logika untuk mengambil satu artikel berdasarkan ID untuk form edit
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id = ?");
                $stmt->execute([$id]);
                $artikel = $stmt->fetch();
                if ($artikel) {
                    echo json_encode(['status' => 'success', 'data' => $artikel]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Artikel tidak ditemukan.']);
                }
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil artikel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID artikel tidak valid.']);
        }
        break;

    case 'update':
        // Logika untuk mengubah artikel (Improvisasi/Tugas)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $isi = trim($_POST['isi'] ?? '');
            $id_kategori = (int)($_POST['id_kategori'] ?? 0);

            if ($id <= 0 || empty($judul) || $id_kategori <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap atau ID tidak valid.']);
                exit();
            }

            try {
                $stmt = $pdo->prepare("UPDATE artikel SET judul = ?, isi = ?, id_kategori = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$judul, $isi, $id_kategori, $id]);
                echo json_encode(['status' => 'success', 'message' => 'Artikel berhasil diupdate.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate artikel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal.']);
        break;
}
?>