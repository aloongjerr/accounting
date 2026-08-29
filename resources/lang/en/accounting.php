<?php

return [

    // ── Account Types ──
    'account_type' => [
        'group' => 'Group',
        'category' => 'Category',
        'account' => 'Account',
    ],

    // ── Account System Keys ──
    'account_system_key' => [
        // Groups
        'assets' => 'Assets',
        'liabilities' => 'Liabilities',
        'equity' => 'Equity',
        'revenue' => 'Revenue',
        'expenses' => 'Expenses',

        // Categories
        'current_assets' => 'Current Assets',
        'fixed_assets' => 'Fixed Assets',
        'current_liabilities' => 'Current Liabilities',
        'long_term_liabilities' => 'Long-term Liabilities',
        'owner_equity' => 'Owner Equity',
        'operating_revenue' => 'Operating Revenue',
        'non_operating_revenue' => 'Non-Operating Revenue',
        'contra_revenue' => 'Contra Revenue',
        'cost_of_goods_sold' => 'Cost of Goods Sold',
        'operating_expenses' => 'Operating Expenses',
        'non_operating_expenses' => 'Non-Operating Expenses',

        // Current Asset Accounts
        'cash_on_hand' => 'Cash on Hand',
        'cash_in_bank' => 'Cash in Bank',
        'accounts_receivable' => 'Accounts Receivable',
        'inventory' => 'Inventory',
        'prepaid_expenses' => 'Prepaid Expenses',
        'tax_receivable' => 'Tax Receivable',

        // Fixed Asset Accounts
        'land' => 'Land',
        'building' => 'Building',
        'equipment' => 'Equipment',
        'vehicle' => 'Vehicle',
        'furniture_and_fixtures' => 'Furniture & Fixtures',
        'accumulated_depreciation' => 'Accumulated Depreciation',

        // Current Liability Accounts
        'accounts_payable' => 'Accounts Payable',
        'accrued_expenses' => 'Accrued Expenses',
        'short_term_loans' => 'Short-term Loans',
        'tax_payable' => 'Tax Payable',
        'wages_payable' => 'Wages Payable',

        // Long-term Liability Accounts
        'long_term_loans' => 'Long-term Loans',
        'mortgage_payable' => 'Mortgage Payable',

        // Equity Accounts
        'owner_capital' => "Owner's Capital",
        'owner_drawings' => "Owner's Drawings",
        'retained_earnings' => 'Retained Earnings',
        'share_capital' => 'Share Capital',

        // Revenue Accounts
        'sales_revenue' => 'Sales Revenue',
        'service_revenue' => 'Service Revenue',
        'interest_income' => 'Interest Income',
        'other_income' => 'Other Income',
        'sales_returns_and_allowances' => 'Sales Returns & Allowances',

        // COGS
        'cost_of_revenue' => 'Cost of Revenue',

        // Operating Expense Accounts
        'salary_expense' => 'Salary Expense',
        'rent_expense' => 'Rent Expense',
        'utilities_expense' => 'Utilities Expense',
        'depreciation_expense' => 'Depreciation Expense',
        'bad_debt_expense' => 'Bad Debt Expense',
        'insurance_expense' => 'Insurance Expense',
        'office_supplies_expense' => 'Office Supplies Expense',

        // Non-Operating Expense Accounts
        'interest_expense' => 'Interest Expense',
        'tax_expense' => 'Tax Expense',
        'loss_on_disposal' => 'Loss on Disposal',
    ],

    // ── Journal Statuses ──
    'journal_status' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'void' => 'Void',
        'reversed' => 'Reversed',
    ],

    // ── Exception Messages ──
    'exceptions' => [
        'cannot_delete' => 'Cannot delete :model. Use void() or create an adjustment entry instead.',
        'cannot_update_posted' => 'Cannot update posted journals. Create an adjustment entry instead.',
    ],

    // ── Navigation ──
    'navigation' => [
        'finance' => 'Finance',
    ],

    // ── Transactions ──
    'transactions' => [
        'received' => 'Received',
        'paid' => 'Paid',
        'sold' => 'Sold',
        'purchased' => 'Purchased',
        'transfer' => 'Transfer',
        'adjustment' => 'Adjustment',
        'manual_journal' => 'Manual Journal',
    ],

    // ── Status Labels ──
    'status' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    // ── Filament Resources ──
    'resources' => [
        'account' => [
            'navigation_label' => 'Chart of Accounts',
            'model_label' => 'Account',
            'plural_model_label' => 'Chart of Accounts',
            'fields' => [
                'code' => 'Code',
                'name' => 'Name',
                'type' => 'Type',
                'system_key' => 'System Key',
                'parent' => 'Parent Account',
                'description' => 'Description',
                'is_active' => 'Active',
                'balance' => 'Balance',
                'running_balance' => 'Running Balance',
                'date' => 'Date',
                'created_at' => 'Created At',
            ],
            'sections' => [
                'details' => 'Account Details',
            ],
            'pages' => [
                'list' => ['title' => 'Chart of Accounts'],
                'create' => ['title' => 'Create Account'],
                'edit' => ['title' => 'Edit Account'],
            ],
        ],
        'journal' => [
            'navigation_label' => 'Journal Entries',
            'model_label' => 'Journal Entry',
            'plural_model_label' => 'Journal Entries',
            'fields' => [
                'date' => 'Date',
                'date_from' => 'Date From',
                'date_to' => 'Date To',
                'description' => 'Description',
                'status' => 'Status',
                'comments' => 'Comments',
                'void_remarks' => 'Void Remarks',
                'reference' => 'Reference',
                'reference_type' => 'Reference Type',
                'account' => 'Account',
                'debit' => 'Debit',
                'credit' => 'Credit',
                'entry_description' => 'Description',
                'total' => 'Total',
                'total_debit' => 'Total Debit',
                'total_credit' => 'Total Credit',
                'balanced' => 'Balanced',
                'amount' => 'Amount',
                'transaction_type' => 'Transaction Type',
                'created_at' => 'Created At',
            ],
            'sections' => [
                'details' => 'Journal Details',
                'entries' => 'Journal Entries',
                'summary' => 'Summary',
            ],
            'placeholders' => [
                'void_remarks' => 'Please provide a reason for voiding this journal entry...',
            ],
            'actions' => [
                'post' => 'Post',
                'void' => 'Void',
                'adjust' => 'Adjust',
                'add_entry' => 'Add Entry',
                'create_transaction' => 'Create Transaction',
            ],
            'pages' => [
                'list' => ['title' => 'Journal Entries'],
                'create' => ['title' => 'Create Manual Journal'],
                'edit' => ['title' => 'Edit Journal Entry'],
            ],
        ],
    ],

    // ── Reports ──
    'reports' => [
        'fields' => [
            'as_of_date' => 'As of Date',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
        ],
        'trial_balance' => [
            'title' => 'Trial Balance',
            'navigation_label' => 'Trial Balance',
        ],
        'income_statement' => [
            'title' => 'Income Statement',
            'navigation_label' => 'Income Statement',
        ],
        'balance_sheet' => [
            'title' => 'Balance Sheet',
            'navigation_label' => 'Balance Sheet',
        ],
    ],

    // ── Widgets ──
    'widgets' => [
        'account_balance' => [
            'cash' => 'Cash in Bank',
            'cash_description' => 'Current bank balance',
            'receivables' => 'Receivables',
            'receivables_description' => 'Outstanding customer invoices',
            'payables' => 'Payables',
            'payables_description' => 'Outstanding supplier bills',
        ],
        'recent_journals' => [
            'heading' => 'Recent Journal Entries',
            'date' => 'Date',
            'description' => 'Description',
            'status' => 'Status',
            'total' => 'Total',
        ],
        'financial_summary' => [
            'income' => 'Total Income',
            'income_description' => 'Year-to-date revenue (:year)',
            'expenses' => 'Total Expenses',
            'expenses_description' => 'Year-to-date expenses (:year)',
            'net_profit' => 'Net Profit',
            'net_profit_description' => 'Income minus expenses',
        ],
    ],

];
