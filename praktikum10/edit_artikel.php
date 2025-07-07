<?php
require_once 'config.php';
$pdo = connectDB();

$title = "Edit Artikel";
$errors = [];
$artikel_data = null;

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID artikel tidak valid.");
}

// Ambil data artikel yang akan diedit
try {
    $stmt_artikel = $pdo->prepare("SELECT * FROM artikel WHERE id = ?");
    $stmt_artikel->execute([$id]);
    $artikel_data = $stmt_artikel->fetch();

    if (!$artikel_data) {
        die("Artikel tidak ditemukan.");
    }
} catch (PDOException $e) {
    die("Error mengambil data artikel: " . $e->getMessage());
}

// Ambil semua kategori untuk dropdown
$stmt_kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");
$kategori_list = $stmt_kategori->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $current_gambar = $artikel_data['gambar']; // Gambar yang sudah ada

    $gambar_update = $current_gambar; // Defaultnya tetap gambar yang lama

    // --- LOGIKA UPLOAD GAMBAR BARU SAAT EDIT ---
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
            $new_file_name = uniqid('img_', true) . '.' . $file_ext;
            $upload_path = __DIR__ . '/gambar/' . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                $gambar_update = $new_file_name;
                // Hapus gambar lama jika ada dan berhasil upload gambar baru
                if ($current_gambar && file_exists(__DIR__ . '/gambar/' . $current_gambar)) {
                    unlink(__DIR__ . '/gambar/' . $current_gambar);
                }
            } else {
                $errors[] = "Gagal mengupload gambar baru.";
            }
        }
    } else if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == 'on') {
        // Logika hapus gambar jika checkbox dicentang
        if ($current_gambar && file_exists(__DIR__ . '/gambar/' . $current_gambar)) {
            unlink(__DIR__ . '/gambar/' . $current_gambar);
        }
        $gambar_update = null; // Set gambar menjadi null di database
    }

    // --- AKHIR LOGIKA UPLOAD/HAPUS GAMBAR ---

    // Validasi input
    if (empty($judul)) {
        $errors[] = "Judul wajib diisi.";
    }
    if ($id_kategori <= 0) {
        $errors[] = "Kategori wajib dipilih.";
    }

    if (empty($errors)) {
        try {
            // Update data artikel, termasuk kolom gambar
            $stmt_update = $pdo->prepare("UPDATE artikel SET judul = ?, isi = ?, id_kategori = ?, gambar = ?, updated_at = NOW() WHERE id = ?");
            $stmt_update->execute([$judul, $isi, $id_kategori, $gambar_update, $id]);
            redirect('admin.php?status=updated');
        } catch (PDOException $e) {
            $errors[] = "Gagal mengupdate artikel: " . $e->getMessage();
            // Jika update database gagal, dan ada gambar baru yang terupload, hapus gambar baru tersebut
            if ($gambar_update && $gambar_update != $current_gambar && file_exists(__DIR__ . '/gambar/' . $gambar_update)) {
                unlink(__DIR__ . '/gambar/' . $gambar_update);
            }
        }
    } else {
        // Jika ada error validasi, update data artikel yang akan ditampilkan di form
        $artikel_data['judul'] = $judul;
        $artikel_data['isi'] = $isi;
        $artikel_data['id_kategori'] = $id_kategori;
        // Jangan lupa set gambar agar tetap tampil jika ada error validasi
        $artikel_data['gambar'] = $gambar_update;
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
                <input type="text" name="judul" id="judul" required value="<?= htmlspecialchars($artikel_data['judul']); ?>">
            </p>
            <p>
                <label for="isi">Isi</label>
                <textarea name="isi" id="isi" cols="50" rows="10"><?= htmlspecialchars($artikel_data['isi']); ?></textarea>
            </p>
            <p>
                <label for="id_kategori">Kategori</label>
                <select name="id_kategori" id="id_kategori" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach($kategori_list as $k): ?>
                        <option value="<?= htmlspecialchars($k['id_kategori']); ?>" <?= ($artikel_data['id_kategori'] == $k['id_kategori']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($k['nama_kategori']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="gambar">Gambar (JPG, JPEG, PNG, GIF, maks 5MB)</label>
                <?php if (!empty($artikel_data['gambar'])): ?>
                    <img src="gambar/<?= htmlspecialchars($artikel_data['gambar']); ?>" alt="Gambar Saat Ini" style="max-width: 200px; height: auto; display: block; margin-bottom: 10px;">
                    <label style="display:inline-block; margin-right: 15px;">
                        <input type="checkbox" name="hapus_gambar"> Hapus gambar saat ini
                    </label>
                    <br>
                    <small>Upload gambar baru untuk mengganti gambar ini.</small>
                <?php endif; ?>
                <input type="file" name="gambar" id="gambar" accept=".jpg,.jpeg,.png,.gif">
            </p>
            <p><input type="submit" value="Update" class="btn"></p>
        </form>
        <a href="admin.php" class="btn btn-secondary" style="margin-top: 15px;">Kembali ke Admin</a>
    </div>
</body>
</html>