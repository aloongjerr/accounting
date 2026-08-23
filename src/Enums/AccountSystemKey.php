<?php

namespace AloongJerr\Accounting\Enums;

use AloongJerr\Accounting\Contracts\HasAccountIdentity;
use Filament\Support\Contracts\HasLabel;

enum AccountSystemKey: string implements HasLabel, HasAccountIdentity
{
    // ── Groups (Level 0) ──
    case Assets = 'assets';
    case Liabilities = 'liabilities';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expenses = 'expenses';

    // ── Asset Categories (Level 1) ──
    case CurrentAssets = 'current_assets';
    case FixedAssets = 'fixed_assets';

    // ── Liability Categories (Level 1) ──
    case CurrentLiabilities = 'current_liabilities';
    case LongTermLiabilities = 'long_term_liabilities';

    // ── Equity Categories (Level 1) ──
    case OwnerEquity = 'owner_equity';

    // ── Revenue Categories (Level 1) ──
    case OperatingRevenue = 'operating_revenue';
    case NonOperatingRevenue = 'non_operating_revenue';
    case ContraRevenue = 'contra_revenue';

    // ── Expense Categories (Level 1) ──
    case CostOfGoodsSold = 'cost_of_goods_sold';
    case OperatingExpenses = 'operating_expenses';
    case NonOperatingExpenses = 'non_operating_expenses';

    // ── Current Asset Accounts (Level 2) ──
    case CashOnHand = 'cash_on_hand';
    case CashInBank = 'cash_in_bank';
    case AccountsReceivable = 'accounts_receivable';
    case Inventory = 'inventory';
    case PrepaidExpenses = 'prepaid_expenses';
    case TaxReceivable = 'tax_receivable';

    // ── Fixed Asset Accounts (Level 2) ──
    case Land = 'land';
    case Building = 'building';
    case Equipment = 'equipment';
    case Vehicle = 'vehicle';
    case FurnitureAndFixtures = 'furniture_and_fixtures';
    case AccumulatedDepreciation = 'accumulated_depreciation';

    // ── Current Liability Accounts (Level 2) ──
    case AccountsPayable = 'accounts_payable';
    case AccruedExpenses = 'accrued_expenses';
    case ShortTermLoans = 'short_term_loans';
    case TaxPayable = 'tax_payable';
    case WagesPayable = 'wages_payable';

    // ── Long-term Liability Accounts (Level 2) ──
    case LongTermLoans = 'long_term_loans';
    case MortgagePayable = 'mortgage_payable';

    // ── Equity Accounts (Level 2) ──
    case OwnerCapital = 'owner_capital';
    case OwnerDrawings = 'owner_drawings';
    case RetainedEarnings = 'retained_earnings';
    case ShareCapital = 'share_capital';

    // ── Revenue Accounts (Level 2) ──
    case SalesRevenue = 'sales_revenue';
    case ServiceRevenue = 'service_revenue';
    case InterestIncome = 'interest_income';
    case OtherIncome = 'other_income';
    case SalesReturnsAndAllowances = 'sales_returns_and_allowances';

    // ── COGS Accounts (Level 2) ──
    case CostOfRevenue = 'cost_of_revenue';

    // ── Operating Expense Accounts (Level 2) ──
    case SalaryExpense = 'salary_expense';
    case RentExpense = 'rent_expense';
    case UtilitiesExpense = 'utilities_expense';
    case DepreciationExpense = 'depreciation_expense';
    case BadDebtExpense = 'bad_debt_expense';
    case InsuranceExpense = 'insurance_expense';
    case OfficeSuppliesExpense = 'office_supplies_expense';

    // ── Non-Operating Expense Accounts (Level 2) ──
    case InterestExpense = 'interest_expense';
    case TaxExpense = 'tax_expense';
    case LossOnDisposal = 'loss_on_disposal';

