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

];
