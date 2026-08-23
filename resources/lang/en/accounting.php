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

];
