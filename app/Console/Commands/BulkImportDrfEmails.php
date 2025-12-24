<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Drf;

class BulkImportDrfEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drf:bulk-import-emails {--sponsor-id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk import email addresses into DRF table with sponsor_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sponsorId = $this->option('sponsor-id');
        
        $emails = [
            'prithvithakur1@rediffmail.com',
            'laxmanvangara@gmail.com',
            'dpushpa761@gmail.com',
            'nrynsngh@gmail.com',
            'en24d0001@iiitdm.ac.in',
            'kandharaja@iiitdm.ac.in',
            'sampat.minnu2009@gmail.com',
            'mrjohnramesh@gmail.com',
            'allapuvenkat.72@gmail.com',
            'soosai.rathinam@gmail.com',
            'nandita.sarkarghosh@gmail.com',
            'grizzlefare@gmail.com',
            'eijukeishan@gmail.com',
            'sumathi.natraj@gmail.com',
            'vinayadharraju@gmail.com',
            'dineshthehuman@gmail.com',
            'bishakhabhardwaj734@gmail.com',
            'buravinaykumar123@gmail.com',
            'nikidhana@gmail.com',
        ];

        $this->info("Starting bulk import of " . count($emails) . " emails with sponsor_id = {$sponsorId}...");
        
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($emails as $email) {
            $email = trim($email);
            
            if (empty($email)) {
                $skipped++;
                continue;
            }

            // Check if DRF already exists for this email and 9th_conference
            $drf = Drf::where('email', $email)
                ->where('conference_attendance', '9th_conference')
                ->orderByDesc('created_at')
                ->first();

            if ($drf) {
                // Update existing record with sponsor_id
                if ($drf->sponsor_id != $sponsorId) {
                    $drf->sponsor_id = $sponsorId;
                    $drf->save();
                    $updated++;
                    $this->line("Updated: {$email}");
                } else {
                    $skipped++;
                    $this->line("Skipped (already has sponsor_id): {$email}");
                }
            } else {
                // Create new DRF record with minimal data
                Drf::create([
                    'email' => $email,
                    'sponsor_id' => $sponsorId,
                    'conference_attendance' => '9th_conference',
                    'member' => 'No',
                    'payment_status' => 'unpaid',
                ]);
                $created++;
                $this->line("Created: {$email}");
            }
        }

        $this->info("\n=== Import Summary ===");
        $this->info("Created: {$created}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");
        $this->info("Total processed: " . ($created + $updated + $skipped));
        
        return Command::SUCCESS;
    }
}
