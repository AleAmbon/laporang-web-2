<?php
require_once 'config.php';
$pdo = connectDB();

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

// Logika Routing dan HTTP Method Handling

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($request_uri, '/'));
$id = null;
if (isset($path_parts[count($path_parts)-1]) && is_numeric($path_parts[count($path_parts)-1])) {
    $id = (int)$path_parts[count($path_parts)-1];
}

// Baca input body untuk PUT/DELETE
$input_data = [];
if (in_array($method, ['PUT', 'DELETE'])) {
    parse_str(file_get_contents('php://input'), $input_data);
}


switch ($method) {
    case 'GET':
        // Menangani requests GET: getData (all), getArtikelById (specific), getKategori
        $action = $_GET['action'] ?? '';
        if ($action === 'getArtikelById' && isset($_GET['id'])) {
            $id = (int)$_GET['id']; // Mengambil ID dari GET parameter
            try {
                $stmt = $pdo->prepare("SELECT * FROM artikel WHERE id = ?");
                $stmt->execute([$id]);
                $artikel = $stmt->fetch();
                if ($artikel) {
                    echo json_encode(['status' => 200, 'data' => $artikel]);
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['status' => 404, 'error' => 'Data tidak ditemukan.']);
                }
            } catch (PDOException $e) {
                http_response_code(500); // Internal Server Error
                echo json_encode(['status' => 500, 'error' => 'Gagal mengambil artikel: ' . $e->getMessage()]);
            }
        } elseif ($action === 'getKategori') {
            try {
                $stmt = $pdo->prepare("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
                $stmt->execute();
                $data = $stmt->fetchAll();
                echo json_encode($data); // Return only data for dropdown
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['status' => 500, 'error' => 'Gagal mengambil kategori: ' . $e->getMessage()]);
            }
        } else { // Default GET request (getData with pagination, search, sort)
            $q = $_GET['q'] ?? '';
            $kategori_id = $_GET['kategori_id'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $sort_by = $_GET['sort_by'] ?? 'id';
            $sort_order = $_GET['sort_order'] ?? 'DESC';

            $allowed_sort_columns = ['id', 'judul', 'status'];
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
                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM artikel JOIN kategori ON artikel.id_kategori = kategori.id_kategori" . $where_clause);
                foreach ($params as $key => $value) {
                    $stmt_count->bindParam($key + 1, $params[$key], $param_types[$key]);
                }
                $stmt_count->execute();
                $total_records = $stmt_count->fetchColumn();
                $total_pages = ceil($total_records / $limit);

                $sql_data = "SELECT artikel.id, artikel.judul, artikel.status, artikel.gambar, kategori.nama_kategori FROM artikel JOIN kategori ON artikel.id_kategori = kategori.id_kategori" . $where_clause;
                $sql_data .= " ORDER BY " . $sort_by . " " . $sort_order . " LIMIT ? OFFSET ?";

                $stmt_data = $pdo->prepare($sql_data);

                $param_index = 1;
                foreach ($params as $value) {
                    $stmt_data->bindParam($param_index, $value, $param_types[$param_index - 1]);
                    $param_index++;
                }
                $stmt_data->bindParam($param_index++, $limit, PDO::PARAM_INT);
                $stmt_data->bindParam($param_index++, $offset, PDO::PARAM_INT);

                $stmt_data->execute();
                $artikel_data = $stmt_data->fetchAll();

                $pager_links = [];
                if ($total_pages > 1) {
                    if ($page > 1) {
                        $pager_links[] = ['page' => $page - 1, 'title' => '&laquo; Previous', 'active' => false];
                    }
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $pager_links[] = ['page' => $i, 'title' => $i, 'active' => ($i == $page)];
                    }
                    if ($page < $total_pages) {
                        $pager_links[] = ['page' => $page + 1, 'title' => 'Next &raquo;', 'active' => false];
                    }
                }

                echo json_encode([
                    'status' => 200, // Tambahkan status HTTP ke response JSON
                    'artikel' => $artikel_data,
                    'q' => $q,
                    'kategori_id' => $kategori_id,
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'pager' => ['links' => $pager_links]
                ]);

            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['status' => 500, 'error' => 'Gagal mengambil data: ' . $e->getMessage()]);
            }
        }
        break;

    case 'POST': // Untuk Menambahkan data
        // Data dari POST request
        $judul = trim($_POST['judul'] ?? '');
        $isi = trim($_POST['isi'] ?? '');
        $id_kategori = (int)($_POST['id_kategori'] ?? 0);
        $status = 'published';
        $slug = generateSlug($judul);
        $gambar = null;

        $errors = [];
        $uploaded_gambar_name = handleUploadGambar($errors);

        if (!empty($errors)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 400, 'error' => implode('. ', $errors)]);
            exit();
        }
        $gambar = $uploaded_gambar_name;

        if (empty($judul) || $id_kategori <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Judul dan Kategori wajib diisi.']);
            if ($gambar && file_exists($upload_dir . $gambar)) {
                unlink($upload_dir . $gambar);
            }
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO artikel (judul, isi, status, slug, id_kategori, gambar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$judul, $isi, $status, $slug, $id_kategori, $gambar]);
            http_response_code(201); // Created
            echo json_encode(['status' => 201, 'messages' => ['success' => 'Data artikel berhasil ditambahkan.']]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Gagal menambahkan artikel: ' . $e->getMessage()]);
            if ($gambar && file_exists($upload_dir . $gambar)) {
                unlink($upload_dir . $gambar);
            }
        }
        break;

    case 'PUT': // Untuk Mengubah data (diterima dari `php://input` atau `$_POST` jika override)
        // Data bisa dari $_POST (jika frontend mengirim _method=PUT via POST)
        // atau dari php://input (jika frontend mengirim PUT method murni)
        $data_from_request = $_POST;
        if (empty($data_from_request) && !empty($input_data)) {
            $data_from_request = $input_data;
        }

        $id = (int)($data_from_request['id'] ?? $id ?? 0); // Ambil ID dari body atau URL path
        $judul = trim($data_from_request['judul'] ?? '');
        $isi = trim($data_from_request['isi'] ?? '');
        $id_kategori = (int)($data_from_request['id_kategori'] ?? 0);
        $hapus_gambar = isset($data_from_request['hapus_gambar']) && ($data_from_request['hapus_gambar'] === 'on' || $data_from_request['hapus_gambar'] === 'true'); // Handle boolean string

        if ($id <= 0 || empty($judul) || $id_kategori <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Data tidak lengkap atau ID tidak valid.']);
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
             http_response_code(400);
             echo json_encode(['status' => 400, 'error' => implode('. ', $errors)]);
             exit();
        }
        if ($uploaded_gambar_name) {
            $gambar_update = $uploaded_gambar_name;
        } else if ($hapus_gambar) { // Jika tidak ada upload baru DAN checkbox hapus dicentang
            if ($current_gambar && file_exists($upload_dir . $current_gambar)) {
                unlink($upload_dir . $current_gambar);
            }
            $gambar_update = null;
        }


        try {
            $stmt = $pdo->prepare("UPDATE artikel SET judul = ?, isi = ?, id_kategori = ?, gambar = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$judul, $isi, $id_kategori, $gambar_update, $id]);
            http_response_code(200); // OK
            echo json_encode(['status' => 200,'messages' => ['success' => 'Data artikel berhasil diubah.']]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Gagal mengupdate artikel: ' . $e->getMessage()]);
            if ($gambar_update && $gambar_update != $current_gambar && file_exists($upload_dir . $gambar_update)) {
                unlink($upload_dir . $gambar_update);
            }
        }
        break;

    case 'DELETE': // Untuk Menghapus data
        // Data bisa dari $_POST (jika frontend mengirim _method=DELETE via POST)
        // atau dari php://input (jika frontend mengirim DELETE method murni)
        $data_from_request = $_POST;
        if (empty($data_from_request) && !empty($input_data)) {
            $data_from_request = $input_data;
        }

        $id = (int)($data_from_request['id'] ?? $id ?? 0); // Ambil ID dari body atau URL path

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID artikel tidak valid.']);
            exit();
        }

        try {
            $stmt_get_gambar = $pdo->prepare("SELECT gambar FROM artikel WHERE id = ?");
            $stmt_get_gambar->execute([$id]);
            $artikel_to_delete = $stmt_get_gambar->fetch();
            $gambar_to_delete = $artikel_to_delete['gambar'] ?? null;

            $stmt = $pdo->prepare("DELETE FROM artikel WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) { // Pastikan ada baris yang terpengaruh (data benar-benar dihapus)
                if ($gambar_to_delete && file_exists($upload_dir . $gambar_to_delete)) {
                    unlink($upload_dir . $gambar_to_delete);
                }
                http_response_code(200); // OK
                echo json_encode(['status' => 200,'messages' => ['success' => 'Data artikel berhasil dihapus.']]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode(['status' => 404, 'error' => 'Data tidak ditemukan.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Gagal menghapus artikel: ' . $e->getMessage()]);
        }
        break;

    case 'GETKATEGORI': // Aksi ini tetap bisa dipanggil sebagai GET
        try {
            $stmt = $pdo->prepare("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
            $stmt->execute();
            $data = $stmt->fetchAll();
            echo json_encode($data);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 500, 'error' => 'Gagal mengambil kategori: ' . $e->getMessage()]);
        }
        break;

    default: // Jika tidak ada action atau method tidak dikenal
        http_response_code(405); // Method Not Allowed (jika tidak ada action yang cocok)
        echo json_encode(['status' => 405, 'error' => 'Metode HTTP atau Aksi tidak diizinkan.']);
        break;
}
?>