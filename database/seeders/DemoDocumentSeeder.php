<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $receiving = User::where('email', 'receiving@pgc.test')->first();
        $isdaStaff = User::where('email', 'dba.staff@pgc.test')->first();
        $ictStaff  = User::where('email', 'net.staff@pgc.test')->first();

        if (! $receiving) {
            return;
        }

        // 1) A document just released to ISDA staff (waiting to be received).
        $doc1 = Document::create([
            'title' => 'Request for Database Server Upgrade',
            'reference_no' => 'MEMO-2026-014',
            'document_type' => 'Memorandum',
            'description' => 'Memorandum requesting the upgrade of the central database server.',
            'source' => 'Office of the Governor',
            'priority' => 'high',
            'status' => 'released',
            'division_id' => $receiving->division_id,
            'department_id' => $receiving->department_id,
            'created_by' => $receiving->id,
            'current_holder_id' => $isdaStaff?->id,
            'received_at' => now()->subDays(2),
            'released_at' => now()->subDay(),
        ]);
        $doc1->assignees()->syncWithoutDetaching([$receiving->id, $isdaStaff?->id]);
        DocumentLog::create(['document_id' => $doc1->id, 'action' => 'encoded', 'actor_id' => $receiving->id, 'remarks' => 'Document received at the department and encoded.']);
        DocumentLog::create(['document_id' => $doc1->id, 'action' => 'assigned', 'actor_id' => $receiving->id, 'to_user_id' => $isdaStaff?->id, 'remarks' => 'Assigned to ISDA for evaluation.']);
        DocumentLog::create(['document_id' => $doc1->id, 'action' => 'released', 'actor_id' => $receiving->id, 'to_user_id' => $isdaStaff?->id, 'remarks' => 'Released. QR printed and attached.']);

        // 2) A document already received and being worked on by ICT staff.
        $doc2 = Document::create([
            'title' => 'Network Cabling Maintenance Report',
            'reference_no' => 'RPT-2026-009',
            'document_type' => 'Report',
            'description' => 'Quarterly maintenance report for network cabling.',
            'source' => 'ICT Division',
            'priority' => 'normal',
            'status' => 'received',
            'division_id' => $receiving->division_id,
            'department_id' => $receiving->department_id,
            'created_by' => $receiving->id,
            'current_holder_id' => $ictStaff?->id,
            'received_at' => now()->subDays(5),
            'released_at' => now()->subDays(4),
        ]);
        $doc2->assignees()->syncWithoutDetaching([$receiving->id, $ictStaff?->id]);
        DocumentLog::create(['document_id' => $doc2->id, 'action' => 'encoded', 'actor_id' => $receiving->id, 'remarks' => 'Encoded by receiving staff.']);
        DocumentLog::create(['document_id' => $doc2->id, 'action' => 'released', 'actor_id' => $receiving->id, 'to_user_id' => $ictStaff?->id, 'remarks' => 'Released to ICT.']);
        DocumentLog::create(['document_id' => $doc2->id, 'action' => 'received', 'actor_id' => $ictStaff?->id, 'remarks' => 'Received physically. Will start review.']);

        // 3) A completed/archived document.
        $doc3 = Document::create([
            'title' => 'Training Attendance Sheet - Q1',
            'reference_no' => 'ETD-2026-101',
            'document_type' => 'Attendance',
            'description' => 'Signed attendance sheet for the Q1 training program.',
            'source' => 'Education & Training Division',
            'priority' => 'low',
            'status' => 'archived',
            'division_id' => $receiving->division_id,
            'department_id' => $receiving->department_id,
            'created_by' => $receiving->id,
            'current_holder_id' => $receiving->id,
            'received_at' => now()->subDays(20),
            'released_at' => now()->subDays(19),
            'completed_at' => now()->subDays(15),
        ]);
        $doc3->assignees()->syncWithoutDetaching([$receiving->id]);
        DocumentLog::create(['document_id' => $doc3->id, 'action' => 'encoded', 'actor_id' => $receiving->id, 'remarks' => 'Encoded.']);
        DocumentLog::create(['document_id' => $doc3->id, 'action' => 'received', 'actor_id' => $receiving->id, 'remarks' => 'Received.']);
        DocumentLog::create(['document_id' => $doc3->id, 'action' => 'archived', 'actor_id' => $receiving->id, 'remarks' => 'Filed and archived. Task complete.']);
    }
}