    public function parentKey(): ?self
    {
        return match ($this) {
            // Groups have no parent
            self::Assets,
            self::Liabilities,
            self::Equity,
            self::Revenue,
            self::Expenses => null,

            // Asset categories → Assets
            self::CurrentAssets,
            self::FixedAssets => self::Assets,

            // Liability categories → Liabilities
            self::CurrentLiabilities,
            self::LongTermLiabilities => self::Liabilities,

            // Equity categories → Equity
            self::OwnerEquity => self::Equity,

            // Revenue categories → Revenue
            self::OperatingRevenue,
            self::NonOperatingRevenue,
            self::ContraRevenue => self::Revenue,

            // Expense categories → Expenses
            self::CostOfGoodsSold,
            self::OperatingExpenses,
            self::NonOperatingExpenses => self::Expenses,

            // Current asset accounts → Current Assets
            self::CashOnHand,
            self::CashInBank,
            self::AccountsReceivable,
            self::Inventory,
            self::PrepaidExpenses,
            self::TaxReceivable => self::CurrentAssets,

            // Fixed asset accounts → Fixed Assets
            self::Land,
            self::Building,
            self::Equipment,
            self::Vehicle,
            self::FurnitureAndFixtures,
            self::AccumulatedDepreciation => self::FixedAssets,

            // Current liability accounts → Current Liabilities
            self::AccountsPayable,
            self::AccruedExpenses,
            self::ShortTermLoans,
            self::TaxPayable,
            self::WagesPayable => self::CurrentLiabilities,

            // Long-term liability accounts → Long-term Liabilities
            self::LongTermLoans,
            self::MortgagePayable => self::LongTermLiabilities,

            // Equity accounts → Owner Equity
            self::OwnerCapital,
            self::OwnerDrawings,
            self::RetainedEarnings,
            self::ShareCapital => self::OwnerEquity,

            // Revenue accounts → Operating Revenue (default)
            self::SalesRevenue,
            self::ServiceRevenue => self::OperatingRevenue,

            self::InterestIncome,
            self::OtherIncome => self::NonOperatingRevenue,

            self::SalesReturnsAndAllowances => self::ContraRevenue,

            // COGS → Cost of Goods Sold category
            self::CostOfRevenue => self::CostOfGoodsSold,

            // Operating expense accounts → Operating Expenses
            self::SalaryExpense,
            self::RentExpense,
            self::UtilitiesExpense,
            self::DepreciationExpense,
            self::BadDebtExpense,
            self::InsuranceExpense,
            self::OfficeSuppliesExpense => self::OperatingExpenses,

            // Non-operating expense accounts → Non-Operating Expenses
            self::InterestExpense,
            self::TaxExpense,
            self::LossOnDisposal => self::NonOperatingExpenses,
        };
    }

    public function getCode(): string
    {
        return match ($this) {
            // Groups (1000s)
            self::Assets => '1000',
            self::Liabilities => '2000',
            self::Equity => '3000',
            self::Revenue => '4000',
            self::Expenses => '5000',

            // Categories (X100s)
            self::CurrentAssets => '1100',
            self::FixedAssets => '1500',
            self::CurrentLiabilities => '2100',
            self::LongTermLiabilities => '2500',
            self::OwnerEquity => '3100',
            self::OperatingRevenue => '4100',
            self::NonOperatingRevenue => '4200',
            self::ContraRevenue => '4300',
            self::CostOfGoodsSold => '5100',
            self::OperatingExpenses => '5200',
            self::NonOperatingExpenses => '5300',

            // Current Asset Accounts
            self::CashOnHand => '1101',
            self::CashInBank => '1102',
            self::AccountsReceivable => '1103',
            self::Inventory => '1104',
            self::PrepaidExpenses => '1105',
            self::TaxReceivable => '1106',

            // Fixed Asset Accounts
            self::Land => '1501',
            self::Building => '1502',
            self::Equipment => '1503',
            self::Vehicle => '1504',
            self::FurnitureAndFixtures => '1505',
            self::AccumulatedDepreciation => '1506',

            // Current Liability Accounts
            self::AccountsPayable => '2101',
            self::AccruedExpenses => '2102',
            self::ShortTermLoans => '2103',
            self::TaxPayable => '2104',
            self::WagesPayable => '2105',

            // Long-term Liability Accounts
            self::LongTermLoans => '2501',
            self::MortgagePayable => '2502',

            // Equity Accounts
            self::OwnerCapital => '3101',
            self::OwnerDrawings => '3102',
            self::RetainedEarnings => '3103',
            self::ShareCapital => '3104',

            // Revenue Accounts
            self::SalesRevenue => '4101',
            self::ServiceRevenue => '4102',
            self::InterestIncome => '4201',
            self::OtherIncome => '4202',
            self::SalesReturnsAndAllowances => '4301',

            // COGS
            self::CostOfRevenue => '5101',

            // Operating Expense Accounts
            self::SalaryExpense => '5201',
            self::RentExpense => '5202',
            self::UtilitiesExpense => '5203',
            self::DepreciationExpense => '5204',
            self::BadDebtExpense => '5205',
            self::InsuranceExpense => '5206',
            self::OfficeSuppliesExpense => '5207',

            // Non-Operating Expense Accounts
            self::InterestExpense => '5301',
            self::TaxExpense => '5302',
            self::LossOnDisposal => '5303',
        };
    }

    public function getLabel(): string
    {
        return __('accounting::accounting.account_system_key.' . $this->value);
    }
}
