<?php
require_once 'config.php'; // Sertakan file konfigurasi
$pdo = connectDB(); // Buat koneksi database

$title = "Daftar Artikel";

// Query untuk mengambil artikel beserta nama kategori
// Menggunakan JOIN untuk menghubungkan tabel artikel dan kategori 
$stmt = $pdo->prepare("SELECT artikel.*, kategori.nama_kategori FROM artikel JOIN kategori ON artikel.id_kategori = kategori.id_kategori WHERE artikel.status = 'published' ORDER BY artikel.id DESC");
$stmt->execute();
$artikel = $stmt->fetchAll(); // Ambil semua hasil sebagai array asosiatif

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title><?= htmlspecialchars($title); ?></title>
    <style>
        body { font-family: sans-serif; margin: 20px; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 8px; }
        h1 { text-align: center; color: #0056b3; margin-bottom: 30px; }
        .entry { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px dashed #eee; }
        .entry:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .entry h2 a { text-decoration: none; color: #333; font-size: 1.8em; transition: color 0.3s ease; }
        .entry h2 a:hover { color: #007bff; }
        .entry p { color: #666; font-size: 0.9em; margin-top: 5px; }
        .entry img { max-width: 100%; height: auto; margin-top: 15px; border-radius: 5px; }
        .admin-link { display: block; text-align: center; margin-top: 40px; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; transition: background-color 0.3s ease; }
        .admin-link:hover { background-color: #218838; }
        .no-data { text-align: center; color: #999; padding: 30px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($title); ?></h1>

        <?php if ($artikel): ?>
            <?php foreach ($artikel as $row): ?>
                <article class="entry">
                    <h2><a href="detail_artikel.php?slug=<?= htmlspecialchars($row['slug']); ?>"><?= htmlspecialchars($row['judul']); ?></a></h2>
                    <p>Kategori: **<?= htmlspecialchars($row['nama_kategori']); ?>**</p>
                    <?php if (!empty($row['gambar'])): // Pastikan ada gambar sebelum menampilkannya ?>
                        <img src="gambar/<?= htmlspecialchars($row['gambar']); ?>" alt="<?= htmlspecialchars($row['judul']); ?>">
                    <?php endif; ?>
                    <p><?= htmlspecialchars(substr($row['isi'], 0, 200)); ?>...</p>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-data">Belum ada data artikel yang dipublikasikan.</p>
        <?php endif; ?>

        <a href="admin.php" class="admin-link">Kelola Artikel (Admin)</a>
    </div>
</body>
</html>