<?php

return [

    // ── Jenis Akaun ──
    'account_type' => [
        'group' => 'Kumpulan',
        'category' => 'Kategori',
        'account' => 'Akaun',
    ],

    // ── Kunci Sistem Akaun ──
    'account_system_key' => [
        // Kumpulan
        'assets' => 'Aset',
        'liabilities' => 'Liabiliti',
        'equity' => 'Ekuiti',
        'revenue' => 'Hasil',
        'expenses' => 'Perbelanjaan',

        // Kategori
        'current_assets' => 'Aset Semasa',
        'fixed_assets' => 'Aset Tetap',
        'current_liabilities' => 'Liabiliti Semasa',
        'long_term_liabilities' => 'Liabiliti Jangka Panjang',
        'owner_equity' => 'Ekuiti Pemilik',
        'operating_revenue' => 'Hasil Operasi',
        'non_operating_revenue' => 'Hasil Bukan Operasi',
        'contra_revenue' => 'Hasil Kontra',
        'cost_of_goods_sold' => 'Kos Barangan Dijual',
        'operating_expenses' => 'Perbelanjaan Operasi',
        'non_operating_expenses' => 'Perbelanjaan Bukan Operasi',

        // Akaun Aset Semasa
        'cash_on_hand' => 'Tunai di Tangan',
        'cash_in_bank' => 'Tunai di Bank',
        'accounts_receivable' => 'Akaun Belum Terima',
        'inventory' => 'Inventori',
        'prepaid_expenses' => 'Perbelanjaan Pra Bayar',
        'tax_receivable' => 'Cukai Belum Terima',

        // Akaun Aset Tetap
        'land' => 'Tanah',
        'building' => 'Bangunan',
        'equipment' => 'Peralatan',
        'vehicle' => 'Kenderaan',
        'furniture_and_fixtures' => 'Perabot & Peralatan',
        'accumulated_depreciation' => 'Susut Nilai Terkumpul',

        // Akaun Liabiliti Semasa
        'accounts_payable' => 'Akaun Belum Bayar',
        'accrued_expenses' => 'Perbelanjaan Terhutang',
        'short_term_loans' => 'Pinjaman Jangka Pendek',
        'tax_payable' => 'Cukai Belum Bayar',
        'wages_payable' => 'Gaji Belum Bayar',

        // Akaun Liabiliti Jangka Panjang
        'long_term_loans' => 'Pinjaman Jangka Panjang',
        'mortgage_payable' => 'Gadai Janji Belum Bayar',

        // Akaun Ekuiti
        'owner_capital' => 'Modal Pemilik',
        'owner_drawings' => 'Lukusan Pemilik',
        'retained_earnings' => 'Pendapatan Ditahan',
        'share_capital' => 'Modal Syer',

        // Akaun Hasil
        'sales_revenue' => 'Hasil Jualan',
        'service_revenue' => 'Hasil Perkhidmatan',
        'interest_income' => 'Pendapatan Faedah',
        'other_income' => 'Pendapatan Lain-lain',
        'sales_returns_and_allowances' => 'Retur & Elaun Jualan',

        // Kos Barangan Dijual
        'cost_of_revenue' => 'Kos Hasil',

        // Akaun Perbelanjaan Operasi
        'salary_expense' => 'Perbelanjaan Gaji',
        'rent_expense' => 'Perbelanjaan Sewa',
        'utilities_expense' => 'Perbelanjaan Utiliti',
        'depreciation_expense' => 'Perbelanjaan Susut Nilai',
        'bad_debt_expense' => 'Perbelanjaan Hutang Lapuk',
        'insurance_expense' => 'Perbelanjaan Insurans',
        'office_supplies_expense' => 'Perbelanjaan Bekalan Pejabat',

        // Akaun Perbelanjaan Bukan Operasi
        'interest_expense' => 'Perbelanjaan Faedah',
        'tax_expense' => 'Perbelanjaan Cukai',
        'loss_on_disposal' => 'Kerugian Pelupusan',
    ],

    // ── Status Jurnal ──
    'journal_status' => [
        'draft' => 'Draf',
        'posted' => 'Dipost',
        'void' => 'Batal',
        'reversed' => 'Diterbalikkan',
    ],

    // ── Mesej Pengecualian ──
    'exceptions' => [
        'cannot_delete' => 'Tidak dibenarkan memadam :model. Sila gunakan void() atau buat entri pelarasan.',
        'cannot_update_posted' => 'Tidak dibenarkan mengemaskini jurnal yang telah dipost. sila buat entri pelarasan.',
    ],

    // ── Navigasi ──
    'navigation' => [
        'finance' => 'Kewangan',
    ],

    // ── Transaksi ──
    'transactions' => [
        'received' => 'Diterima',
        'paid' => 'Dibayar',
        'sold' => 'Dijual',
        'purchased' => 'Dibeli',
        'transfer' => 'Pindahan',
        'adjustment' => 'Pelarasan',
        'manual_journal' => 'Jurnal Manual',
    ],

    // ── Label Status ──
    'status' => [
        'yes' => 'Ya',
        'no' => 'Tidak',
    ],

    // ── Sumber Filament ──
    'resources' => [
        'account' => [
            'navigation_label' => 'Carta Akaun',
            'model_label' => 'Akaun',
            'plural_model_label' => 'Carta Akaun',
            'fields' => [
                'code' => 'Kod',
                'name' => 'Nama',
                'type' => 'Jenis',
                'system_key' => 'Kunci Sistem',
                'parent' => 'Akaun Induk',
                'description' => 'Keterangan',
                'is_active' => 'Aktif',
                'balance' => 'Baki',
                'running_balance' => 'Baki Semasa',
                'date' => 'Tarikh',
                'created_at' => 'Dicipta Pada',
            ],
            'sections' => [
                'details' => 'Butiran Akaun',
            ],
            'pages' => [
                'list' => ['title' => 'Carta Akaun'],
                'create' => ['title' => 'Cipta Akaun'],
                'edit' => ['title' => 'Kemaskini Akaun'],
            ],
        ],
        'journal' => [
            'navigation_label' => 'Entri Jurnal',
            'model_label' => 'Entri Jurnal',
            'plural_model_label' => 'Entri Jurnal',
            'fields' => [
                'date' => 'Tarikh',
                'date_from' => 'Tarikh Dari',
                'date_to' => 'Tarikh Hingga',
                'description' => 'Keterangan',
                'status' => 'Status',
                'comments' => 'Ulasan',
                'void_remarks' => 'Sebab Pembatalan',
                'reference' => 'Rujukan',
                'reference_type' => 'Jenis Rujukan',
                'account' => 'Akaun',
                'debit' => 'Debit',
                'credit' => 'Kredit',
                'entry_description' => 'Keterangan',
                'total' => 'Jumlah',
                'total_debit' => 'Jumlah Debit',
                'total_credit' => 'Jumlah Kredit',
                'balanced' => 'Seimbang',
                'amount' => 'Jumlah',
                'transaction_type' => 'Jenis Transaksi',
                'created_at' => 'Dicipta Pada',
            ],
            'sections' => [
                'details' => 'Butiran Jurnal',
                'entries' => 'Entri Jurnal',
                'summary' => 'Ringkasan',
            ],
            'placeholders' => [
                'void_remarks' => 'Sila nyatakan sebab untuk membatalkan entri jurnal ini...',
            ],
            'actions' => [
                'post' => 'Post',
                'void' => 'Batal',
                'adjust' => 'Laraskan',
                'add_entry' => 'Tambah Entri',
                'create_transaction' => 'Cipta Transaksi',
            ],
            'pages' => [
                'list' => ['title' => 'Entri Jurnal'],
                'create' => ['title' => 'Cipta Jurnal Manual'],
                'edit' => ['title' => 'Kemaskini Entri Jurnal'],
            ],
        ],
    ],

    // ── Laporan ──
    'reports' => [
        'fields' => [
            'as_of_date' => 'Setakat Tarikh',
            'start_date' => 'Tarikh Mula',
            'end_date' => 'Tarikh Akhir',
        ],
        'trial_balance' => [
            'title' => 'Imbangan Duga',
            'navigation_label' => 'Imbangan Duga',
            'generate' => 'Jana Laporan',
            'balanced' => 'Imbangan duga adalah seimbang.',
            'unbalanced' => 'Imbangan duga TIDAK seimbang. Sila semak entri anda.',
        ],
        'income_statement' => [
            'title' => 'Penyata Pendapatan',
            'navigation_label' => 'Penyata Pendapatan',
            'generate' => 'Jana Laporan',
            'total_income' => 'Jumlah Pendapatan',
            'total_expenses' => 'Jumlah Perbelanjaan',
            'net_profit' => 'Keuntungan Bersih',
        ],
        'balance_sheet' => [
            'title' => 'Kunci Tara',
            'navigation_label' => 'Kunci Tara',
            'generate' => 'Jana Laporan',
            'total_assets' => 'Jumlah Aset',
            'total_liabilities' => 'Jumlah Liabiliti',
            'total_equity' => 'Jumlah Ekuiti',
        ],
        'aging' => [
            'title' => 'Laporan Hutang Lama',
            'navigation_label' => 'Hutang Lama',
            'buckets' => [
                'current' => 'Semasa (0-30 hari)',
                '31-60' => '31-60 hari',
                '61-90' => '61-90 hari',
                'over_90' => 'Lebih 90 hari',
            ],
            'type' => [
                'receivable' => 'Akaun Belum Terima',
                'payable' => 'Akaun Belum Bayar',
            ],
            'fields' => [
                'account' => 'Akaun',
                'journal' => 'Jurnal',
                'date' => 'Tarikh',
                'days_old' => 'Hari Tertunggak',
                'amount' => 'Jumlah',
                'bucket' => 'Kategori Umur',
            ],
            'summary' => [
                'total' => 'Jumlah Tertunggak',
            ],
        ],
        'budget' => [
            'title' => 'Bajet lwn Sebenar',
            'navigation_label' => 'Bajet lwn Sebenar',
            'fields' => [
                'account' => 'Akaun',
                'budgeted' => 'Dibajetkan',
                'actual' => 'Sebenar',
                'variance' => 'Varians',
                'variance_percentage' => 'Varians %',
                'start_date' => 'Tarikh Mula',
                'end_date' => 'Tarikh Akhir',
                'amount' => 'Jumlah Bajet',
                'description' => 'Keterangan',
            ],
            'summary' => [
                'total_budgeted' => 'Jumlah Dibajetkan',
                'total_actual' => 'Jumlah Sebenar',
                'total_variance' => 'Jumlah Varians',
            ],
        ],
        'reconciliation' => [
            'title' => 'Penyelarasan Bank',
            'navigation_label' => 'Penyelarasan Bank',
            'status' => [
                'draft' => 'Draf',
                'completed' => 'Selesai',
            ],
            'type' => [
                'matched' => 'Dipadankan',
                'unmatched_bank' => 'Bank Tidak Dipadankan',
                'unmatched_system' => 'Sistem Tidak Dipadankan',
            ],
            'fields' => [
                'account' => 'Akaun Bank',
                'start_date' => 'Tarikh Mula',
                'end_date' => 'Tarikh Akhir',
                'opening_balance' => 'Baki Pembukaan',
                'closing_balance' => 'Baki Penutupan',
                'status' => 'Status',
                'transaction_date' => 'Tarikh Transaksi',
                'description' => 'Keterangan',
                'amount' => 'Jumlah',
                'reference' => 'Rujukan',
                'bank_type' => 'Jenis',
                'is_matched' => 'Dipadankan',
            ],
            'summary' => [
                'bank_statement_balance' => 'Baki Penyata Bank',
                'system_balance' => 'Baki Sistem',
                'matched_total' => 'Jumlah Dipadankan',
                'unmatched_bank_total' => 'Item Bank Tidak Dipadankan',
                'unmatched_system_total' => 'Item Sistem Tidak Dipadankan',
                'difference' => 'Perbezaan',
            ],
        ],
    ],

    // ── Widget ──
    'widgets' => [
        'account_balance' => [
            'cash' => 'Tunai di Bank',
            'cash_description' => 'Baki bank semasa',
            'receivables' => 'Piutang',
            'receivables_description' => 'Invois pelanggan yang belum dijelaskan',
            'payables' => 'Hutang',
            'payables_description' => 'Bil pembekal yang belum dijelaskan',
        ],
        'recent_journals' => [
            'heading' => 'Catatan Jurnal Terkini',
            'date' => 'Tarikh',
            'description' => 'Keterangan',
            'status' => 'Status',
            'total' => 'Jumlah',
        ],
        'financial_summary' => [
            'income' => 'Jumlah Pendapatan',
            'income_description' => 'Pendapatan tahun semasa (:year)',
            'expenses' => 'Jumlah Perbelanjaan',
            'expenses_description' => 'Perbelanjaan tahun semasa (:year)',
            'net_profit' => 'Keuntungan Bersih',
            'net_profit_description' => 'Pendapatan tolak perbelanjaan',
        ],
    ],

];
