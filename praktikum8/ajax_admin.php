<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Artikel AJAX</title>
    <link rel="stylesheet" href="style.css">
    <script src="assets/js/jquery-3.7.1.min.js"></script> </head>
<body>
    <div class="container">
        <h1>Manajemen Artikel AJAX</h1>

        <div id="formArtikel" style="display: none; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 30px; background-color: #f9f9f9;">
            <h2 id="formTitle">Tambah Artikel Baru</h2>
            <form id="artikelForm">
                <input type="hidden" name="id" id="artikelId">
                <p>
                    <label for="judul">Judul</label>
                    <input type="text" name="judul" id="judul" required>
                </p>
                <p>
                    <label for="isi">Isi</label>
                    <textarea name="isi" id="isi" rows="8"></textarea>
                </p>
                <p>
                    <label for="id_kategori">Kategori</label>
                    <select name="id_kategori" id="id_kategori" required>
                        <option value="">-- Pilih Kategori --</option>
                        </select>
                </p>
                <p>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Batal</button>
                </p>
            </form>
        </div>

        <button id="addArtikelBtn" class="btn btn-success" style="margin-bottom: 20px;">Tambah Artikel Baru</button>

        <table class="table-data" id="artikelTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Kategori</th> <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5">Loading data...</td></tr> </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function() {
            const api_url = 'api_artikel.php'; // URL ke API backend kita

            // Fungsi untuk menampilkan pesan loading
            function showLoadingMessage() {
                $('#artikelTable tbody').html('<tr><td colspan="5">Loading data...</td></tr>'); // [cite: 476]
            }

            // Fungsi untuk memuat data kategori ke dropdown
            function loadKategoriDropdown() {
                $.ajax({
                    url: api_url + '?action=getKategori', // Anda bisa menambahkan action 'getKategori' di api_artikel.php jika perlu
                    method: 'GET',
                    dataType: 'json',
                    success: function(kategoriData) {
                        let options = '<option value="">-- Pilih Kategori --</option>';
                        // Asumsi api_artikel.php akan memberikan data kategori jika action=getKategori
                        // Untuk saat ini, kita bisa dummy atau ambil dari database di api_artikel.php
                        // Contoh sederhana jika belum buat getKategori di api_artikel.php:
                        $.each(kategoriData, function(i, k) {
                            options += `<option value="${k.id_kategori}">${k.nama_kategori}</option>`;
                        });
                        $('#id_kategori').html(options);
                    },
                    error: function() {
                         // Fallback jika tidak bisa ambil dari API, ambil dari file terpisah atau tampilkan pesan error
                         console.error("Gagal memuat kategori. Pastikan action=getKategori di api_artikel.php sudah ada.");
                         // Contoh sederhana ambil dari data dummy jika belum ada API:
                         let dummyKategori = [
                             {id_kategori: 1, nama_kategori: 'Teknologi'},
                             {id_kategori: 2, nama_kategori: 'Gaya Hidup'},
                             {id_kategori: 3, nama_kategori: 'Olahraga'}
                         ];
                         let options = '<option value="">-- Pilih Kategori --</option>';
                         $.each(dummyKategori, function(i, k) {
                             options += `<option value="${k.id_kategori}">${k.nama_kategori}</option>`;
                         });
                         $('#id_kategori').html(options);
                    }
                });
            }
            // Untuk memastikan kategori terisi saat pertama kali halaman dimuat atau form dibuka
            // Anda perlu menambahkan case 'getKategori' di api_artikel.php seperti ini:
            /*
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
            */


            // Fungsi untuk memuat data artikel ke tabel
            function loadData() {
                showLoadingMessage(); // Tampilkan pesan loading [cite: 486]
                $.ajax({
                    url: api_url + '?action=getData', // URL ke endpoint getData [cite: 492]
                    method: 'GET', // Metode GET [cite: 493]
                    dataType: 'json', // Tipe data yang diharapkan: JSON [cite: 494]
                    success: function(data) { // Callback jika sukses [cite: 495]
                        let tableBody = "";
                        if (data.length > 0) {
                            $.each(data, function(index, row) { // Iterasi data [cite: 498]
                                tableBody += '<tr>';
                                tableBody += '<td>' + row.id + '</td>'; // [cite: 501]
                                tableBody += '<td>' + row.judul + '</td>'; // [cite: 501]
                                tableBody += '<td>' + row.status + '</td>'; // [cite: 502]
                                tableBody += '<td>' + (row.nama_kategori || 'N/A') + '</td>'; // Tampilkan nama kategori
                                tableBody += '<td>';
                                tableBody += '<button class="btn btn-info btn-sm btn-edit" data-id="' + row.id + '">Edit</button>'; // Tombol Edit
                                tableBody += '<button class="btn btn-danger btn-sm btn-delete" data-id="' + row.id + '">Delete</button>'; // Tombol Delete [cite: 506]
                                tableBody += '</td>';
                                tableBody += '</tr>';
                            });
                        } else {
                            tableBody = '<tr><td colspan="5" style="text-align: center;">Tidak ada data artikel.</td></tr>';
                        }
                        $('#artikelTable tbody').html(tableBody); // Masukkan data ke tabel [cite: 514]
                    },
                    error: function(jqXHR, textStatus, errorThrown) { // Callback jika error [cite: 533]
                        $('#artikelTable tbody').html('<tr><td colspan="5" style="color: red;">Error memuat data: ' + textStatus + ' ' + errorThrown + '</td></tr>');
                    }
                });
            }

            // Panggil loadData saat halaman pertama kali dimuat
            loadData(); // [cite: 516]

            // Event handler untuk tombol hapus
            $(document).on('click', '.btn-delete', function(e) { // [cite: 518]
                e.preventDefault(); // Mencegah aksi default link/button [cite: 519]
                const id = $(this).data('id'); // Ambil ID dari atribut data-id [cite: 520]

                if (confirm('Apakah Anda yakin ingin menghapus artikel ini?')) { // Konfirmasi 
                    $.ajax({
                        url: api_url + '?action=delete&id=' + id, // URL ke endpoint delete dengan ID [cite: 529]
                        method: 'GET', // Metode DELETE (bisa juga POST, tapi GET lebih mudah diimplementasi sederhana)
                                        // Catatan: Modul memakai method: "DELETE" tapi di polosan URL GET lebih mudah.
                                        // Untuk method DELETE HTTP sebenarnya, perlu penanganan lebih lanjut di PHP backend.
                        dataType: 'json',
                        success: function(response) { // Callback jika sukses [cite: 530]
                            if (response.status === 'success') {
                                loadData(); // Reload data setelah hapus berhasil 
                            } else {
                                alert('Gagal menghapus artikel: ' + response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) { // Callback jika error [cite: 533]
                            alert('Error menghapus artikel: ' + textStatus + ' ' + errorThrown); // [cite: 534]
                        }
                    });
                }
                console.log('Delete button clicked for ID:', id); // Debugging [cite: 539]
            });

            // Event handler untuk menampilkan form tambah artikel
            $('#addArtikelBtn').on('click', function() {
                $('#formArtikel').slideDown(); // Tampilkan form
                $('#formTitle').text('Tambah Artikel Baru');
                $('#artikelForm')[0].reset(); // Reset form
                $('#artikelId').val(''); // Kosongkan ID tersembunyi
                $('#submitBtn').text('Simpan').removeClass('btn-info').addClass('btn-primary'); // Ubah teks tombol
                loadKategoriDropdown(); // Muat ulang kategori untuk form
            });

            // Event handler untuk tombol batal di form
            $('#cancelBtn').on('click', function() {
                $('#formArtikel').slideUp(); // Sembunyikan form
            });

            // Event handler untuk submit form Tambah/Edit
            $('#artikelForm').on('submit', function(e) {
                e.preventDefault(); // Mencegah reload halaman
                const formData = $(this).serializeArray(); // Ambil data form
                const artikelId = $('#artikelId').val(); // Cek apakah ini mode edit atau tambah

                let action = '';
                if (artikelId) {
                    action = 'update'; // Jika ada ID, ini adalah update
                } else {
                    action = 'add'; // Jika tidak ada ID, ini adalah tambah
                }

                $.ajax({
                    url: api_url + '?action=' + action, // Kirim ke endpoint yang sesuai
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            $('#formArtikel').slideUp(); // Sembunyikan form
                            loadData(); // Reload tabel artikel
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Terjadi kesalahan AJAX: ' + textStatus + ' ' + errorThrown);
                    }
                });
            });

            // Event handler untuk tombol Edit
            $(document).on('click', '.btn-edit', function() {
                const id = $(this).data('id');
                $('#formArtikel').slideDown(); // Tampilkan form
                $('#formTitle').text('Edit Artikel');
                $('#submitBtn').text('Update').removeClass('btn-primary').addClass('btn-info'); // Ubah teks tombol
                loadKategoriDropdown(); // Muat ulang kategori untuk form

                $.ajax({
                    url: api_url + '?action=getArtikelById&id=' + id, // Ambil data artikel via AJAX
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            const artikel = response.data;
                            $('#artikelId').val(artikel.id);
                            $('#judul').val(artikel.judul);
                            $('#isi').val(artikel.isi);
                            $('#id_kategori').val(artikel.id_kategori); // Set nilai kategori yang terpilih
                        } else {
                            alert('Gagal memuat data artikel: ' + response.message);
                            $('#formArtikel').slideUp();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Error memuat data artikel untuk edit: ' + textStatus + ' ' + errorThrown);
                        $('#formArtikel').slideUp();
                    }
                });
            });

            // Optional: load kategori saat pertama kali halaman dimuat untuk dropdown form
            // loadKategoriDropdown(); // Bisa dipanggil di sini juga jika form langsung terlihat.
        });
    </script>
</body>
</html>