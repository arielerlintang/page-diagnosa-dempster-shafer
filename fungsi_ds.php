<?php

function hitungDS($gejala_terpilih, $koneksi)
{

    // dapatkan semua list dan gejala dengan bobot
    $aturan_list = [];
    $nama_penyakit_list = [];

    foreach ($gejala_terpilih as $id_gejala => $nilai) {

        $perintah = "SELECT a.id_penyakit, p.nama_penyakit, a.bobot FROM aturan a JOIN penyakit p ON a.id_penyakit = p.id_penyakit WHERE a.id_gejala = $id_gejala";

        $ambil = $koneksi->query($perintah);
        while ($detail = $ambil->fetch_assoc()) {

            $aturan_list[$id_gejala][$detail['id_penyakit']] = $detail['bobot'];
            $nama_penyakit_list[$detail['id_penyakit']] = $detail['nama_penyakit'];
        }
    };

    // Persiapan Dempster Shafer
    $kombinasi_list = [];
    $langkah = 0;
    $m = [];
    $m_kombinasi_list = [];
    $densitas_list = [];

    // mendapatkan bukti pertama
    $id_gejala_pertama = key($gejala_terpilih);
    // tolong penjelasannya
    $aturan_pertama = isset($aturan_list[$id_gejala_pertama]) ? $aturan_list[$id_gejala_pertama] : [];

    // inialisasi bukti pertama
    if (!empty($aturan_pertama)) {
        foreach ($aturan_pertama as $id_penyakit => $bobot) {
            // konversi integer ke desimal
            $m[0][$id_penyakit] = $bobot / 100;
        }
        // penyakit teta( θ ) adalah penyakit yg tidak diketahui (KONFLIK)
        $m[0]['θ'] = 1 - array_sum($m[0]);
        $densitas_list[] = $m[0];
    }
    // hapus bukti pertama dari daftar terpilih, karna sudah masuk kedalam densitas list
    unset($gejala_terpilih[$id_gejala_pertama]);

    $langkah = 1;
    foreach ($gejala_terpilih as $id_gejala => $nilai) {
        // jika listnya kosong, maka hitung m-nya
        if (!isset($aturan_list[$id_gejala]) || empty($aturan_list[$id_gejala])) continue;

        $m_saat_ini = [];
        foreach ($aturan_list[$id_gejala] as $id_penyakit => $bobot) {
            $m_saat_ini[$id_penyakit] = $bobot / 100;
        }

        $m_saat_ini['θ'] = 1 - array_sum($m_saat_ini);

        // hitung kombinasi
        $m_baru = [];
        $konflik = 0;

        // buat table untuk kombinasi visualisasi
        $tabel_kombinasi = [
            'header' => ['', ''],
            'rows' => []
        ];

        // set header untuk M2
        $header_m2 = "M" . ($langkah + 1) . "(";
        foreach ($m_saat_ini as $id_penyakit => $nilai) {
            if ($id_penyakit != 'θ') {
                $header_m2 .= $id_penyakit . ", ";
            }
        }

        $header_m2 = rtrim($header_m2, ", ") . ")";

        // Perhitungan tabel kombinasi
        $tabel_kombinasi['header'][0] = $header_m2 . "<hr>" . number_format($m_saat_ini[$id_penyakit], 3);

        // mendapatkan nilai dari teta
        $tabel_kombinasi['header'][1] = "M" . ($langkah + 1) . "( θ ) <hr>" . number_format($m_saat_ini["θ"], 3);

        
        // hitung kombinasi isi tabel
        
        foreach ($m[$langkah - 1] as $m1_penyakit => $m1_nilai) {

            $baris = ['header' => ''];

            if ($m1_penyakit == 'θ') {
                $baris['header'] = "M" . $langkah . " (θ) <hr>" . number_format($m1_nilai, 3);
            } else {
                $baris['header'] = "M" . $langkah . "(" . $m1_penyakit . ") <hr>" . number_format($m1_nilai, 3);
            }

            $sel_list = [];
            foreach ($m_saat_ini as $m2_penyakit => $m2_nilai) {

                $sel = [];
                $nilai_sel = $m1_nilai * $m2_nilai;

                // jika m1 penyakit sama dengan 0 dan m2 penyakit sama dengan 0, maka sel menampung hasil penjumlahan irisian
                if ($m1_penyakit == 'θ' && $m2_penyakit == 'θ') {
                    $irisan = 'θ';
                    $m_baru[$irisan] = isset($m_baru[$irisan]) ? $m_baru[$irisan] + $nilai_sel : $nilai_sel;
                    $sel['hasil'] = 'θ';
                }
                // selain itu jika m1 penyakit sama dengan 0 maka
                elseif ($m1_penyakit == 'θ') {
                    $irisan = $m2_penyakit;
                    $m_baru[$irisan] = isset($m_baru[$irisan]) ? $m_baru[$irisan] + $nilai_sel : $nilai_sel;
                    $sel['hasil'] = $m2_penyakit;
                }
                // selain itu jika m2 penyakit sama dengan 0 maka
                elseif ($m2_penyakit == 'θ') {
                    $irisan = $m1_penyakit;
                    $m_baru[$irisan] = isset($m_baru[$irisan]) ? $m_baru[$irisan] + $nilai_sel : $nilai_sel;
                    $sel['hasil'] = $m1_penyakit;
                }
                // selain itu maka
                else {

                    // jika m1 penyakit sama atau memiliki irisan
                    if ($m1_penyakit == $m2_penyakit) {
                        $irisan = $m1_penyakit;
                        $m_baru[$irisan] = isset($m_baru[$irisan]) ? $m_baru[$irisan] + $nilai_sel : $nilai_sel;
                        $sel['hasil'] = $m1_penyakit;
                    }
                    // selain itu karena tidak memiliki irisan maka dihitung konflik
                    else {
                        $konflik += $nilai_sel;
                        $sel['hasil'] = 'konflik';
                    }
                }

                $sel['nilai'] = $nilai_sel;
                $sel_list[] = $sel;
            } //penutup foreach ketiga

            $baris['sel_list'] = $sel_list;
            $tabel_kombinasi['rows'][] = $baris;
        } // penutup foreach kedua

        // echo "<pre>";
        // print_r ($tabel_kombinasi);
        // echo "</pre>";
        // exit();
        // simpan tabel kombinasi untuk ditampilkan
        $m_kombinasi_list[$langkah] = $tabel_kombinasi;

        // normaliasi masa jika ada konflik
        if ($konflik < 1) {
            foreach ($m_baru as $id_penyakit => $massa) {
                $m_baru[$id_penyakit] = $massa / (1 - $konflik);
            } // penutup foreach keempat
        }



        // simpan perhitungan normalisasi
        $perhitungan_normalisasi = [];
        foreach ($m_baru as $id_penyakit => $massa) {
            $kalkulasi = [
                'penyakit' => $id_penyakit,
                'rumus' => '(' . number_format($massa, 3) . ') / (1 -' . number_format($konflik, 3) . ')',
                'langkah1' => number_format($massa, 3) .  ' / ' . number_format(1 - $konflik, 3),
                'hasil' => number_format($massa / (1 - $konflik), 3)
            ];

            $perhitungan_normalisasi[] = $kalkulasi;
        } //penutup foreach ke enam

        $densitas_list[$langkah] = [
            'kombinasi' => $m_baru,
            'konflik' => $konflik,
            'perhitungan' => $perhitungan_normalisasi
        ];

        $m[$langkah] = $m_baru;
        $langkah++;

    } // penutup foreach pertama

    // simpan hasil akhir
    $hasil_akhir = [];
    

    if (isset($m[$langkah - 1] )) {
        $m_terakhir = $m[$langkah - 1];

        foreach ($m_terakhir as $id_penyakit => $kepercayaan) {
            if ($id_penyakit != 'θ') {
                $hasil_akhir[$id_penyakit] = [
                    'nama_penyakit' => isset($nama_penyakit_list[$id_penyakit]) ? $nama_penyakit_list[$id_penyakit] : "penyakit $id_penyakit",
                    'kepercayaan' => $kepercayaan,
                    'persentase' => $kepercayaan * 100,
                    'id_penyakit' => $id_penyakit
                ];
            }
        }
    }

    // echo "<pre>";
    // print_r($hasil_akhir);
    // echo "<pre>";

    // mengurutkan dari yang terkecil > terbesar
    usort($hasil_akhir, function ($a, $b) {
        return $b['kepercayaan'] <=> $a['kepercayaan'];
    });




    return $result =  ['densitas_list' => $densitas_list, 'kombinasi_list' => $m_kombinasi_list, 'hasil_akhir' => $hasil_akhir];

}

// Fungsi untuk mendapatkan semua nama penyakit sekaligus (untuk efisiensi)
function getAllNamaPenyakit($koneksi) {
    $nama_penyakit = [];
    $perintah = "SELECT id_penyakit, nama_penyakit FROM penyakit";
    $hasil = $koneksi->query($perintah);
    
    if ($hasil) {
        while ($data = $hasil->fetch_assoc()) {
            $nama_penyakit[$data['id_penyakit']] = $data['nama_penyakit'];
        }
    }
    
    $nama_penyakit['θ'] = 'θ (Tidak Diketahui)';
    return $nama_penyakit;
}
