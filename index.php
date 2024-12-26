<?php
session_start();

if (!isset($_SESSION['uktData'])) {
    $_SESSION['uktData'] = [];
}

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

function getStatistics($data) {
    if (empty($data)) return null;

    sort($data);
    $min = $data[0];
    $max = $data[count($data) - 1];
    $median = calculateMedian($data);
    $q1 = calculateMedian(array_slice($data, 0, floor(count($data) / 2)));
    $q3 = calculateMedian(array_slice($data, ceil(count($data) / 2)));

    return [
        'min' => $min,
        'q1' => $q1,
        'median' => $median,
        'q3' => $q3,
        'max' => $max,
    ];
}

function getOutliers($data) {
    if (empty($data)) return null;

    $stats = getStatistics($data);
    $iqr = $stats['q3'] - $stats['q1'];
    $lowerBound = $stats['q1'] - 1.5 * $iqr;
    $upperBound = $stats['q3'] + 1.5 * $iqr;

    $outliers = array_filter($data, function($value) use ($lowerBound, $upperBound) {
        return $value < $lowerBound || $value > $upperBound;
    });

    return [
        'lowerBound' => $lowerBound,
        'upperBound' => $upperBound,
        'outliers' => $outliers,
    ];
}

function getStandardDeviation($data) {
    if (empty($data)) return 0;

    $mean = array_sum($data) / count($data);
    $variance = array_sum(array_map(function($value) use ($mean) {
        return pow($value - $mean, 2);
    }, $data)) / count($data);

    return sqrt($variance);
}

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

$uktValues = array_column($_SESSION['uktData'], 'ukt');
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

    <!-- Statistik -->
    <h3>Hasil Statistik</h3>
    <?php if (!empty($uktValues)): ?>
    <p>
        <?php $stats = getStatistics($uktValues); ?>
        <strong>Statistik 5 Serangkai:</strong><br>
        Minimum: <?= $stats['min'] ?><br>
        Q1: <?= $stats['q1'] ?><br>
        Median: <?= $stats['median'] ?><br>
        Q3: <?= $stats['q3'] ?><br>
        Maximum: <?= $stats['max'] ?><br>
    </p>
    <p>
        <?php $outliers = getOutliers($uktValues); ?>
        <strong>Data Pencilan:</strong><br>
        Batas Bawah: <?= $outliers['lowerBound'] ?><br>
        Batas Atas: <?= $outliers['upperBound'] ?><br>
        Data Pencilan: <?= implode(', ', $outliers['outliers']) ?: 'Tidak ada' ?><br>
    </p>
    <p>
        <strong>Standar Deviasi:</strong> <?= number_format(getStandardDeviation($uktValues), 2, ',', '.') ?>
    </p>
    <?php else: ?>
    <p>Tidak ada data untuk statistik.</p>
    <?php endif; ?>

</body>

</html>