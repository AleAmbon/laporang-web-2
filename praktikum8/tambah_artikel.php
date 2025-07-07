<?php
require_once 'config.php';
$pdo = connectDB();

$title = "Tambah Artikel";
$errors = [];
$uploaded_gambar = ''; // Untuk menyimpan nama file gambar jika upload gagal tapi input lain valid

// Ambil semua kategori untuk dropdown
$stmt_kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
$kategori_list = $stmt_kategori->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $status = 'published';
    $slug = generateSlug($judul);
    $gambar = null; // Default null jika tidak ada gambar

    // --- LOGIKA UPLOAD GAMBAR BARU ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['gambar']['tmp_name'];
        $file_name = $_FILES['gambar']['name'];
        $file_size = $_FILES['gambar']['size'];
        $file_type = $_FILES['gambar']['type'];
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
            // Generate nama unik untuk gambar
            $new_file_name = uniqid('img_', true) . '.' . $file_ext;
            $upload_path = __DIR__ . '/gambar/' . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                $gambar = $new_file_name;
            } else {
                $errors[] = "Gagal mengupload gambar.";
            }
        }
    }
    // --- AKHIR LOGIKA UPLOAD GAMBAR ---

    // Validasi input
    if (empty($judul)) {
        $errors[] = "Judul wajib diisi.";
    }
    if ($id_kategori <= 0) {
        $errors[] = "Kategori wajib dipilih.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO artikel (judul, isi, status, slug, id_kategori, gambar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$judul, $isi, $status, $slug, $id_kategori, $gambar]);
            redirect('admin.php?status=added');
        } catch (PDOException $e) {
            $errors[] = "Gagal menambahkan artikel: " . $e->getMessage();
            // Jika ada gambar yang sudah diupload tapi insert gagal, hapus gambar
            if ($gambar && file_exists(__DIR__ . '/gambar/' . $gambar)) {
                unlink(__DIR__ . '/gambar/' . $gambar);
            }
        }
    } else {
        // Jika ada error validasi, pastikan nilai form tetap ada
        $uploaded_gambar = $gambar; // Simpan nama gambar jika sudah berhasil diupload tapi ada error lain
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

        <?php if (!empty($errors)): ?>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <p>
                <label for="judul">Judul</label>
                <input type="text" name="judul" id="judul" required value="<?= htmlspecialchars($_POST['judul'] ?? ''); ?>">
            </p>
            <p>
                <label for="isi">Isi</label>
                <textarea name="isi" id="isi" cols="50" rows="10"><?= htmlspecialchars($_POST['isi'] ?? ''); ?></textarea>
            </p>
            <p>
                <label for="id_kategori">Kategori</label>
                <select name="id_kategori" id="id_kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach($kategori_list as $k): ?>
                        <option value="<?= htmlspecialchars($k['id_kategori']); ?>" <?= (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $k['id_kategori']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($k['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="gambar">Gambar (JPG, JPEG, PNG, GIF, maks 5MB)</label>
                <input type="file" name="gambar" id="gambar" accept=".jpg,.jpeg,.png,.gif">
                <?php if (!empty($uploaded_gambar)): ?>
                    <small>File yang diupload sebelumnya: <?= htmlspecialchars($uploaded_gambar); ?></small>
                <?php endif; ?>
            </p>
            <p><input type="submit" value="Kirim" class="btn"></p>
        </form>
        <a href="admin.php" class="btn btn-secondary" style="margin-top: 15px;">Kembali ke Admin</a>
    </div>
</body>
</html>