<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts the earlier per-TYPE availability model (v1.6.0: an `availability` column
 * + a document_type_department pivot) into a per-OFFICE restriction:
 * departments.restricted_doc_types (null/[] = all types). All document types become
 * global; an office can be limited to a subset. Idempotent — safe whether or not the
 * old artifacts exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('departments', 'restricted_doc_types')) {
            Schema::table('departments', function (Blueprint $t) {
                $t->json('restricted_doc_types')->nullable()->after('sla_document_type');
            });
        }

        // Rebuild each office's restricted list from the old type→office pivot.
        if (Schema::hasTable('document_type_department')) {
            $rows = DB::table('document_type_department as p')
                ->join('document_types as t', 't.id', '=', 'p.document_type_id')
                ->select('p.department_id', 't.name')->get();
            $byDept = [];
            foreach ($rows as $r) {
                $byDept[$r->department_id][] = $r->name;
            }
            foreach ($byDept as $deptId => $names) {
                DB::table('departments')->where('id', $deptId)
                    ->update(['restricted_doc_types' => json_encode(array_values(array_unique($names)))]);
            }
        }

        // Accounting offices are limited to Voucher/Payroll.
        DB::table('departments')->where('is_accounting', true)->whereNull('restricted_doc_types')
            ->update(['restricted_doc_types' => json_encode(['Voucher', 'Payroll'])]);

        // Voucher & Payroll are normal global types; no legacy voucher-number flag.
        DB::table('document_types')->whereIn('name', ['Voucher', 'Payroll'])->update(['requires_voucher' => false]);

        // Tear down the per-type artifacts.
        Schema::dropIfExists('document_type_department');
        if (Schema::hasColumn('document_types', 'availability')) {
            Schema::table('document_types', function (Blueprint $t) {
                $t->dropColumn('availability');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('departments', 'restricted_doc_types')) {
            Schema::table('departments', function (Blueprint $t) {
                $t->dropColumn('restricted_doc_types');
            });
        }
    }
};
