<?php
session_start(); // Memulai sesi

$google_webhook_url = "https://script.google.com/macros/s/AKfycbxvV8OHBSDRZuOuHJF4aZtbyLiJSR6vQT53i9nMYE4KM_bFm8mQX8QfGlIVBZiq95Ls/exec"; // Ganti dengan URL Web App Anda

// Inisialisasi nomor antrian dari file (bisa diganti dengan database jika diperlukan)
$queue_file = "queue_number.txt";
if (!file_exists($queue_file)) {
    file_put_contents($queue_file, "0");
}

$queue_number = (int) file_get_contents($queue_file) + 1;
file_put_contents($queue_file, $queue_number);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil nomor antrian saat ini
    $queue_number = $_SESSION['queue_number'];
    $postData = [
        'queue_number' => $queue_number,
        'name' => $_POST['name'] ?? '',
        'nik' => $_POST['nik'] ?? '',
        'department' => $_POST['department'] ?? '',
        'option' => $_POST['option'] ?? '',
        'duration' => $_POST['duration'] ?? '',
        'size' => $_POST['size'] ?? '',
        'material' => $_POST['material'] ?? '',
        'dimension' => $_POST['dimension'] ?? '',
        'message' => $_POST['message'] ?? '',
        'flayer_message' => $_POST['flayer_message'] ?? '',
    ];

    // Proses Upload File dengan Validasi JPG, PNG, dan Ukuran Maksimal 1MB
    if (!empty($_FILES['file']['name'])) {
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_name = basename($_FILES['file']['name']);
        $file_type = mime_content_type($file_tmp);
        $file_size = $_FILES['file']['size'];

        if (($file_type === 'image/jpeg' || $file_type === 'image/png') && $file_size <= 1048576) { // 1MB = 1048576 bytes
            $file_data = base64_encode(file_get_contents($file_tmp));
            $postData['file_name'] = $file_name;
            $postData['file_data'] = $file_data;
        } else {
            echo "<div class='alert alert-danger'>Hanya file JPG dan PNG dengan ukuran maksimal 1MB yang diperbolehkan!</div>";
            exit;
        }
    }

    $ch = curl_init($google_webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    // Simpan pesan sukses di sesi
    $_SESSION['success_message'] = "Selamat! Request Anda Dalam Antrian. Nomor Antrian Anda: <strong>$queue_number</strong>";

    // Tambahkan nomor antrian hanya jika formulir dikirim
    $_SESSION['queue_number']++;

    // Redirect agar POST tidak dikirim ulang jika user me-refresh halaman
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Google Form Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .form-container {
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
            font-weight: 500;
            color: #673AB7;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #333;
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            border: 1px solid #ccc;
            transition: all 0.3s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #673AB7;
            box-shadow: 0 0 5px rgba(103, 58, 183, 0.5);
        }

        .btn-primary {
            background-color: #673AB7;
            border: none;
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background-color: #512DA8;
        }

        .card {
            background-color: #fff;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        .alert.alert-success {
            position: absolute;
            top: 0;
        }

        .radiobtn>input {
            margin-left: 22px;
        }

        .radiobtn {
            padding: 20px 0;
        }
    </style>

    <script>
        function toggleFields() {
            var option = document.querySelector('input[name="option"]:checked').value;
            document.getElementById('videoFields').style.display = option === 'Video' ? 'block' : 'none';
            document.getElementById('flayerFields').style.display = option === 'Design Flayer' ? 'block' : 'none';
            document.getElementById('printFields').style.display = option === 'Design Cetak' ? 'block' : 'none';
        }
    </script>
</head>

<body class="container mt-5">
    <div class="form-container">
        <h2 class="mb-4">E-Ticket Request Design Atau Video</h2>
        <!-- Menampilkan Alert Sukses Jika Ada -->
        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="alert alert-success">
                <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan 
            ?>
        <?php endif; ?>

        <!-- Menampilkan Nomor Antrian -->
        <div class="queue-box alert alert-primary" role="alert">Nomor Antrian Anda: <span id="queueNumber"><?= $_SESSION['queue_number'] ?></span></div>


        <form method="post" enctype="multipart/form-data">
            <div class="card mb-3">
                <label class="form-label">Nama</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="card mb-3">
                <label class="form-label">NIK</label>
                <input type="number" name="nik" class="form-control" required>
            </div>
            <div class="card mb-3">
                <label class="form-label">Departement</label>
                <input type="text" name="department" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mau Dibikin Apa?</label>
                <div class="radiobtn">
                    <input type="radio" name="option" value="Video" required onclick="toggleFields()"> Video
                    <input type="radio" name="option" value="Design Flayer" onclick="toggleFields()"> Design Flayer
                    <input type="radio" name="option" value="Design Cetak" onclick="toggleFields()"> Design Cetak
                </div>

            </div>
            <div id="videoFields" style="display: none;">
                <div class="card mb-3">
                    <label class="form-label">Durasi (menit)</label>
                    <input type="text" name="duration" class="form-control">
                </div>
                <div class="card mb-3">
                    <label class="form-label">Dimensi landscape atau portrait</label>
                    <input type="text" name="size" class="form-control">
                </div>
            </div>
            <div id="flayerFields" style="display: none;">
                <div class="card mb-3">
                    <label class="form-label">Pilih Design</label>
                    <select name="material" class="form-control">
                        <option value="">-- Silahkan Pilih Design --</option>
                        <option value="OPL">OPL</option>
                        <option value="Undangan">Undangan</option>
                    </select>
                </div>
                <div class="card mb-3">
                    <label class="form-label">Isi Materi Design</label>
                    <textarea name="flayer_message" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div id="printFields" style="display: none;">
                <div class="card mb-3">
                    <label class="form-label">Ukuran (Lebar x Panjang) dalam satuan cm Contoh: 200x300 cm</label>
                    <input type="text" name="dimension" class="form-control">
                </div>
            </div>
            <div class="card mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="message" class="form-control" rows="3" required></textarea>
            </div>
            <div class="card mb-3">
                <label class="form-label">Upload Referensi design (Hanya JPG & PNG, Maks 1MB)</label>
                <input type="file" name="file" class="form-control" accept="image/jpeg, image/png">
            </div>
            <button type="submit" class="btn btn-primary">Kirim</button>
        </form>
    </div>

</body>

</html>