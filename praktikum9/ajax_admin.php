<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Artikel AJAX</title>
    <link rel="stylesheet" href="style.css">
    <script src="assets/js/jquery-3.7.1.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>Manajemen Artikel AJAX</h1>

        <div id="formArtikel" style="display: none; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 30px; background-color: #f9f9f9;">
            <h2 id="formTitle">Tambah Artikel Baru</h2>
            <form id="artikelForm" enctype="multipart/form-data">
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
                    <label for="gambar">Gambar (JPG, JPEG, PNG, GIF, maks 5MB)</label>
                    <input type="file" name="gambar" id="gambar" accept=".jpg,.jpeg,.png,.gif">
                    <div id="currentGambarInfo" style="margin-top: 10px; display: none;">
                        Gambar saat ini: <span id="currentGambarName"></span>
                        <br>
                        <label>
                            <input type="checkbox" name="hapus_gambar" id="hapus_gambar_checkbox"> Hapus gambar saat ini
                        </label>
                    </div>
                </p>
                <p>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Batal</button>
                </p>
            </form>
        </div>

        <button id="addArtikelBtn" class="btn btn-success" style="margin-bottom: 20px;">Tambah Artikel Baru</button>

        <div class="row mb-3">
            <div class="form-inline" style="width: 100%;">
                <form id="search-filter-form" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
                    <input type="text" name="q" id="search-box" value="" placeholder="Cari judul artikel" style="flex-grow: 1;">
                    <select name="kategori_id" id="category-filter" style="flex-grow: 1;">
                        <option value="">Semua Kategori</option>
                        </select>
                    <select name="sort_by" id="sort-by" style="flex-grow: 0;"> <option value="id">Urutkan Berdasarkan ID</option>
                        <option value="judul">Urutkan Berdasarkan Judul</option>
                        <option value="status">Urutkan Berdasarkan Status</option>
                    </select>
                    <select name="sort_order" id="sort-order" style="flex-grow: 0;"> <option value="DESC">Terbaru (DESC)</option>
                        <option value="ASC">Terlama (ASC)</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Cari/Filter</button>
                </form>
            </div>
        </div>

        <div id="loading-indicator" style="text-align: center; padding: 20px; display: none;">
            <img src="https://cdnjs.cloudflare.com/ajax/libs/galleriffic/2.0.1/css/loader.gif" alt="Loading..." style="width: 50px;">
            <p>Memuat data...</p>
        </div>

        <div id="article-container">
            </div>
        <div id="pagination-container" style="margin-top: 20px; text-align: center;">
            </div>
    </div>

    <script>
        $(document).ready(function() {
            const api_url = 'api_artikel.php';
            const articleContainer = $('#article-container');
            const paginationContainer = $('#pagination-container');
            const searchFilterForm = $('#search-filter-form');
            const searchBox = $('#search-box');
            const categoryFilter = $('#category-filter');
            const sortBy = $('#sort-by'); // Elemen sorting
            const sortOrder = $('#sort-order'); // Elemen sorting
            const loadingIndicator = $('#loading-indicator'); // Indikator loading

            // Fungsi untuk menampilkan pesan loading
            function showLoading() {
                loadingIndicator.show();
                articleContainer.empty(); // Kosongkan kontainer artikel saat loading
                paginationContainer.empty(); // Kosongkan kontainer paginasi saat loading
            }

            function hideLoading() {
                loadingIndicator.hide();
            }

            // Fungsi untuk memuat data kategori ke dropdown (untuk filter dan form CRUD)
            function loadCategoryFilterAndDropdown(selectedFilterId = '', selectedFormId = '') {
                $.ajax({
                    url: api_url + '?action=getKategori',
                    method: 'GET',
                    dataType: 'json',
                    success: function(kategoriData) {
                        let filterOptions = '<option value="">Semua Kategori</option>';
                        let formOptions = '<option value="">-- Pilih Kategori --</option>';
                        $.each(kategoriData, function(i, k) {
                            filterOptions += `<option value="${k.id_kategori}" ${k.id_kategori == selectedFilterId ? 'selected' : ''}>${k.nama_kategori}</option>`;
                            formOptions += `<option value="${k.id_kategori}" ${k.id_kategori == selectedFormId ? 'selected' : ''}>${k.nama_kategori}</option>`;
                        });
                        $('#category-filter').html(filterOptions); // Untuk filter
                        $('#id_kategori').html(formOptions); // Untuk form CRUD
                    },
                    error: function() {
                         console.error("Gagal memuat kategori.");
                         // Fallback jika gagal (bisa tampilkan pesan error)
                         $('#category-filter').html('<option value="">Error memuat kategori</option>');
                         $('#id_kategori').html('<option value="">Error memuat kategori</option>');
                    }
                });
            }

            // Fungsi untuk memuat data artikel
            const fetchData = (page = 1) => {
                showLoading(); // Tampilkan indikator loading

                const q = searchBox.val();
                const kategori_id = categoryFilter.val();
                const sort_by_val = sortBy.val(); // Ambil nilai sorting
                const sort_order_val = sortOrder.val(); // Ambil nilai order

                $.ajax({
                    url: api_url,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'getData', // Aksi di backend
                        page: page,
                        q: q,
                        kategori_id: kategori_id,
                        sort_by: sort_by_val, // Kirim parameter sorting
                        sort_order: sort_order_val // Kirim parameter order
                    },
                    success: function(response) {
                        hideLoading(); // Sembunyikan indikator loading
                        if (response.error) {
                            articleContainer.html('<p style="color: red;">' + response.error + '</p>');
                            paginationContainer.empty();
                            return;
                        }
                        renderArticles(response.artikel);
                        renderPagination(response.pager, response.q, response.kategori_id, response.current_page); // Sertakan current_page
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        hideLoading();
                        articleContainer.html('<p style="color: red;">Error memuat data: ' + textStatus + ' ' + errorThrown + ' ' + jqXHR.responseText + '</p>');
                        paginationContainer.empty();
                    }
                });
            };

            // Fungsi untuk me-render artikel ke dalam tabel
            const renderArticles = (articles) => {
                let html = '<table class="table">';
                html += '<thead><tr><th>ID</th><th>Judul</th><th>Status</th><th>Kategori</th><th>Gambar</th><th>Aksi</th></tr></thead><tbody>';

                if (articles.length > 0) {
                    articles.forEach(article => {
                        html += `
                        <tr>
                            <td>${article.id}</td>
                            <td>
                                <b>${article.judul}</b>
                                <p><small>${article.isi ? article.isi.substring(0, 50) + '...' : ''}</small></p>
                            </td>
                            <td>${article.status}</td>
                            <td>${article.nama_kategori || 'N/A'}</td>
                            <td>
                                ${article.gambar ? `<img src="gambar/${article.gambar}" alt="Gambar" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">` : 'N/A'}
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm btn-edit" data-id="${article.id}">Edit</button>
                                <button class="btn btn-danger btn-sm btn-delete" data-id="${article.id}">Delete</button>
                            </td>
                        </tr>
                        `;
                    });
                } else {
                    html += '<tr><td colspan="6" style="text-align: center;">Tidak ada data.</td></tr>';
                }
                html += '</tbody></table>';
                articleContainer.html(html);
            };

            // Fungsi untuk me-render paginasi
            const renderPagination = (pager, q, kategori_id, currentPage) => {
                let html = '<nav><ul class="pagination">';
                pager.links.forEach(link => {
                    // Pastikan link.page ada dan valid, jika tidak, pakai currentPage
                    const pageNum = link.page || currentPage;
                    const url = `javascript:void(0);" data-page="${pageNum}" class="page-link ${link.active ? 'active' : ''}`;
                    html += `<li class="page-item"><a href="${url}">${link.title}</a></li>`;
                });
                html += '</ul></nav>';
                paginationContainer.html(html);
            };

            // Event listener untuk tombol dan dropdown form search/filter
            searchFilterForm.on('submit', function(e) {
                e.preventDefault();
                fetchData(1); // Kembali ke halaman 1 saat pencarian/filter baru
            });

            categoryFilter.on('change', function() {
                fetchData(1); // Kembali ke halaman 1 saat filter kategori berubah
            });

            sortBy.on('change', function() { // Event untuk sorting
                fetchData(1);
            });

            sortOrder.on('change', function() { // Event untuk sorting order
                fetchData(1);
            });


            // Event listener untuk klik paginasi
            $(document).on('click', '#pagination-container .page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page'); // Ambil nomor halaman dari data-page
                if (page) { // Pastikan page terdefinisi
                    fetchData(page);
                }
            });

            // Event handler untuk tombol hapus (tetap sama)
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                const id = $(this).data('id');

                if (confirm('Apakah Anda yakin ingin menghapus artikel ini?')) {
                    $.ajax({
                        url: api_url,
                        method: 'POST',
                        data: { action: 'delete', id: id }, // Kirim ID dan action via POST
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                alert(response.message);
                                fetchData(1); // Reload data, kembali ke halaman 1
                            } else {
                                alert('Gagal menghapus artikel: ' + response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert('Error menghapus artikel: ' + textStatus + ' ' + errorThrown);
                        }
                    });
                }
            });

            // Event handler untuk menampilkan form tambah artikel (tetap sama)
            $('#addArtikelBtn').on('click', function() {
                $('#formArtikel').slideDown();
                $('#formTitle').text('Tambah Artikel Baru');
                $('#artikelForm')[0].reset();
                $('#artikelId').val('');
                $('#submitBtn').text('Simpan').removeClass('btn-info').addClass('btn-primary');
                $('#currentGambarInfo').hide();
                $('#hapus_gambar_checkbox').prop('checked', false);
                loadCategoryFilterAndDropdown('', ''); // Muat kategori, tanpa terpilih
            });

            // Event handler untuk tombol batal di form (tetap sama)
            $('#cancelBtn').on('click', function() {
                $('#formArtikel').slideUp();
            });

            // Event handler untuk submit form Tambah/Edit (tetap sama, menggunakan FormData)
            $('#artikelForm').on('submit', function(e) {
                e.preventDefault();

                const artikelId = $('#artikelId').val();
                let action = '';
                if (artikelId) {
                    action = 'update';
                } else {
                    action = 'add';
                }

                const formData = new FormData(this);
                formData.append('action', action);

                $.ajax({
                    url: api_url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            $('#formArtikel').slideUp();
                            fetchData(1); // Reload data, kembali ke halaman 1
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Terjadi kesalahan AJAX: ' + textStatus + ' ' + errorThrown);
                        console.log(jqXHR.responseText);
                    }
                });
            });

            // Event handler untuk tombol Edit (tetap sama)
            $(document).on('click', '.btn-edit', function() {
                const id = $(this).data('id');
                $('#formArtikel').slideDown();
                $('#formTitle').text('Edit Artikel');
                $('#submitBtn').text('Update').removeClass('btn-primary').addClass('btn-info');
                $('#hapus_gambar_checkbox').prop('checked', false);

                $.ajax({
                    url: api_url + '?action=getArtikelById&id=' + id,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            const artikel = response.data;
                            $('#artikelId').val(artikel.id);
                            $('#judul').val(artikel.judul);
                            $('#isi').val(artikel.isi);
                            loadCategoryFilterAndDropdown(categoryFilter.val(), artikel.id_kategori); // Pilih kategori untuk form
                            if (artikel.gambar) {
                                $('#currentGambarName').text(artikel.gambar);
                                $('#currentGambarInfo').show();
                            } else {
                                $('#currentGambarInfo').hide();
                            }
                            $('#gambar').val('');
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

            // Initial load of categories for filters and forms
            loadCategoryFilterAndDropdown(); // Muat saat inisialisasi

            // Initial load of articles
            fetchData(1);
        });
    </script>
</body>
</html>