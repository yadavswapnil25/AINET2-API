<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixStoragePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:fix-permissions 
                            {--check : Only check permissions without fixing}
                            {--force : Force fix even if permissions seem correct}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and ensure storage directories have correct permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking storage permissions...');
        
        $directories = [
            'storage/app/public',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/testing',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ];

        $issues = [];
        $fixed = 0;

        foreach ($directories as $dir) {
            $path = base_path($dir);
            
            // Create directory if it doesn't exist
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->line("✓ Created: {$dir}");
                $fixed++;
            }

            // Check if directory is writable
            if (!is_writable($path)) {
                $issues[] = $dir;
                $this->error("✗ Not writable: {$dir}");
                
                if (!$this->option('check')) {
                    // Try to make it writable (may require sudo)
                    @chmod($path, 0775);
                    if (is_writable($path)) {
                        $this->line("  → Fixed permissions for {$dir}");
                        $fixed++;
                    } else {
                        $this->warn("  → Could not fix automatically. Run: sudo chmod -R 775 {$path}");
                    }
                }
            } else {
                $this->line("✓ Writable: {$dir}");
            }
        }

        if (!empty($issues) && !$this->option('check')) {
            $this->newLine();
            $this->warn('Some directories could not be fixed automatically.');
            $this->info('Run this command on your server:');
            $this->line('  sudo bash fix-permissions.sh');
        }

        if (empty($issues) || $fixed > 0) {
            $this->newLine();
            $this->info('Storage permissions check completed!');
            return 0;
        }

        return 1;
    }
}
