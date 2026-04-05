<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\User; 
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
{
    $user = Auth::user();
    $userData = session('user_data');

    // DATA UMUM
    $bukus = Buku::latest()->take(12)->get();
    
    // Sesuaikan: Ambil semua pinjaman user tanpa filter null agar muncul di list user
    $pinjaman_saya = Peminjaman::where('user_id', $user->id)
                                ->with('buku')
                                ->latest()
                                ->get();

    // LOGIKA KHUSUS ADMIN (Role 1) ATAU STAFF (Role 3)
    if (in_array($user->role_id, [1, 3])) {
        $stats = [
            'total_buku'   => Buku::count(),
            'total_user'   => User::where('role_id', 2)->count(),
            // Box Biru: Hitung yang statusnya 'PINJAM'
            'pinjam_aktif' => Peminjaman::where('status', 'PINJAM')->count(), 
            // Box Orange: Hitung yang statusnya 'PENDING'
            'antrean'      => Peminjaman::where('status', 'PENDING')->count(),
        ];
        
        // --- LOGIKA DATA GRAFIK (7 Hari Terakhir) ---
        $grafik = ['labels' => [], 'data' => []];
        for ($i = 6; $i >= 0; $i--) {
            $tgl = now()->subDays($i)->format('Y-m-d');
            $grafik['labels'][] = now()->subDays($i)->format('D'); 
            // Hitung semua aktifitas (Pending & Pinjam) per hari untuk grafik
            $grafik['data'][] = Peminjaman::whereDate('created_at', $tgl)->count();
        }

        // Tabel Antrean: Ambil yang statusnya 'PENDING'
        $antrean_terbaru = Peminjaman::with(['user', 'buku'])
                                    ->where('status', 'PENDING')
                                    ->latest()
                                    ->take(5)
                                    ->get();

        return view('dashboard.index', compact('userData', 'stats', 'bukus', 'pinjaman_saya', 'antrean_terbaru', 'grafik'));
    }

    return view('dashboard.index', compact('userData', 'bukus', 'pinjaman_saya'));
}
}