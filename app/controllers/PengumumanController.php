<?php

class PengumumanController {

  public function index(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    $data = Pengumuman::all();
    $pageTitle = "Kelola Pengumuman";
    
    // Variabel pendukung layout
    $base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($base === '/') $base = '';
    $path = $req->path();
    $authUser = Auth::user();

    $contentFile = __DIR__ . '/../views/pengumuman/index.php';
    include __DIR__ . '/../views/layouts/admin.php';
  }

  public function store(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    $title = trim((string)$req->input('title', ''));
    $content = trim((string)$req->input('content', ''));

    if ($title === '' || $content === '') {
      Session::flash('error', 'Judul dan isi wajib diisi.');
    } else {
      if (Pengumuman::create(['title' => $title, 'content' => $content])) {
        Session::flash('success', 'Pengumuman berhasil diterbitkan.');
      } else {
        Session::flash('error', 'Gagal membuat pengumuman.');
      }
    }
    $res->redirect('/pengumuman');
  }

  public function delete(Request $req, Response $res): void {
    if (!Auth::check()) $res->redirect('/login');

    $id = (int)$req->input('id', 0);
    if ($id > 0 && Pengumuman::delete($id)) {
      Session::flash('success', 'Pengumuman berhasil dihapus.');
    } else {
      Session::flash('error', 'Gagal menghapus pengumuman.');
    }
    $res->redirect('/pengumuman');
  }
}