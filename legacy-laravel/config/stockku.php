<?php

return [
    // Kode QRIS statis milik toko (dari bank/penyedia QRIS), diisi di file .env.
    // Tidak menggunakan payment gateway — pelanggan membayar sendiri lewat
    // aplikasi m-banking masing-masing, lalu kasir mengonfirmasi pembayaran.
    'qris_code' => env('QRIS_STATIC_CODE', ''),

    'qris_holder' => env('QRIS_HOLDER', 'StockKu Toko Serba Ada'),
];
