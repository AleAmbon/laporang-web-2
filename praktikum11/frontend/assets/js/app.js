const { createApp } = Vue;

const apiUrl = 'http://localhost/praktikum11/backend/api_artikel.php';

createApp({
    data() {
        return {
            artikel: [], 
            kategoriOptions: [], 
            formData: {  
                id: null,
                judul: '',
                isi: '',
                status: 'published', 
                id_kategori: '',
                gambar_file: null, 
                current_gambar: null, 
                hapus_gambar: false, 
            },
            showForm: false, 
            formTitle: 'Tambah Data', 
            statusOptions: [ 
                { text: 'Draft', value: 'draft' }, 
                { text: 'Publish', value: 'published' }
            ]
        };
    },
    mounted() {
        this.loadData();
        this.loadKategoriOptions(); 
    },
    methods: {
        // Fungsi untuk memuat data artikel dari API
        loadData() {
            axios.get(apiUrl + '?action=getData')
                .then(response => {
                    if (response.data && response.data.artikel) {
                        this.artikel = response.data.artikel;
                    } else {
                        console.warn("API response does not contain 'artikel' array:", response.data);
                        this.artikel = [];
                    }
                })
                .catch(error => {
                    console.error("Error loading data:", error);
                    alert("Gagal memuat data artikel. Cek konsol untuk detail.");
                });
        },

        // Fungsi untuk memuat daftar kategori untuk dropdown
        loadKategoriOptions() {
            axios.get(apiUrl + '?action=getKategori') // Menggunakan action=getKategori
                .then(response => {
                    this.kategoriOptions = response.data; // Asumsikan respons langsung array kategori
                })
                .catch(error => {
                    console.error("Error loading categories:", error);
                    alert("Gagal memuat daftar kategori.");
                });
        },

        // Fungsi untuk menangani upload file dari input type="file"
        handleFileUpload(event) {
            this.formData.gambar_file = event.target.files[0];
        },

        // Fungsi untuk membuka form "Tambah Data"
        tambah() {
            this.showForm = true;
            this.formTitle = 'Tambah Data';
            this.formData = { // Reset formData
                id: null,
                judul: '',
                isi: '',
                status: 'published', // Default status untuk tambah
                id_kategori: '',
                gambar_file: null,
                current_gambar: null,
                hapus_gambar: false,
            };
            document.getElementById('gambar').value = ''; // Kosongkan input file secara manual
            this.loadKategoriOptions(); // Muat ulang kategori
        },

        // Fungsi untuk menghapus artikel
        hapus(index, id) {
            if (confirm('Yakin menghapus data ini?')) {
                // Untuk DELETE di API polosan, kita pakai POST dengan _method=DELETE
                axios.post(apiUrl, { action: 'delete', id: id, _method: 'DELETE' })
                    .then(response => {
                        if (response.data.status === 200) {
                            alert(response.data.messages.success);
                            this.artikel.splice(index, 1); // Hapus dari array Vue tanpa reload penuh
                        } else {
                            alert('Gagal menghapus: ' + (response.data.error || response.data.message));
                        }
                    })
                    .catch(error => {
                        console.error("Error deleting data:", error.response || error);
                        alert("Gagal menghapus data. Cek konsol untuk detail.");
                    });
            }
        },

        // Fungsi untuk membuka form "Ubah Data"
        edit(data) {
            this.showForm = true;
            this.formTitle = 'Ubah Data';
            // Isi formData dengan data artikel yang akan diedit
            this.formData = {
                id: data.id,
                judul: data.judul,
                isi: data.isi,
                status: data.status,
                id_kategori: data.id_kategori,
                gambar_file: null, // Reset input file
                current_gambar: data.gambar, // Simpan nama gambar saat ini
                hapus_gambar: false, // Reset checkbox
            };
            document.getElementById('gambar').value = ''; // Kosongkan input file secara manual
            this.loadKategoriOptions(); // Muat ulang kategori
            console.log('Edit item:', this.formData);
        },

        // Fungsi untuk menyimpan data (Tambah atau Ubah)
        saveData() {
            // Buat FormData untuk mengirim data, termasuk file
            const formData = new FormData();
            formData.append('id', this.formData.id || '');
            formData.append('judul', this.formData.judul);
            formData.append('isi', this.formData.isi);
            formData.append('status', this.formData.status);
            formData.append('id_kategori', this.formData.id_kategori);

            if (this.formData.gambar_file) {
                formData.append('gambar', this.formData.gambar_file);
            }
            if (this.formData.hapus_gambar) {
                formData.append('hapus_gambar', 'on'); // Kirim 'on' jika dicentang
            }

            let request;
            if (this.formData.id) {
                // Untuk UPDATE, gunakan POST dengan _method=PUT
                formData.append('action', 'update');
                formData.append('_method', 'PUT'); // Override method ke PUT
                request = axios.post(apiUrl, formData);
            } else {
                // Untuk ADD, gunakan POST
                formData.append('action', 'add');
                request = axios.post(apiUrl, formData);
            }

            request.then(response => {
                if (response.data.status === 201 || response.data.status === 200) {
                    alert(response.data.messages.success);
                    this.showForm = false; // Tutup form modal
                    this.loadData(); // Muat ulang data artikel
                } else {
                    alert('Gagal menyimpan: ' + (response.data.error || response.data.message));
                }
            })
            .catch(error => {
                console.error("Error saving data:", error.response || error);
                alert("Gagal menyimpan data. Cek konsol untuk detail.");
            });
        },


        statusText(status) {
            return status === 'published' ? 'Publish' : 'Draft';
        }
    }
}).mount('#app');