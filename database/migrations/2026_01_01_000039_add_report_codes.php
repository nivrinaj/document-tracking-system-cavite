<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funds', function (Blueprint $t) {
            if (! Schema::hasColumn('funds', 'report_code')) {
                $t->string('report_code', 20)->nullable()->after('code');
            }
        });
        Schema::table('nature_of_transactions', function (Blueprint $t) {
            if (! Schema::hasColumn('nature_of_transactions', 'report_code')) {
                $t->string('report_code', 20)->nullable()->after('name');
            }
        });

        // Sensible defaults — editable later in Accounting Setup.
        foreach (['General Funds' => 'GF', 'SEF' => 'SEF', 'Trust Fund' => 'TF', 'Gen. Fund 20% Development Fund' => 'GFDF'] as $name => $code) {
            DB::table('funds')->where('name', $name)->update(['report_code' => $code]);
        }
        foreach (['Payment' => 'Payt.', 'Reimbursement' => 'Reimb.', 'Liquidation' => 'Liq.', 'Cash Advance' => 'CA', 'Refund' => 'Refund'] as $name => $code) {
            DB::table('nature_of_transactions')->where('name', $name)->update(['report_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('funds', fn (Blueprint $t) => $t->dropColumn('report_code'));
        Schema::table('nature_of_transactions', fn (Blueprint $t) => $t->dropColumn('report_code'));
    }
};
