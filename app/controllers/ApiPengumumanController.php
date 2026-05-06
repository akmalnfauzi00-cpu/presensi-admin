<?php

class ApiPengumumanController
{
    private function json($data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function index($req, $res): void {
        $data = Pengumuman::all();
        // Memformat tanggal agar lebih cantik untuk UI Mobile
        foreach ($data as &$item) {
            $item['date'] = date('d M Y', strtotime($item['created_at']));
            // Memberikan icon/color default agar selaras dengan UI mobile Anda
            $item['icon'] = 'megaphone-outline';
            $item['color'] = '#2563EB';
            $item['bg'] = '#EFF6FF';
        }
        $this->json(['status' => 'success', 'data' => $data]);
    }
}