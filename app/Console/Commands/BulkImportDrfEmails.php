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
            'prerna.kaushik@gateway.edu.in',
            'shivani.narwal@gateway.edu.in',
            'taruna.garg@gateway.edu.in',
            'deepu.dahiya@gateway.edu.in',
            'kanika.khurana@gateway.edu.in',
            'soniya.gupta@gateway.edu.in',
            'mamta.arora@gateway.edu.in',
            'shailja.kumari@gateway.edu.in',
            'shish.pal@gateway.edu.in',
            'renu.bhuntel@gateway.edu.in',
            'sunita.saini@gateway.edu.in',
            'shakuntla.kumari@gateway.edu.in',
            'deepak.yadav@gateway.edu.in',
            'anju.saini@gateway.edu.in',
            'sanita.nain@gateway.edu.in',
            'simran@gateway.edu.in',
            'mohit.bansal@gateway.edu.in',
            'lalit.gahlawat@gateway.edu.in',
            'reena.pharma@gateway.edu.in',
            'davender.chauhan@gateway.edu.in',
            'vinayak.bhushan@gateway.edu.in',
            'birender.singh@gateway.edu.in',
            'priyankasharma@gateway.edu.in',
            'pooja.hooda@gateway.edu.in',
            'shivani.pannu@gateway.edu.in',
            'disha.biswas@gateway.edu.in',
            'gurdeep.singh@gateway.edu.in',
            'rajeev.sharma@gateway.edu.in',
            'dg@gateway.edu.in',
            'sunny.singla@gateway.edu.in',
            'administrator@gateway.edu.in',
            'ajay.kumar@gateway.edu.in',
            'ashok.saini@gateway.edu.in',
            'aakash@gateway.edu.in',
            'hodcs@gateway.edu.in',
            'rachna.dhaka@gateway.edu.in',
            'hema.arora@gateway.edu.in',
            'ashish.aggarwal@gateway.edu.in',
            'nutan.kumari@gateway.edu.in',
            'priya.tyagi@gateway.edu.in',
            'vikas.kuchhal@gateway.edu.in',
            'pratibha.dabas@gateway.edu.in',
            'hod.dca@gateway.edu.in',
            'ajay.goel@gateway.edu.in',
            'namrata.gaur@gateway.edu.in',
            'mandvi.sharma@gateway.edu.in',
            'dinesh.rohilla@gateway.edu.in',
            'aruna.kapoor@gateway.edu.in',
            'neha.kalia@gateway.edu.in',
            'kanika.manchanda@gateway.edu.in',
            'anita.goswami@gateway.edu.in',
            'leesha.patlaan@gateway.edu.in',
            'head.hr@gateway.edu.in',
            'megha.sharma@gateway.edu.in',
            'headappliedcell@gateway.edu.in',
            'head.placements@gateway.edu.in',
            'chetna.kapoor@gateway.edu.in',
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
