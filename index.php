<?php
session_start();

// Inisialisasi data UKT jika belum ada
if (!isset($_SESSION['uktData'])) {
    $_SESSION['uktData'] = [];
}

// Fungsi untuk menghitung median
function calculateMedian($data) {
    $count = count($data);
    if ($count === 0) return 0;

    sort($data);
    $middle = floor($count / 2);

    if ($count % 2) {
        return $data[$middle];
    } else {
        return ($data[$middle - 1] + $data[$middle]) / 2;
    }
}

// Fungsi untuk menghitung statistik 5 serangkai
function getStatistics($data) {
    if (empty($data)) return null;

    sort($data);
    $min = $data[0];
    $max = $data[count($data) - 1];
    $median = calculateMedian($data);
    $q1 = calculateMedian(array_slice($data, 0, floor(count($data) / 2)));
    $q3 = calculateMedian(array_slice($data, ceil(count($data) / 2)));

    return [
        'Min' => $min,
        'Q1 (Kuartil 1)' => $q1,
        'Median' => $median,
        'Q3 (Kuartil 3)' => $q3,
        'Max' => $max,
    ];
}

// Fungsi untuk menghitung pencilan
function getOutliers($data) {
    if (empty($data)) return null;

    $stats = getStatistics($data);
    $iqr = $stats['Q3 (Kuartil 3)'] - $stats['Q1 (Kuartil 1)'];
    $lowerBound = $stats['Q1 (Kuartil 1)'] - 1.5 * $iqr;
    $upperBound = $stats['Q3 (Kuartil 3)'] + 1.5 * $iqr;

    $outliers = array_filter($data, function($value) use ($lowerBound, $upperBound) {
        return $value < $lowerBound || $value > $upperBound;
    });

    return [
        'Batas Bawah' => $lowerBound,
        'Batas Atas' => $upperBound,
        'Data Pencilan' => empty($outliers) ? 'Tidak ada pencilan' : implode(', ', $outliers),
    ];
}

// Fungsi untuk menghitung standar deviasi
function getStandardDeviation($data) {
    if (empty($data)) return 0;

    $mean = array_sum($data) / count($data);
    $variance = array_sum(array_map(function($value) use ($mean) {
        return pow($value - $mean, 2);
    }, $data)) / count($data);

    return sqrt($variance);
}

// Menambahkan data baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama'], $_POST['nim'], $_POST['alamat'], $_POST['prodi'], $_POST['ukt'])) {
    $nama = $_POST['nama'];
    $nim = $_POST['nim'];
    $alamat = $_POST['alamat'];
    $prodi = $_POST['prodi'];
    $ukt = floatval($_POST['ukt']);

    $_SESSION['uktData'][] = [
        'nama' => $nama,
        'nim' => $nim,
        'alamat' => $alamat,
        'prodi' => $prodi,
        'ukt' => $ukt,
    ];
}

// Ambil data UKT untuk operasi statistik
$uktValues = array_column($_SESSION['uktData'], 'ukt');

// Variabel untuk menampilkan hasil berdasarkan tombol
$statisticsResult = null;
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'statistics') {
        $statisticsResult = getStatistics($uktValues);
    } elseif ($_POST['action'] === 'outliers') {
        $statisticsResult = getOutliers($uktValues);
    } elseif ($_POST['action'] === 'stdDev') {
        $statisticsResult = [
            'Standar Deviasi' => getStandardDeviation($uktValues),
        ];
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Data UKT Mahasiswa</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 20px;
    }

    td,
    th {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    input,
    button {
        padding: 10px;
        margin: 5px 0;
        width: 100%;
    }
    </style>
</head>

<body>

    <h2>Data UKT Mahasiswa</h2>

    <!-- Form untuk memasukkan data -->
    <form method="POST">
        <label for="nama">Nama:</label>
        <input type="text" id="nama" name="nama" required>

        <label for="nim">NIM:</label>
        <input type="text" id="nim" name="nim" required>

        <label for="alamat">Alamat:</label>
        <input type="text" id="alamat" name="alamat" required>

        <label for="prodi">Prodi:</label>
        <input type="text" id="prodi" name="prodi" required>

        <label for="ukt">UKT:</label>
        <input type="number" id="ukt" name="ukt" required>

        <button type="submit">Tambah Data</button>
    </form>

    <!-- Tabel Data UKT -->
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>NIM</th>
                <th>Alamat</th>
                <th>Prodi</th>
                <th>UKT</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['uktData'] as $data): ?>
            <tr>
                <td><?= htmlspecialchars($data['nama']) ?></td>
                <td><?= htmlspecialchars($data['nim']) ?></td>
                <td><?= htmlspecialchars($data['alamat']) ?></td>
                <td><?= htmlspecialchars($data['prodi']) ?></td>
                <td><?= number_format($data['ukt'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Tombol Statistik -->
    <h3>Operasi Statistik</h3>
    <form method="POST">
        <button type="submit" name="action" value="statistics">Tampilkan Statistik 5 Serangkai</button>
        <button type="submit" name="action" value="outliers">Tampilkan Data Pencilan</button>
        <button type="submit" name="action" value="stdDev">Tampilkan Standar Deviasi</button>
    </form>

    <!-- Hasil Statistik -->
    <h3>Hasil</h3>
    <?php if ($statisticsResult !== null): ?>
    <?php foreach ($statisticsResult as $key => $value): ?>
    <p><strong><?= htmlspecialchars($key) ?>:</strong>
        <?= htmlspecialchars(is_array($value) ? implode(', ', $value) : $value) ?></p>
    <?php endforeach; ?>
    <?php else: ?>
    <p>Belum ada hasil yang ditampilkan.</p>
    <?php endif; ?>

</body>

</html>