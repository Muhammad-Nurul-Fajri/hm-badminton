<?php
/**
 * FILE: pelanggan/riwayat.php
 * FUNGSI: Menampilkan riwayat reservasi pelanggan
 */

require_once __DIR__ . '/../config/middleware.php';
requireCustomer();

/*
 * Ambil semua reservasi milik pelanggan yang sedang login
 */
$reservasi = getReservasiByUser($_SESSION['user_id']);

/*
 * Karena data reservasi ditampilkan dari yang TERBARU ke TERLAMA,
 * maka nomor reservasi dibuat dari belakang.
 *
 * Contoh:
 * Data tampil:
 * Reservasi terbaru   -> nomor 3
 * Reservasi kedua     -> nomor 2
 * Reservasi pertama   -> nomor 1
 */
$totalReservasi = count($reservasi);

$title = 'Riwayat Reservasi - HM Badminton Court';
$active_page = 'riwayat';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">

    <?php require_once __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">

        <header class="page-header">
            <h1>Riwayat Reservasi</h1>
        </header>

        <div class="content-section">

            <?php if (empty($reservasi)): ?>

                <!-- Jika belum ada reservasi -->
                <div class="empty-state">
                    <span>📋</span>

                    <p>Belum ada reservasi</p>

                    <a href="<?php echo BASE_URL; ?>pelanggan/reservasi.php"
                       class="btn btn-primary">
                        Buat Reservasi
                    </a>
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cabang</th>
                                <th>Lapangan</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Durasi</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Bayar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($reservasi as $index => $r): ?>

                                <?php
                                /*
                                 * Membuat nomor urut reservasi.
                                 *
                                 * $index = 0 adalah data paling baru.
                                 * Karena data ditampilkan terbaru -> terlama,
                                 * nomor terbesar diberikan pada data terbaru.
                                 *
                                 * Contoh jika ada 3 reservasi:
                                 *
                                 * index 0 -> 3
                                 * index 1 -> 2
                                 * index 2 -> 1
                                 */
                                $nomorReservasi = $totalReservasi - $index;
                                ?>

                                <tr>

                                    <!-- NOMOR RESERVASI -->
                                    <td>
                                        <strong>
                                            #<?php echo $nomorReservasi; ?>
                                        </strong>
                                    </td>

                                    <!-- CABANG -->
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $r['cabang_nama'] ?? '-'
                                        );
                                        ?>
                                    </td>

                                    <!-- LAPANGAN -->
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $r['lapangan_nama'] ?? '-'
                                        );
                                        ?>
                                    </td>

                                    <!-- TANGGAL -->
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            getHariIndo($r['tanggal']) .
                                            ', ' .
                                            formatTanggal($r['tanggal'])
                                        );
                                        ?>
                                    </td>

                                    <!-- WAKTU -->
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            substr($r['waktu_mulai'], 0, 5)
                                        );
                                        ?>

                                        -

                                        <?php
                                        echo htmlspecialchars(
                                            substr($r['waktu_selesai'], 0, 5)
                                        );
                                        ?>
                                    </td>

                                    <!-- DURASI -->
                                    <td>
                                        <?php
                                        echo (int)$r['durasi_jam'];
                                        ?>
                                        jam
                                    </td>

                                    <!-- TOTAL -->
                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            formatRupiah($r['total_harga'])
                                        );
                                        ?>
                                    </td>

                                    <!-- STATUS RESERVASI -->
                                    <td>
                                        <span class="badge
                                            <?php
                                            echo htmlspecialchars(
                                                getStatusBadgeClass(
                                                    $r['status']
                                                )
                                            );
                                            ?>">

                                            <?php
                                            echo htmlspecialchars(
                                                getStatusLabel(
                                                    $r['status']
                                                )
                                            );
                                            ?>

                                        </span>
                                    </td>

                                    <!-- STATUS PEMBAYARAN -->
                                    <td>
                                        <span class="badge
                                            <?php
                                            echo htmlspecialchars(
                                                getPaymentStatusBadgeClass(
                                                    $r['payment_status']
                                                )
                                            );
                                            ?>">

                                            <?php
                                            echo htmlspecialchars(
                                                getPaymentStatusLabel(
                                                    $r['payment_status']
                                                )
                                            );
                                            ?>

                                        </span>
                                    </td>

                                    <!-- AKSI -->
                                    <td class="action-cell">

                                        <?php
                                        /*
                                         * Jika reservasi masih pending
                                         * dan belum dibayar
                                         */
                                        ?>

                                        <?php if (
                                            $r['status'] === 'pending'
                                            &&
                                            $r['payment_status'] === 'unpaid'
                                        ): ?>

                                            <!-- Tombol Bayar -->
                                            <a
                                                href="<?php echo BASE_URL; ?>pelanggan/pembayaran.php?reservasi_id=<?php echo (int)$r['id']; ?>"
                                                class="btn btn-sm btn-primary"
                                            >
                                                💳 Bayar
                                            </a>

                                            <!-- Tombol Batal -->
                                            <a
                                                href="<?php echo BASE_URL; ?>pelanggan/batal.php?id=<?php echo (int)$r['id']; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Yakin batalkan reservasi ini?')"
                                            >
                                                ✕ Batal
                                            </a>


                                        <?php
                                        /*
                                         * Jika sudah confirmed
                                         * atau sudah dibayar
                                         */
                                        ?>

                                        <?php elseif (
                                            $r['status'] === 'confirmed'
                                            ||
                                            $r['payment_status'] === 'paid'
                                        ): ?>

                                            <!-- Tombol Tiket -->
                                            <a
                                                href="<?php echo BASE_URL; ?>pelanggan/tiket.php?id=<?php echo (int)$r['id']; ?>"
                                                class="btn btn-sm btn-success"
                                            >
                                                🎫 Tiket
                                            </a>


                                        <?php
                                        /*
                                         * Jika sudah membayar DP
                                         * tetapi belum lunas
                                         */
                                        ?>

                                        <?php elseif (
                                            $r['payment_status'] === 'dp_paid'
                                        ): ?>

                                            <!-- Tombol Lunasi -->
                                            <a
                                                href="<?php echo BASE_URL; ?>pelanggan/pembayaran.php?reservasi_id=<?php echo (int)$r['id']; ?>"
                                                class="btn btn-sm btn-primary"
                                            >
                                                💳 Lunasi
                                            </a>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </main>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>