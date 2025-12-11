<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulkUpdateMemberDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:bulk-update-member-date 
                            {file : Path to the Excel/CSV file}
                            {--date= : The member_date to set (format: Y-m-d). If not provided, uses current date}
                            {--email-column=email : Column name for email in the file}
                            {--date-column=member_date : Column name for member_date in the file (if date is in file)}
                            {--dry-run : Run without making changes to show what would be updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk update member_date for users by matching emails from an Excel/CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $memberDate = $this->option('date') ?: date('Y-m-d');
        $emailColumn = $this->option('email-column');
        $dateColumn = $this->option('date-column');
        $dryRun = $this->option('dry-run');

        // Validate file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // Validate date format
        $validator = Validator::make(['date' => $memberDate], [
            'date' => 'required|date_format:Y-m-d'
        ]);

        if ($validator->fails()) {
            $this->error("Invalid date format. Please use Y-m-d format (e.g., 2024-07-31)");
            return 1;
        }

        $this->info("Reading file: {$filePath}");
        
        // Read the file
        $rows = $this->readFile($filePath);
        if (empty($rows)) {
            $this->error("No data found in file");
            return 1;
        }

        $this->info("Found " . count($rows) . " rows in file");
        
        // Get headers (first row)
        $headers = array_keys($rows[0]);
        $this->info("Columns found: " . implode(', ', $headers));

        // Check if email column exists
        if (!in_array($emailColumn, $headers)) {
            $this->error("Email column '{$emailColumn}' not found in file. Available columns: " . implode(', ', $headers));
            return 1;
        }

        $updated = 0;
        $notFound = 0;
        $errors = [];
        $wouldUpdate = [];

        if ($dryRun) {
            $this->warn("=== DRY RUN MODE - No changes will be made ===");
        } else {
            DB::beginTransaction();
        }
        // dd($rows);
        try {
            foreach ($rows as $index => $row) {
                $email = trim($row[$emailColumn] ?? '');
                
                if (empty($email)) {
                    $errors[] = "Row " . ($index + 2) . ": Empty email";
                    continue;
                }

                // If date column is provided and exists in file, use that date, otherwise use the option date
                $rowMemberDate = null;
                if ($dateColumn && isset($row[$dateColumn]) && !empty($row[$dateColumn])) {
                    $rowMemberDate = $row[$dateColumn];
                    // Try to parse the date
                    $parsedDate = $this->parseDate($rowMemberDate);
                    if ($parsedDate) {
                        $rowMemberDate = $parsedDate;
                    } else {
                        $errors[] = "Row " . ($index + 2) . ": Invalid date format for {$email}";
                        continue;
                    }
                } else {
                    $rowMemberDate = $memberDate;
                }

                // Find user by email
                $user = User::where('email', $email)->first();

                if ($user) {
                    if ($dryRun) {
                        $currentDate = $user->member_date ? $user->member_date->format('Y-m-d') : 'NULL';
                        $wouldUpdate[] = [
                            'email' => $email,
                            'current_date' => $currentDate,
                            'new_date' => $rowMemberDate,
                            'name' => $user->name ?? 'N/A'
                        ];
                        $updated++;
                        $this->line("✓ Would update: {$email} ({$user->name}) from {$currentDate} -> {$rowMemberDate}");
                    } else {
                        $user->member_date = $rowMemberDate;
                        $user->save();
                        $updated++;
                        $this->line("✓ Updated: {$email} -> {$rowMemberDate}");
                    }
                } else {
                    $notFound++;
                    $this->warn("✗ Not found: {$email}");
                }
            }

            if (!$dryRun) {
                DB::commit();
            }

            $this->info("\n=== Summary ===");
            $this->info("Total rows processed: " . count($rows));
            if ($dryRun) {
                $this->info("Would update: {$updated}");
                $this->info("Not found: {$notFound}");
                if (!empty($wouldUpdate)) {
                    $this->info("\nDetailed preview of changes:");
                    $this->table(
                        ['Email', 'Name', 'Current Date', 'New Date'],
                        array_map(function ($item) {
                            return [
                                $item['email'],
                                $item['name'],
                                $item['current_date'],
                                $item['new_date']
                            ];
                        }, $wouldUpdate)
                    );
                }
            } else {
                $this->info("Successfully updated: {$updated}");
                $this->info("Not found: {$notFound}");
            }
            
            if (!empty($errors)) {
                $this->warn("Errors: " . count($errors));
                foreach ($errors as $error) {
                    $this->warn("  - {$error}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error("Error: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . " Line: " . $e->getLine());
            return 1;
        }
    }

    /**
     * Read file (CSV or Excel)
     */
    private function readFile($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->readCsv($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            // For Excel files, we'll use a simple approach
            // Note: You may need to install PhpSpreadsheet for better Excel support
            return $this->readExcelSimple($filePath);
        } else {
            throw new \Exception("Unsupported file format. Please use CSV or Excel files.");
        }
    }

    /**
     * Read CSV file
     */
    private function readCsv($filePath)
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new \Exception("Could not open file: {$filePath}");
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return [];
        }

        // Convert headers to lowercase keys for consistency
        $headers = array_map('trim', $headers);

        // Read data rows
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue; // Skip malformed rows
            }
            
            $row = array_combine($headers, array_map('trim', $data));
            if ($row) {
                $rows[] = $row;
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Simple Excel reader (basic support)
     * For better Excel support, install: composer require phpoffice/phpspreadsheet
     */
    private function readExcelSimple($filePath)
    {
        // Check if PhpSpreadsheet is available
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return $this->readExcelWithPhpSpreadsheet($filePath);
        }

        // Excel files cannot be read without PhpSpreadsheet
        throw new \Exception(
            "Excel files (.xlsx/.xls) require PhpSpreadsheet library.\n" .
            "Please either:\n" .
            "1. Install PhpSpreadsheet: composer require phpoffice/phpspreadsheet\n" .
            "   (Requires PHP extensions: ext-gd and ext-zip)\n" .
            "2. OR convert your Excel file to CSV format:\n" .
            "   - Open in Excel/Google Sheets\n" .
            "   - File → Save As → CSV (Comma delimited)\n" .
            "   - Use the .csv file with this command"
        );
    }

    /**
     * Read Excel using PhpSpreadsheet (if available)
     */
    private function readExcelWithPhpSpreadsheet($filePath)
    {
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        // Get headers (first row)
        $headers = [];
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            $cellValue = $worksheet->getCell($cellCoordinate)->getValue();
            $headers[] = trim($cellValue ?? '');
        }

        // Get data rows
        $highestRow = $worksheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            foreach ($headers as $colIndex => $header) {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . $row;
                $cell = $worksheet->getCell($cellCoordinate);
                $cellValue = $cell->getValue();
                
                // Check if cell contains a date (Excel stores dates as serial numbers)
                if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    // Convert Excel date serial number to PHP DateTime
                    $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cellValue);
                    $cellValue = $dateValue->format('m/d/Y'); // Format as m/d/Y to match Excel display
                } else {
                    $cellValue = trim($cellValue ?? '');
                }
                
                $rowData[$header] = $cellValue;
            }
            $rows[] = $rowData;
        }

        return $rows;
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        // Try common date formats (prioritize US format m/d/Y)
        $formats = [
            'n/j/Y',      // 2/21/2024 (month/day/year without leading zeros)
            'm/d/Y',      // 02/21/2024 (month/day/year with leading zeros)
            'm-d-Y',      // 02-21-2024
            'n-j-Y',      // 2-21-2024
            'Y-m-d',      // 2024-02-21
            'Y/m/d',      // 2024/02/21
            'd-m-Y',      // 21-02-2024
            'd/m/Y',      // 21/02/2024
            'Y-m-d H:i:s',
            'Y-m-d H:i:s.v',
            'm/d/Y H:i:s', // 2/21/2024 12:00:00
            'n/j/Y H:i:s', // 2/21/2024 12:00:00
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($dateString));
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try strtotime as fallback (handles various formats)
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }
}

