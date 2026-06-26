<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 'all' = available to every office; 'restricted' = only offices in the pivot.
        if (! Schema::hasColumn('document_types', 'availability')) {
            Schema::table('document_types', function (Blueprint $t) {
                $t->string('availability', 12)->default('all')->after('name');
            });
        }

        if (! Schema::hasTable('document_type_department')) {
            Schema::create('document_type_department', function (Blueprint $t) {
                $t->id();
                $t->foreignId('document_type_id')->constrained()->cascadeOnDelete();
                $t->foreignId('department_id')->constrained()->cascadeOnDelete();
                $t->unique(['document_type_id', 'department_id']);
            });
        }

        // Consolidate any per-department duplicate types into ONE canonical type
        // (same name, no department) that is "restricted" to those offices via the pivot.
        if (Schema::hasColumn('document_types', 'department_id')) {
            foreach (DB::table('document_types')->whereNotNull('department_id')->get() as $row) {
                $canonicalId = DB::table('document_types')
                    ->whereNull('department_id')->where('name', $row->name)->value('id');

                if (! $canonicalId) {
                    // No global twin — promote this row to the canonical (drop its office, mark restricted).
                    DB::table('document_types')->where('id', $row->id)
                        ->update(['department_id' => null, 'availability' => 'restricted']);
                    $canonicalId = $row->id;
                } else {
                    DB::table('document_types')->where('id', $canonicalId)->update(['availability' => 'restricted']);
                }

                DB::table('document_type_department')->insertOrIgnore([
                    'document_type_id' => $canonicalId,
                    'department_id' => $row->department_id,
                ]);

                if ((int) $canonicalId !== (int) $row->id) {
                    DB::table('document_types')->where('id', $row->id)->delete();
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_department');
        if (Schema::hasColumn('document_types', 'availability')) {
            Schema::table('document_types', function (Blueprint $t) {
                $t->dropColumn('availability');
            });
        }
    }
};
