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

        $this->seedBroadcast($receiving);
        $this->seedOverdueVouchers();
    }

    /** A department memo broadcast to 15 staff (to demo the concerned-staff panel). */
    private function seedBroadcast(User $sender): void
    {
        $softdev = \App\Models\Division::where('code', 'SOFTDEV')->first();
        if (! $softdev) {
            return;
        }

        // Create 15 demo staff in PICTO · Software Development.
        $recipients = collect();
        for ($i = 1; $i <= 15; $i++) {
            $u = User::firstOrCreate(
                ['email' => "softdev{$i}@pgc.test"],
                [
                    'name' => 'SoftDev Staff '.$i,
                    'username' => "softdev{$i}",
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    'department_id' => $softdev->department_id,
                    'division_id' => $softdev->id,
                    'position' => 'Developer',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $u->syncRoles(['Staff']);
            $recipients->push($u);
        }

        $memo = Document::create([
            'title' => 'Q3 All-Staff Memo: Updated Office Hours',
            'document_type' => 'Memorandum',
            'description' => 'Please be advised of the updated office hours effective next month.',
            'source' => 'PICTO',
            'priority' => 'normal',
            'status' => 'released',
            'is_broadcast' => true,
            'division_id' => $softdev->id,
            'department_id' => $softdev->department_id,
            'created_by' => $sender->id,
            'current_holder_id' => null,
            'received_at' => now()->subDay(),
            'released_at' => now()->subDay(),
        ]);
        DocumentLog::create(['document_id' => $memo->id, 'action' => 'encoded', 'actor_id' => $sender->id, 'remarks' => 'Memo encoded for broadcast.']);
        DocumentLog::create(['document_id' => $memo->id, 'action' => 'released', 'actor_id' => $sender->id, 'remarks' => 'Broadcast to 15 staff (division).']);

        // Attach all; mark the first 6 as already acknowledged so the progress bar shows partial.
        foreach ($recipients as $idx => $r) {
            $memo->assignees()->syncWithoutDetaching([$r->id => ['acknowledged_at' => $idx < 6 ? now()->subHours(3) : null]]);
        }
    }

    /** Vouchers in the Accounting Office that breach the 7-day SLA (for the SLA report). */
    private function seedOverdueVouchers(): void
    {
        $acct = User::where('email', 'acctg.receiving@pgc.test')->first();
        $staff = User::where('email', 'disb.staff@pgc.test')->first();
        if (! $acct) {
            return;
        }

        // A) Completed late — 11-day turnaround vs 7-day SLA.
        $v1 = Document::create([
            'title' => 'Disbursement Voucher - Office Supplies', 'document_type' => 'Voucher', 'voucher_number' => 'DV-2026-0001',
            'tracking_code' => Document::trackingCodeForVoucher('DV-2026-0001'),
            'description' => 'Payment for office supplies.', 'source' => 'PACCO', 'priority' => 'high', 'status' => 'completed',
            'division_id' => $acct->division_id, 'department_id' => $acct->department_id, 'created_by' => $acct->id,
            'current_holder_id' => $staff?->id ?? $acct->id,
            'received_at' => now()->subDays(14), 'released_at' => now()->subDays(13), 'completed_at' => now()->subDays(3),
        ]);
        $v1->assignees()->syncWithoutDetaching(array_filter([$acct->id, $staff?->id]));
        DocumentLog::create(['document_id' => $v1->id, 'action' => 'encoded', 'actor_id' => $acct->id, 'remarks' => 'Voucher received.']);
        DocumentLog::create(['document_id' => $v1->id, 'action' => 'completed', 'actor_id' => $staff?->id ?? $acct->id, 'remarks' => 'Processed (late).']);

        // B) Still open and overdue — received 10 days ago, not yet completed.
        $v2 = Document::create([
            'title' => 'Disbursement Voucher - Travel Reimbursement', 'document_type' => 'Voucher', 'voucher_number' => 'DV-2026-0002',
            'tracking_code' => Document::trackingCodeForVoucher('DV-2026-0002'),
            'description' => 'Travel reimbursement claim.', 'source' => 'PACCO', 'priority' => 'urgent', 'status' => 'received',
            'division_id' => $acct->division_id, 'department_id' => $acct->department_id, 'created_by' => $acct->id,
            'current_holder_id' => $staff?->id ?? $acct->id,
            'received_at' => now()->subDays(10), 'released_at' => now()->subDays(10),
        ]);
        $v2->assignees()->syncWithoutDetaching(array_filter([$acct->id, $staff?->id]));
        DocumentLog::create(['document_id' => $v2->id, 'action' => 'encoded', 'actor_id' => $acct->id, 'remarks' => 'Voucher received.']);
        DocumentLog::create(['document_id' => $v2->id, 'action' => 'received', 'actor_id' => $staff?->id ?? $acct->id, 'remarks' => 'Under review.']);
    }
}
