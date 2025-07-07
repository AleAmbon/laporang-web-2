<?php

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     
define('DB_PASS', '');         
define('DB_NAME', 'lab_ci4');  

/**
 * Fungsi untuk membuat koneksi PDO ke database.
 * @return P
 */
function connectDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
        return $pdo;
    } catch (PDOException $e) {
        die("Koneksi database gagal: " . $e->getMessage());
    }
}

/**
 * Fungsi helper sederhana untuk redirect ke URL tertentu.
 * @param string $url URL tujuan redirect.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Fungsi untuk membuat slug dari teks (judul).
 * @param string $text Teks yang akan diubah menjadi slug.
 * @return string Slug yang dihasilkan.
 */
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a'; 
    }
    return $text;
}

?>