<?php
require 'DatabaseHelper.php';

$db = new DatabaseHelper();

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

// Tambahkan data ke database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama'], $_POST['nim'], $_POST['alamat'], $_POST['prodi'], $_POST['ukt'])) {
    $sql = "INSERT INTO mahasiswa (nama, nim, alamat, prodi, ukt) VALUES (?, ?, ?, ?, ?)";
    $db->execute($sql, [$_POST['nama'], $_POST['nim'], $_POST['alamat'], $_POST['prodi'], $_POST['ukt']]);
}

// Ambil data UKT
$result = $db->query("SELECT ukt FROM mahasiswa");
$uktValues = [];
while ($row = $result->fetch_assoc()) {
    $uktValues[] = $row['ukt'];
}

// Variabel untuk hasil statistik
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
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="inputResult">
        <div class="forms">
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
                <button type="submit" class="addData">Tambah Data</button>
            </form>
        </div>
        <div class="result">
            <h3>Operasi Statistik</h3>
            <form method="POST" class="operator">
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
        </div>
    </div>



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
        $result = $db->query("SELECT * FROM mahasiswa");
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
</body>

</html>