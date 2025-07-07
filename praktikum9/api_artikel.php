<?php
require_once 'config.php'; // Sertakan konfigurasi database
$pdo = connectDB(); // Buat koneksi database

// Set header untuk memberitahu browser bahwa responsnya adalah JSON
header('Content-Type: application/json');

// Direktori untuk menyimpan gambar
$upload_dir = __DIR__ . '/gambar/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fungsi bantu untuk mengupload gambar (sama seperti sebelumnya)
function handleUploadGambar(&$errors, $current_gambar = null) {
    global $upload_dir;
    $new_file_name = null;

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['gambar']['tmp_name'];
        $file_name = $_FILES['gambar']['name'];
        $file_size = $_FILES['gambar']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $errors[] = "Ekstensi file gambar tidak diizinkan. Hanya JPG, JPEG, PNG, GIF.";
        }
        if ($file_size > $max_file_size) {
            $errors[] = "Ukuran file gambar terlalu besar (maksimal 5MB).";
        }

        if (empty($errors)) {
            $new_file_name = uniqid('img_', true) . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                if ($current_gambar && file_exists($upload_dir . $current_gambar)) {
                    unlink($upload_dir . $current_gambar);
                }
                return $new_file_name;
            } else {
                $errors[] = "Gagal mengupload gambar.";
            }
        }
    }
    return null;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'getData':
        // --- LOGIKA PENCARIAN, FILTER, PAGINASI, SORTING UNTUK AJAX ---
        $q = $_GET['q'] ?? '';
        $kategori_id = $_GET['kategori_id'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 10; // Jumlah item per halaman
        $offset = ($page - 1) * $limit;
        $sort_by = $_GET['sort_by'] ?? 'id'; // Default sort by id
        $sort_order = $_GET['sort_order'] ?? 'DESC'; // Default sort order DESC

        // Validasi sort_by dan sort_order untuk mencegah SQL injection
        $allowed_sort_columns = ['id', 'judul', 'status']; // Kolom yang boleh disorting
        $allowed_sort_orders = ['ASC', 'DESC'];

        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'id';
        }
        if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
            $sort_order = 'DESC';
        }

        $sql_where_parts = [];
        $params = [];
        $param_types = [];

        if (!empty($q)) {
            $sql_where_parts[] = "artikel.judul LIKE ?";
            $params[] = '%' . $q . '%';
            $param_types[] = PDO::PARAM_STR;
        }
        if (!empty($kategori_id)) {
            $sql_where_parts[] = "artikel.id_kategori = ?";
            $params[] = $kategori_id;
            $param_types[] = PDO::PARAM_INT;
        }

        $where_clause = '';
        if (!empty($sql_where_parts)) {
            $where_clause = " WHERE " . implode(" AND ", $sql_where_parts);
        }

        try {
            // Query untuk menghitung total data (tanpa LIMIT/OFFSET)
            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM artikel JOIN kategori ON artikel.id_kategori = kategori.id_kategori" . $where_clause);
            // Bind parameter untuk count query
            foreach ($params as $key => $value) {
                $stmt_count->bindParam($key + 1, $params[$key], $param_types[$key]);
            }
            $stmt_count->execute();
            $total_records = $stmt_count->fetchColumn();
            $total_pages = ceil($total_records / $limit);

            // Query untuk mengambil data artikel dengan JOIN, filter, sort, dan paginasi
            $sql_data = "SELECT artikel.id, artikel.judul, artikel.status, artikel.gambar, kategori.nama_kategori FROM artikel JOIN kategori ON artikel.id_kategori = kategori.id_kategori" . $where_clause;
            $sql_data .= " ORDER BY " . $sort_by . " " . $sort_order . " LIMIT ? OFFSET ?";

            $stmt_data = $pdo->prepare($sql_data);

            // Bind parameter untuk data query (termasuk paginasi)
            $param_index = 1;
            foreach ($params as $value) {
                $stmt_data->bindParam($param_index, $value, $param_types[$param_index - 1]);
                $param_index++;
            }
            $stmt_data->bindParam($param_index++, $limit, PDO::PARAM_INT);
            $stmt_data->bindParam($param_index++, $offset, PDO::PARAM_INT);

            $stmt_data->execute();
            $artikel_data = $stmt_data->fetchAll();

            // Persiapan data pager untuk frontend
            $pager_links = [];
            if ($total_pages > 1) {
                // Link Sebelumnya
                if ($page > 1) {
                    $pager_links[] = ['page' => $page - 1, 'title' => '&laquo; Previous', 'active' => false];
                }
                // Link Halaman
                for ($i = 1; $i <= $total_pages; $i++) {
                    $pager_links[] = ['page' => $i, 'title' => $i, 'active' => ($i == $page)];
                }
                // Link Selanjutnya
                if ($page < $total_pages) {
                    $pager_links[] = ['page' => $page + 1, 'title' => 'Next &raquo;', 'active' => false];
                }
            }

            echo json_encode([
                'artikel' => $artikel_data,
                'q' => $q,
                'kategori_id' => $kategori_id,
                'current_page' => $page,
                'total_pages' => $total_pages,
                'pager' => ['links' => $pager_links] // Format mirip CodeIgniter pager
            ]);

        } catch (PDOException $e) {
            echo json_encode(['error' => 'Gagal mengambil data: ' . $e->getMessage()]);
        }
        break;

    // ... (kasus 'delete', 'add', 'getArtikelById', 'update', 'getKategori' tetap sama seperti sebelumnya) ...
    case 'delete':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt_get_gambar = $pdo->prepare("SELECT gambar FROM artikel WHERE id = ?");
                $stmt_get_gambar->execute([$id]);
                $artikel_to_delete = $stmt_get_gambar->fetch();
                $gambar_to_delete = $artikel_to_delete['gambar'] ?? null;

                $stmt = $pdo->prepare("DELETE FROM artikel WHERE id = ?");
                $stmt->execute([$id]);

                if ($gambar_to_delete && file_exists($upload_dir . $gambar_to_delete)) {
                    unlink($upload_dir . $gambar_to_delete);
                }

                echo json_encode(['status' => 'success', 'message' => 'Artikel berhasil dihapus.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus artikel: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID artikel tidak valid.']);
        }
        break;

    case 'add':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $judul = trim($_POST['judul'] ?? '');
            $isi = trim($_POST['isi'] ?? '');
            $id_kategori = (int)($_POST['id_kategori'] ?? 0);
            $status = 'published';
            $slug = generateSlug($judul);
            $gambar = null;

            $errors = [];
            $uploaded_gambar_name = handleUploadGambar($errors);

            if (!empty($errors)) {
                echo json_encode(['status' => 'error', 'message' => implode('. ', $errors)]);
                exit();
            }
            $gambar = $uploaded_gambar_name;

            if (empty($judul) || $id_kategori <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Judul dan Kategori wajib diisi.']);
                if ($gambar && file_exists($upload_dir . $gambar)) {
                    unlink($upload_dir . $gambar);
                }
                exit();
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO artikel (judul, isi, status, slug, id_kategori, gambar) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$judul, $isi, $status, $slug, $id_kategori, $gambar]);
                echo json_encode(['status' => 'success', 'message' => 'Artikel berhasil ditambahkan.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan artikel: ' . $e->getMessage()]);
                if ($gambar && file_exists($upload_dir . $gambar)) {
                    unlink($upload_dir . $gambar);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
        }
        break;

    case 'getArtikelById':
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $isi = trim($_POST['isi'] ?? '');
            $id_kategori = (int)($_POST['id_kategori'] ?? 0);
            $hapus_gambar = isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] === 'on';

            if ($id <= 0 || empty($judul) || $id_kategori <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap atau ID tidak valid.']);
                exit();
            }

            $stmt_get_old_gambar = $pdo->prepare("SELECT gambar FROM artikel WHERE id = ?");
            $stmt_get_old_gambar->execute([$id]);
            $old_artikel_data = $stmt_get_old_gambar->fetch();
            $current_gambar = $old_artikel_data['gambar'] ?? null;

            $errors = [];
            $gambar_update = $current_gambar;

            $uploaded_gambar_name = handleUploadGambar($errors, $current_gambar);
            if (!empty($errors)) {
                 echo json_encode(['status' => 'error', 'message' => implode('. ', $errors)]);
                 exit();
            }
            if ($uploaded_gambar_name) {
                $gambar_update = $uploaded_gambar_name;
            } else if ($hapus_gambar) {
                if ($current_gambar && file_exists($upload_dir . $current_gambar)) {
                    unlink($upload_dir . $current_gambar);
                }
                $gambar_update = null;
            }

            try {
                $stmt = $pdo->prepare("UPDATE artikel SET judul = ?, isi = ?, id_kategori = ?, gambar = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$judul, $isi, $id_kategori, $gambar_update, $id]);
                echo json_encode(['status' => 'success', 'message' => 'Artikel berhasil diupdate.']);
            } catch (PDOException $e) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate artikel: ' . $e->getMessage()]);
                if ($gambar_update && $gambar_update != $current_gambar && file_exists($upload_dir . $gambar_update)) {
                    unlink($upload_dir . $gambar_update);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
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

    default:
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal.']);
        break;
}
?>