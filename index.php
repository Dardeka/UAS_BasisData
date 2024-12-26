<?php
require 'DatabaseHelper.php';

$conn = getDatabaseConnection();

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

function getOutliers($data) {
    $stats = getStatistics($data);
    $iqr = $stats['Q3 (Kuartil 3)'] - $stats['Q1 (Kuartil 1)'];
    $lowerBound = $stats['Q1 (Kuartil 1)'] - 1.5 * $iqr;
    $upperBound = $stats['Q3 (Kuartil 3)'] + 1.5 * $iqr;

    $outliers = array_filter($data, function ($value) use ($lowerBound, $upperBound) {
        return $value < $lowerBound || $value > $upperBound;
    });

    return [
        'Batas Bawah' => $lowerBound,
        'Batas Atas' => $upperBound,
        'Data Pencilan' => empty($outliers) ? 'Tidak ada pencilan' : implode(', ', $outliers),
    ];
}

function getStandardDeviation($data) {
    if (empty($data)) return 0;

    $mean = array_sum($data) / count($data);
    $variance = array_sum(array_map(function ($value) use ($mean) {
        return pow($value - $mean, 2);
    }, $data)) / count($data);

    return sqrt($variance);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama'], $_POST['nim'], $_POST['alamat'], $_POST['prodi'], $_POST['ukt'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $nim = $conn->real_escape_string($_POST['nim']);
    $alamat = $conn->real_escape_string($_POST['alamat']);
    $prodi = $conn->real_escape_string($_POST['prodi']);
    $ukt = (float)$_POST['ukt'];

    $conn->query("INSERT INTO mahasiswa (nama, nim, alamat, prodi, ukt) VALUES ('$nama', '$nim', '$alamat', '$prodi', $ukt)");
}

$result = $conn->query("SELECT ukt FROM mahasiswa");
$uktValues = [];
while ($row = $result->fetch_assoc()) {
    $uktValues[] = $row['ukt'];
}

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
    table {
        border-collapse: collapse;
        width: 100%;
    }

    td,
    th {
        border: 1px solid #ddd;
        padding: 8px;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    button,
    input {
        margin: 5px 0;
    }
    </style>
</head>

<body>

    <h2>Data UKT Mahasiswa</h2>
    <form method="POST">
        <label>Nama:</label><br>
        <input type="text" name="nama" required><br>
        <label>NIM:</label><br>
        <input type="text" name="nim" required><br>
        <label>Alamat:</label><br>
        <input type="text" name="alamat" required><br>
        <label>Prodi:</label><br>
        <input type="text" name="prodi" required><br>
        <label>UKT:</label><br>
        <input type="number" name="ukt" step="0.01" required><br>
        <button type="submit">Tambah Data</button>
    </form>

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
            <?php
        $result = $conn->query("SELECT * FROM mahasiswa");
        while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['nim']) ?></td>
                <td><?= htmlspecialchars($row['alamat']) ?></td>
                <td><?= htmlspecialchars($row['prodi']) ?></td>
                <td><?= number_format($row['ukt'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <h3>Operasi Statistik</h3>
    <form method="POST">
        <button type="submit" name="action" value="statistics">Tampilkan Statistik 5 Serangkai</button>
        <button type="submit" name="action" value="outliers">Tampilkan Data Pencilan</button>
        <button type="submit" name="action" value="stdDev">Tampilkan Standar Deviasi</button>
    </form>

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