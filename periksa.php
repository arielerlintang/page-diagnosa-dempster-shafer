<?php include "header.php" ?>
<?php include "fungsi_dfs.php" ?>
<?php include "fungsi_ds.php" ?>


<?php

$gejala = [];

$ambil_gejala = $koneksi->query("SELECT * FROM gejala");
while ($detail_gejala = $ambil_gejala->fetch_assoc()) {
    $gejala[] = $detail_gejala;
}

// data untuk menampilkan gejala
$gejalas = [];

$ambil_gejalas = $koneksi->query("SELECT * FROM gejala");
while ($detail_gejalas = $ambil_gejalas->fetch_assoc()) {
    $gejalas[$detail_gejalas['id_gejala']] = $detail_gejalas;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['gejala']) {

    $gejala_terpilih = $_POST['gejala'];

    // array_keys digunakan untuk mapping ulang susuran array

    $detail_gejala = [];
    $gejala_map = array_keys($gejala_terpilih);

    foreach ($gejala_map as $id_gejala) {

        $perintah = "SELECT * FROM gejala WHERE id_gejala='$id_gejala'";

        $ambil = $koneksi->query($perintah);
        while ($detail = $ambil->fetch_assoc()) {
            $detail_gejala[] = $detail;
        }
    }

    // menampilkan data aturan berdasarkan id_gejala yang dipilih
    $aturan_terpilih = [];

    foreach ($gejala_map as $id_gejala) {

        $ambil_a = $koneksi->query("SELECT * FROM aturan
            LEFT JOIN gejala ON gejala.id_gejala = aturan.id_gejala
            LEFT JOIN penyakit ON penyakit.id_penyakit = aturan.id_penyakit
            WHERE aturan.id_gejala='$id_gejala'");

        while ($detail_a = $ambil_a->fetch_assoc())
            $aturan_terpilih[] = $detail_a;
    }

    $graf = bangunGraf($gejala_terpilih, $koneksi);

    // menenukan node awal dengan mengambil nilai id paling awal
    $node_awal = Key($gejala_terpilih);

    // fuction untuk menentukan rute dari dfs
    $hasil_dfs = pencarianDFS($graf, $node_awal);

    // function untuk perhitungan DFS
    $hasil_ds = hitungDS($gejala_terpilih, $koneksi);

    // Dapatkan semua nama penyakit untuk efisiensi
    $nama_penyakit_list = getAllNamaPenyakit($koneksi);

    // menampilkan nama dari rute DFS
    $rute_dfs = [];
    foreach ($hasil_dfs as $key => $value) {
        $rute_dfs[$key] = ambil_gejala($value, $koneksi);
    }
}

?>


<div class="container mt-5 pt-5 mb-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <h1>Silahkan pilih</h1>
                </div>
                <form method="post">
                    <div class="card-body row">
                        <?php foreach ($gejala as $key => $value): ?>
                            <div class="col-md-6">
                                <input type="checkbox" name="gejala[<?php echo $value['id_gejala'] ?>]" class="form-check-input" <?php if (isset($gejala_terpilih[$value['id_gejala']])) {
                                    echo "checked";
                                } ?>>
                                <label><?php echo $value['nama_gejala'] ?></label>
                            </div>
                        <?php endforeach ?>

                        <div class="mt-3">
                            <button class="btn btn-primary btn-lg" type="submit">Proses</button>
                            <a href="" class="btn btn-danger btn-lg">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Jika data tidak kosong, maka muculkan data dalam tabel -->
        <?php if (!empty($gejala_terpilih)) { ?>

            <div class="col-md-12 table-responsive mb-3">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h5 class="text-white">1. Tabel Gejala yang Dipilih</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail_gejala as $key => $value) : ?>
                                    <tr>
                                        <td><?php echo $key + 1; ?></td>
                                        <td><?php echo $value['kode_gejala']; ?></td>
                                        <td><?php echo $value['nama_gejala']; ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>

            <div class="col-md-12 table-responsive mb-3">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h5 class="text-white">2. Tabel Aturan yang Dipilih</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Gejala</th>
                                    <th>Penyakit</th>
                                    <th>CF</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aturan_terpilih as $key => $value) :  ?>
                                    <tr>
                                        <td><?php echo $key + 1; ?></td>
                                        <td><?php echo $value['nama_gejala']; ?></td>
                                        <td><?php echo $value['nama_penyakit']; ?></td>
                                        <td><?php echo $value['bobot']; ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>

            <div class="col-md-12 table-responsive mb-3">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h1 class="text-white">3. Hasil Depth First Search</h1>
                    </div>
                    <div class="card-body">
                        <p>Jalur Pencarian : <?php echo implode(" <i class='bi bi-arrow-right'></i> ", $rute_dfs); ?> </p>

                        <h3>Perhitungan DFS</h3>
                        <p>
                            Depth First Search Melakukan berdasarkan struktur graft
                        </p>
                        <p>dimulai dari gejala <strong> <?php echo $gejalas[$node_awal]['nama_gejala']; ?></strong>, penelusuran dilakukan pada semua node yang terhubung secara rekrusif</p>

                        
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Node</th>
                                    <th>Hubungan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($graf as $node => $koneksi_list) : ?>
                                    <tr>
                                        <td><?php echo  konversiNamaNode($node,$koneksi) ?></td>
                                        <td>
                                            <?php 
                                            $nama_koneksi = array_map(function ($k) use ($koneksi)  {
                                                return konversiNamaNode($k,$koneksi);
                                            }, $koneksi_list);

                                            echo implode(" -> ",$nama_koneksi);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-12 table-responsive mb-3">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h1 class="text-white">4. Perhitungan Dempster Shafer</h1>
                    </div>
                    <div class="card-body">

                        <?php for($i = 1; $i < count($hasil_ds['densitas_list']); $i++){ ?>
                            <h6>A - Densitas Awal</h6>
                            <table class="table table-bordered center-text">
                                <thead>
                                    <tr>
                                        <th>Densitas</th>
                                        <th>Penyakit</th>
                                        <th>Believe</th>
                                        <th>Plausibility</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hasil_ds['densitas_list'][$i-1] as $id_penyakit => $nilai): ?>
                                        <?php if ($id_penyakit !== 'kombinasi' && $id_penyakit !== 'konflik' && $id_penyakit !== 'perhitungan'): ?>
                                            <tr>
                                                <td>m<?php echo $i; ?> (<?php echo $id_penyakit === 'θ' ? 'θ' : $nama_penyakit_list[$id_penyakit] ?? "Penyakit $id_penyakit"; ?>)</td>
                                                <td>
                                                    <?php 
                                                    if ($id_penyakit === 'θ') {
                                                        echo 'θ (Tidak Diketahui)';
                                                    } else {
                                                        echo $nama_penyakit_list[$id_penyakit] ?? "Penyakit $id_penyakit";
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($nilai, 3) ?></td>
                                                <td><?php echo number_format(1 - $nilai, 3); ?></td>
                                            </tr>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </tbody>
                            </table>

                            <h6>B - Kombinasi Densitas</h6>
                            <?php if (isset($hasil_ds['kombinasi_list'][$i])): ?>
                                <table class="table table-bordered center-text">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <?php foreach ($hasil_ds['kombinasi_list'][$i]['header'] as $header): ?>
                                                <th class="text-center">
                                                    <?php 
                                        // Ubah header untuk menampilkan nama penyakit
                                                    $header_text = $header;
                                                    if (preg_match('/M(\d+)\(([^)]+)\)/', $header, $matches)) {
                                                        $m_number = $matches[1];
                                                        $diseases = $matches[2];

                                                        if ($diseases === 'θ' || $diseases === ' θ ') {
                                                            $header_text = "M$m_number (θ)";
                                                        } else {
                                                            $disease_names = [];
                                                            $disease_ids = explode(', ', $diseases);
                                                            foreach ($disease_ids as $disease_id) {
                                                                $disease_id = trim($disease_id);
                                                                if (is_numeric($disease_id)) {
                                                                    $disease_names[] = $nama_penyakit_list[$disease_id] ?? "Penyakit $disease_id";
                                                                }
                                                            }
                                                            if (!empty($disease_names)) {
                                                                $header_text = "M$m_number (" . implode(', ', $disease_names) . ")";
                                                            }
                                                        }
                                                    }
                                                    echo $header_text;
                                                    ?>
                                                    <hr>
                                                    <?php 
                                        // Ambil nilai dari header asli
                                                    if (preg_match('/[\d.]+$/', $header, $value_matches)) {
                                                        echo $value_matches[0];
                                                    }
                                                    ?>
                                                </th>
                                            <?php endforeach ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($hasil_ds['kombinasi_list'][$i]['rows'] as $baris): ?>
                                            <tr>
                                                <th class="text-center">
                                                    <?php 
                                        // Ubah header baris untuk menampilkan nama penyakit
                                                    $baris_header = $baris['header'];
                                                    if (preg_match('/M(\d+)\s*\(([^)]+)\)/', $baris_header, $matches)) {
                                                        $m_number = $matches[1];
                                                        $disease_id = trim($matches[2]);

                                                        if ($disease_id === 'θ') {
                                                            $disease_name = 'θ';
                                                        } else {
                                                            $disease_name = $nama_penyakit_list[$disease_id] ?? "Penyakit $disease_id";
                                                        }

                                                        $baris_header = preg_replace('/M(\d+)\s*\([^)]+\)/', "M$m_number ($disease_name)", $baris_header);
                                                    }
                                                    echo $baris_header;
                                                    ?>
                                                </th>
                                                <?php foreach ($baris['sel_list'] as $sel): ?>
                                                    <td class="text-center">
                                                        <?php 
                                            // Ubah hasil untuk menampilkan nama penyakit
                                                        $hasil_text = $sel['hasil'];
                                                        if ($hasil_text !== 'konflik' && $hasil_text !== 'θ' && is_numeric($hasil_text)) {
                                                            $hasil_text = $nama_penyakit_list[$hasil_text] ?? "Penyakit $hasil_text";
                                                        } elseif ($hasil_text === 'θ') {
                                                            $hasil_text = 'θ';
                                                        }
                                                        echo $hasil_text;
                                                        ?>
                                                        <hr>
                                                        <?php echo number_format($sel['nilai'], 3) ?>
                                                    </td>
                                                <?php endforeach ?>
                                            </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                </table>
                            <?php endif ?>

                            <h6>C - Perhitungan Dempster Shafer</h6>

                            <table class="table">
                                <tbody>
                                    <?php foreach ($hasil_ds['densitas_list'][$i]['perhitungan'] as $key => $value): ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <?php 
                                        // Ubah id penyakit menjadi nama penyakit
                                                    $penyakit_id = $value['penyakit'];
                                                    if ($penyakit_id === 'θ') {
                                                        echo 'θ (Tidak Diketahui)';
                                                    } else {
                                                        echo $nama_penyakit_list[$penyakit_id] ?? "Penyakit $penyakit_id";
                                                    }
                                                    ?>
                                                </strong>
                                            </td>
                                            <td>
                                                : <?php echo $value['rumus'] ?>
                                                <br>
                                                : <?php echo $value['langkah1'] ?>
                                                <br>
                                                : <?php echo $value['hasil'] ?>
                                            </td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>

                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h6 class="text-white">Hasil Akhir</h6>
                    </div>
                    <div class="card-body">
                        <h6>Berdasarkan perhitungan di atas, maka hasil hipotesa berdasarkan gejala yang diinputkan adalah:</h6>
                        <ol>
                            <?php foreach ($hasil_ds['hasil_akhir'] as $value) : ?>
                                <li>Penyakit <strong>
                                    <a href="periksa_detail.php?id=<?php echo $value['id_penyakit'] ?>" target="_BLANK">
                                        <?php echo $value['nama_penyakit'] ?>
                                    </a>
                                </strong>, Dengan persentase sebesar <strong><?php echo number_format($value['persentase'], 2); ?>%</strong></li>
                            <?php endforeach ?>
                        </ol>
                    </div>
                </div>
            </div>



        <?php } ?>

    </div>
</div>

<?php include "footer.php" ?>
