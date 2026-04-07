<?php

class DashboardController
{
  private function base(): string
  {
    $b = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $b === '/' ? '' : $b;
  }

  private function today(): string
  {
    return date('Y-m-d');
  }

  private function monthStart(): string
  {
    return date('Y-m-01');
  }

  private function monthEnd(): string
  {
    return date('Y-m-t');
  }

  public function index($req, $res)
  {
    $pdo = Db::pdo();
    $pageTitle = 'Dashboard';
    $base = $this->base();

    $today  = $this->today();
    $mStart = $this->monthStart();
    $mEnd   = $this->monthEnd();

    $totalGuru = (int)$pdo->query("SELECT COUNT(*) FROM guru")->fetchColumn();

    // Hadir hari ini = hanya yang hadir dan tidak terlambat
    $stmt = $pdo->prepare("
      SELECT COUNT(DISTINCT id_guru)
      FROM presensi_detail
      WHERE DATE(COALESCE(jam_masuk, created_at)) = :t
        AND status_kehadiran = 'HADIR'
        AND COALESCE(is_terlambat,0) = 0
    ");
    $stmt->execute([':t' => $today]);
    $hadirHariIni = (int)$stmt->fetchColumn();

    // Terlambat hari ini = hadir tapi terlambat
    $stmt = $pdo->prepare("
      SELECT COUNT(DISTINCT id_guru)
      FROM presensi_detail
      WHERE DATE(COALESCE(jam_masuk, created_at)) = :t
        AND status_kehadiran = 'HADIR'
        AND COALESCE(is_terlambat,0) = 1
    ");
    $stmt->execute([':t' => $today]);
    $terlambatHariIni = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
      SELECT COUNT(DISTINCT id_guru)
      FROM presensi_detail
      WHERE DATE(COALESCE(jam_masuk, created_at)) = :t
        AND status_kehadiran = 'IZIN'
    ");
    $stmt->execute([':t' => $today]);
    $izinHariIni = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
      SELECT COUNT(DISTINCT id_guru)
      FROM presensi_detail
      WHERE DATE(COALESCE(jam_masuk, created_at)) = :t
        AND status_kehadiran = 'SAKIT'
    ");
    $stmt->execute([':t' => $today]);
    $sakitHariIni = (int)$stmt->fetchColumn();

    $izinSakitHariIni = $izinHariIni + $sakitHariIni;

    // Tidak hadir = total guru - (hadir + terlambat + izin + sakit)
    $tidakHadirHariIni = max(0, $totalGuru - ($hadirHariIni + $terlambatHariIni + $izinHariIni + $sakitHariIni));
    $belumAbsen = $tidakHadirHariIni;

    $stmt = $pdo->prepare("
      SELECT COUNT(*)
      FROM pengajuan_presensi
      WHERE status_verifikasi = 'MENUNGGU'
    ");
    $stmt->execute();
    $pengajuanMenunggu = (int)$stmt->fetchColumn();

    // Trend 7 hari: hanya hadir yang tidak terlambat
    $trend = [];
    for ($i = 6; $i >= 0; $i--) {
      $d = date('Y-m-d', strtotime("-{$i} day"));

      $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT id_guru)
        FROM presensi_detail
        WHERE DATE(COALESCE(jam_masuk, created_at)) = :d
          AND status_kehadiran = 'HADIR'
          AND COALESCE(is_terlambat,0) = 0
      ");
      $stmt->execute([':d' => $d]);

      $trend[] = [
        'date'  => $d,
        'label' => date('D', strtotime($d)),
        'value' => (int)$stmt->fetchColumn(),
      ];
    }

    $maxTrend = 0;
    foreach ($trend as $t) {
      $maxTrend = max($maxTrend, (int)$t['value']);
    }
    if ($maxTrend < 1) $maxTrend = 1;

    $stmt = $pdo->prepare("
      SELECT
        g.nama_guru,
        g.nip,
        pd.status_kehadiran,
        pd.is_terlambat,
        pd.created_at
      FROM presensi_detail pd
      JOIN guru g ON g.id_guru = pd.id_guru
      ORDER BY pd.created_at DESC
      LIMIT 5
    ");
    $stmt->execute();
    $aktivitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top disiplin: hadir = hanya yang tidak terlambat
    $stmt = $pdo->prepare("
      SELECT
        g.id_guru,
        g.nama_guru,
        g.nip,

        SUM(
          CASE
            WHEN pd.status_kehadiran='HADIR'
             AND COALESCE(pd.is_terlambat,0)=0
            THEN 1 ELSE 0
          END
        ) AS hadir,

        SUM(
          CASE
            WHEN pd.status_kehadiran='HADIR'
             AND COALESCE(pd.is_terlambat,0)=1
            THEN 1 ELSE 0
          END
        ) AS terlambat,

        SUM(CASE WHEN pd.status_kehadiran IN ('IZIN','SAKIT') THEN 1 ELSE 0 END) AS izin,

        (
          2 * SUM(
                CASE
                  WHEN pd.status_kehadiran='HADIR'
                   AND COALESCE(pd.is_terlambat,0)=0
                  THEN 1 ELSE 0
                END
              )
          - 1 * SUM(
                CASE
                  WHEN pd.status_kehadiran='HADIR'
                   AND COALESCE(pd.is_terlambat,0)=1
                  THEN 1 ELSE 0
                END
              )
          - 1 * SUM(CASE WHEN pd.status_kehadiran IN ('IZIN','SAKIT') THEN 1 ELSE 0 END)
        ) AS skor

      FROM guru g
      LEFT JOIN presensi_detail pd
        ON pd.id_guru = g.id_guru
       AND DATE(COALESCE(pd.jam_masuk, pd.created_at)) BETWEEN :m1 AND :m2
      GROUP BY g.id_guru, g.nama_guru, g.nip
      ORDER BY skor DESC, terlambat ASC, hadir DESC
      LIMIT 5
    ");
    $stmt->execute([':m1' => $mStart, ':m2' => $mEnd]);
    $topDisiplin = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top terlambat: hadir = hanya yang tidak terlambat
    $stmt = $pdo->prepare("
      SELECT
        g.id_guru,
        g.nama_guru,
        g.nip,

        SUM(
          CASE
            WHEN pd.status_kehadiran='HADIR'
             AND COALESCE(pd.is_terlambat,0)=1
            THEN 1 ELSE 0
          END
        ) AS terlambat,

        SUM(
          CASE
            WHEN pd.status_kehadiran='HADIR'
             AND COALESCE(pd.is_terlambat,0)=0
            THEN 1 ELSE 0
          END
        ) AS hadir

      FROM guru g
      LEFT JOIN presensi_detail pd
        ON pd.id_guru = g.id_guru
       AND DATE(COALESCE(pd.jam_masuk, pd.created_at)) BETWEEN :m1 AND :m2
      GROUP BY g.id_guru, g.nama_guru, g.nip
      HAVING terlambat > 0
      ORDER BY terlambat DESC, hadir ASC
      LIMIT 5
    ");
    $stmt->execute([':m1' => $mStart, ':m2' => $mEnd]);
    $topTerlambat = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $contentFile = __DIR__ . '/../views/dashboard/index.php';
    $layoutFile  = __DIR__ . '/../views/layouts/admin.php';
    require $layoutFile;
  }
}