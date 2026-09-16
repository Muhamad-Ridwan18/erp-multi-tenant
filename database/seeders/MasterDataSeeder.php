<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Location;
use App\Models\PaymentTerm;
use App\Models\ProductCategory;
use App\Models\Tax;
use App\Models\TaxGroup;
use App\Models\Uom;
use App\Models\UomCategory;
use App\Models\Warehouse;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $idr = Currency::query()->updateOrCreate(
            ['code' => 'IDR'],
            ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'rate' => 1, 'is_active' => true]
        );

        $unitCat = UomCategory::query()->updateOrCreate(['name' => 'Unit']);
        $pcs = Uom::query()->updateOrCreate(
            ['uom_category_id' => $unitCat->id, 'code' => 'pcs'],
            ['name' => 'Units', 'ratio' => 1, 'is_active' => true]
        );
        Uom::query()->updateOrCreate(
            ['uom_category_id' => $unitCat->id, 'code' => 'dz'],
            ['name' => 'Dozen', 'ratio' => 12, 'is_active' => true]
        );

        ProductCategory::query()->updateOrCreate(['name' => 'All']);

        $saleGroup = TaxGroup::query()->updateOrCreate(['name' => 'Sales Tax']);
        Tax::query()->updateOrCreate(
            ['code' => 'PPN11'],
            [
                'name' => 'PPN 11%',
                'amount' => 11,
                'type' => 'both',
                'price_include' => 'exclusive',
                'tax_group_id' => $saleGroup->id,
                'is_active' => true,
            ]
        );
        Tax::query()->updateOrCreate(
            ['code' => 'EXEMPT'],
            [
                'name' => 'Exempt 0%',
                'amount' => 0,
                'type' => 'both',
                'price_include' => 'exclusive',
                'tax_group_id' => $saleGroup->id,
                'is_active' => true,
            ]
        );

        PaymentTerm::query()->updateOrCreate(['name' => 'Immediate Payment'], ['days' => 0, 'is_active' => true]);
        PaymentTerm::query()->updateOrCreate(['name' => 'Net 15'], ['days' => 15, 'is_active' => true]);
        PaymentTerm::query()->updateOrCreate(['name' => 'Net 30'], ['days' => 30, 'is_active' => true]);

        $ar = Account::query()->updateOrCreate(['code' => '1100'], ['name' => 'Accounts Receivable', 'type' => 'asset']);
        $ap = Account::query()->updateOrCreate(['code' => '2100'], ['name' => 'Accounts Payable', 'type' => 'liability']);
        $cash = Account::query()->updateOrCreate(['code' => '1000'], ['name' => 'Cash', 'type' => 'asset']);
        $bank = Account::query()->updateOrCreate(['code' => '1010'], ['name' => 'Bank', 'type' => 'asset']);
        $income = Account::query()->updateOrCreate(['code' => '4000'], ['name' => 'Sales Income', 'type' => 'income']);
        $expense = Account::query()->updateOrCreate(['code' => '5000'], ['name' => 'Cost of Goods / Purchases', 'type' => 'expense']);
        $taxOut = Account::query()->updateOrCreate(['code' => '2200'], ['name' => 'Tax Payable', 'type' => 'liability']);
        $taxIn = Account::query()->updateOrCreate(['code' => '1200'], ['name' => 'Tax Receivable', 'type' => 'asset']);
        Account::query()->updateOrCreate(['code' => '1300'], ['name' => 'Inventory Asset', 'type' => 'asset']);

        Journal::query()->updateOrCreate(['code' => 'INV'], ['name' => 'Customer Invoices', 'type' => 'sale', 'default_account_id' => $income->id]);
        Journal::query()->updateOrCreate(['code' => 'BILL'], ['name' => 'Vendor Bills', 'type' => 'purchase', 'default_account_id' => $expense->id]);
        Journal::query()->updateOrCreate(['code' => 'CASH'], ['name' => 'Cash', 'type' => 'cash', 'default_account_id' => $cash->id]);
        Journal::query()->updateOrCreate(['code' => 'BANK'], ['name' => 'Bank', 'type' => 'bank', 'default_account_id' => $bank->id]);
        Journal::query()->updateOrCreate(['code' => 'MISC'], ['name' => 'Miscellaneous', 'type' => 'general', 'default_account_id' => null]);

        $wh = Warehouse::query()->updateOrCreate(
            ['code' => 'WH01'],
            ['name' => 'Main Warehouse', 'is_active' => true]
        );

        Location::query()->updateOrCreate(
            ['warehouse_id' => $wh->id, 'code' => 'STOCK'],
            ['name' => 'Stock', 'type' => 'internal', 'is_active' => true]
        );
        Location::query()->updateOrCreate(
            ['warehouse_id' => null, 'code' => 'VENDORS'],
            ['name' => 'Vendors', 'type' => 'vendor', 'is_active' => true]
        );
        Location::query()->updateOrCreate(
            ['warehouse_id' => null, 'code' => 'CUSTOMERS'],
            ['name' => 'Customers', 'type' => 'customer', 'is_active' => true]
        );
        Location::query()->updateOrCreate(
            ['warehouse_id' => null, 'code' => 'SCRAP'],
            ['name' => 'Scrap', 'type' => 'inventory', 'is_active' => true]
        );
        Location::query()->updateOrCreate(
            ['warehouse_id' => null, 'code' => 'PRODUCTION'],
            ['name' => 'Production', 'type' => 'production', 'is_active' => true]
        );

        WorkCenter::query()->updateOrCreate(
            ['code' => 'WC01'],
            ['name' => 'Assembly', 'default_capacity' => 1, 'is_active' => true]
        );

        unset($idr, $pcs, $ar, $ap, $taxOut, $taxIn);
    }
}
