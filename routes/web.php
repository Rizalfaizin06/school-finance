<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Pages\DetailPembayaranSiswa;

Route::get('/', function () {
    return view('welcome');
});

// Route for payment processing from Detail Pembayaran Siswa page
Route::post('/admin/detail-pembayaran-siswa/process-payment', [DetailPembayaranSiswa::class, 'processPayment'])
    ->middleware(['web', 'auth'])
    ->name('detail-pembayaran-siswa.process-payment');
