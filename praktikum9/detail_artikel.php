<?php
require_once 'config.php'; // Sertakan file konfigurasi
$pdo = connectDB(); // Buat koneksi database

$title = "Detail Artikel";
$artikel_data = null; // Variabel untuk menyimpan data artikel

$slug = $_GET['slug'] ?? ''; // Ambil slug dari parameter URL

if (empty($slug)) {
    die("Slug artikel tidak valid.");
}

// Query untuk mengambil artikel berdasarkan slug, termasuk nama kategori
// Menggunakan JOIN untuk mengambil nama kategori dari tabel kategori 
$stmt = $pdo->prepare("SELECT artikel.*, kategori.nama_kategori FROM artikel JOIN kategori ON artikel.id_kategori = kategori.id_kategori WHERE artikel.slug = ? AND artikel.status = 'published'");
$stmt->execute([$slug]);
$artikel_data = $stmt->fetch(); // Ambil satu baris data

if (!$artikel_data) {
    die("Artikel tidak ditemukan atau tidak dipublikasikan.");
}

$title = $artikel_data['judul']; // Judul halaman sesuai judul artikel

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
        h1 { text-align: center; color: #0056b3; margin-bottom: 25px; font-size: 2.2em; }
        .article-meta { text-align: center; font-size: 0.9em; color: #777; margin-bottom: 20px; }
        .article-meta span { margin: 0 8px; }
        .article-image { text-align: center; margin-bottom: 25px; }
        .article-image img { max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .article-content { font-size: 1.1em; text-align: justify; }
        .back-link { display: block; text-align: center; margin-top: 40px; padding: 10px 20px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px; transition: background-color 0.3s ease; }
        .back-link:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <article class="entry">
            <h1><?= htmlspecialchars($artikel_data['judul']); ?></h1>
            <div class="article-meta">
                <span>Kategori: **<?= htmlspecialchars($artikel_data['nama_kategori']); ?>**</span>
                <span>Dibuat: <?= date('d M Y', strtotime($artikel_data['created_at'])); ?></span>
            </div>
            <?php if (!empty($artikel_data['gambar'])): ?>
                <div class="article-image">
                    <img src="gambar/<?= htmlspecialchars($artikel_data['gambar']); ?>" alt="<?= htmlspecialchars($artikel_data['judul']); ?>">
                </div>
            <?php endif; ?>
            <div class="article-content">
                <p><?= nl2br(htmlspecialchars($artikel_data['isi'])); ?></p>
            </div>
        </article>
        <a href="index.php" class="back-link">&larr; Kembali ke Daftar Artikel</a>
    </div>
</body>
</html>