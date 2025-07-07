<?php
require_once 'config.php';
$pdo = connectDB();

$title = "Daftar Artikel (Admin)";

// Logika Filter dan Pencarian
$q = $_GET['q'] ?? ''; // Keyword pencarian
$kategori_id = $_GET['kategori_id'] ?? ''; // Filter kategori

// Paginasi Sederhana
$limit = 10; // Jumlah artikel per halaman
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Bangun query dasar dengan JOIN ke tabel kategori
$sql = "SELECT artikel.*, kategori.nama_kategori FROM artikel JOIN kategori ON artikel.id_kategori = kategori.id_kategori WHERE 1=1";
$params = []; // Array untuk parameter query yang akan di-bind
$param_types = []; // Array baru untuk menyimpan tipe parameter (jika perlu)

// Terapkan filter pencarian jika keyword disediakan
if (!empty($q)) {
    $sql .= " AND artikel.judul LIKE ?";
    $params[] = '%' . $q . '%';
    $param_types[] = PDO::PARAM_STR; // String
}
// Terapkan filter kategori jika kategori_id disediakan
if (!empty($kategori_id)) {
    $sql .= " AND artikel.id_kategori = ?";
    $params[] = $kategori_id;
    $param_types[] = PDO::PARAM_INT; // Integer
}

// Hitung total data untuk paginasi (tanpa LIMIT/OFFSET)
$stmt_count = $pdo->prepare(str_replace('artikel.*, kategori.nama_kategori', 'COUNT(*)', $sql));
// Bind parameter untuk count query
foreach ($params as $key => $value) {
    // Hanya bind parameter yang relevan untuk count query (string dan integer)
    if (isset($param_types[$key])) {
        $stmt_count->bindParam($key + 1, $params[$key], $param_types[$key]);
    } else {
        // Fallback to string if type not specified (e.g., for LIKE parameters)
        $stmt_count->bindParam($key + 1, $params[$key], PDO::PARAM_STR);
    }
}
$stmt_count->execute();
$total_data = $stmt_count->fetchColumn();
$total_pages = ceil($total_data / $limit); // Hitung total halaman

// Tambahkan LIMIT dan OFFSET untuk paginasi ke query utama
$sql .= " ORDER BY artikel.id DESC LIMIT ? OFFSET ?";


// Eksekusi query utama untuk mengambil artikel
$stmt = $pdo->prepare($sql);

// Bind parameter secara manual untuk mengontrol tipe data
$param_index = 1;
foreach ($params as $value) {
    if (isset($param_types[$param_index - 1])) { // Cek apakah tipe data sudah ditentukan
        $stmt->bindParam($param_index, $value, $param_types[$param_index - 1]);
    } else {
        // Default ke string jika tidak ada tipe spesifik (misalnya untuk LIKE)
        $stmt->bindParam($param_index, $value, PDO::PARAM_STR);
    }
    $param_index++;
}

// Bind limit dan offset sebagai integer secara eksplisit
$stmt->bindParam($param_index++, $limit, PDO::PARAM_INT);
$stmt->bindParam($param_index++, $offset, PDO::PARAM_INT);


$stmt->execute(); // Baris 44
$artikel = $stmt->fetchAll();

// Ambil semua kategori untuk dropdown filter
$stmt_kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
$kategori_list = $stmt_kategori->fetchAll();

// Logika Hapus Artikel
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    try {
        $stmt_delete = $pdo->prepare("DELETE FROM artikel WHERE id = ?");
        $stmt_delete->execute([$id_to_delete]);
        redirect('admin.php?status=deleted'); // Redirect dengan pesan sukses
    } catch (PDOException $e) {
        // Handle error, misalnya karena ada foreign key constraint (walaupun sudah CASCADE)
        echo "<p style='color: red;'>Gagal menghapus artikel: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2><?= htmlspecialchars($title); ?></h2>

        <div class="row">
            <div class="form-inline">
                <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="text" name="q" value="<?= htmlspecialchars($q); ?>" placeholder="Cari judul artikel">
                    <select name="kategori_id">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori_list as $k): ?>
                            <option value="<?= htmlspecialchars($k['id_kategori']); ?>" <?= ($kategori_id == $k['id_kategori']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($k['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Cari</button>
                </form>
            </div>
            <div style="margin-left: auto;">
                <a href="tambah_artikel.php" class="btn btn-success">Tambah Artikel</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($artikel) > 0): ?>
                    <?php foreach ($artikel as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td>
                                <b><?= htmlspecialchars($row['judul']); ?></b>
                                <p><small><?= htmlspecialchars(substr($row['isi'], 0, 50)); ?>...</small></p>
                            </td>
                            <td><?= htmlspecialchars($row['nama_kategori']); ?></td>
                            <td>
                                <?= htmlspecialchars($row['status']); ?>
                            </td>
                            <td>
                                <a class="btn btn-info btn-sm" href="edit_artikel.php?id=<?= htmlspecialchars($row['id']); ?>">Ubah</a>
                                <a class="btn btn-danger btn-sm" onclick="return confirm('Yakin menghapus data ini?');" href="admin.php?action=delete&id=<?= htmlspecialchars($row['id']); ?>">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Tidak ada data artikel.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i; ?>&q=<?= urlencode($q); ?>&kategori_id=<?= urlencode($kategori_id); ?>" class="
                    <?= ($i == $page) ? 'active' : ''; ?>">
                    <?= $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <a href="index.php" class="admin-link btn-secondary" style="margin-top: 30px;">Lihat Halaman Publik</a>
    </div>
</body>
</html>