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
        $this->info("Memory usage: " . $this->formatBytes(memory_get_usage(true)));
        $startTime = microtime(true);
        
        // Process in chunks to avoid memory issues
        $chunkSize = 500; // Process 500 rows at a time
        $updated = 0;
        $notFound = 0;
        $errors = [];
        $wouldUpdate = [];
        $totalRows = 0;
        $headers = null;

        if ($dryRun) {
            $this->warn("=== DRY RUN MODE - No changes will be made ===");
        } else {
            DB::beginTransaction();
        }
        
        try {
            // Get headers first
            $headers = $this->getFileHeaders($filePath);
            if (empty($headers)) {
                $this->error("Could not read file headers");
                return 1;
            }
            
            $this->info("Columns found: " . implode(', ', $headers));
            
            // Check if email column exists
            if (!in_array($emailColumn, $headers)) {
                $this->error("Email column '{$emailColumn}' not found in file. Available columns: " . implode(', ', $headers));
                return 1;
            }
            
            // Process file in chunks
            $processStartTime = microtime(true);
            $chunkNumber = 0;
            
            $this->info("Processing file in chunks of {$chunkSize} rows...");
            
            foreach ($this->readFileInChunks($filePath, $chunkSize, $headers) as $chunkRows) {
                $chunkNumber++;
                $chunkStart = microtime(true);
                
                if ($totalRows === 0) {
                    // First chunk - estimate total rows
                    $estimatedTotal = $this->estimateTotalRows($filePath);
                    $bar = $this->output->createProgressBar($estimatedTotal);
                    $bar->start();
                }
                
                // Extract emails from this chunk
                $emails = [];
                foreach ($chunkRows as $row) {
                    $email = trim($row[$emailColumn] ?? '');
                    if (!empty($email)) {
                        $emails[] = $email;
                    }
                }
                
                if (empty($emails)) {
                    $bar->advance(count($chunkRows));
                    continue;
                }
                
                // Fetch users for this chunk
                $users = User::whereIn('email', $emails)->get()->keyBy('email');
                
                // Process each row in chunk
                foreach ($chunkRows as $rowIndex => $row) {
                    $totalRows++;
                    $email = trim($row[$emailColumn] ?? '');
                    
                    if (empty($email)) {
                        $errors[] = "Row " . ($totalRows + 1) . ": Empty email";
                        $bar->advance();
                        continue;
                    }

                    // If date column is provided and exists in file, use that date, otherwise use the option date
                    $rowMemberDate = null;
                    if ($dateColumn && isset($row[$dateColumn]) && !empty($row[$dateColumn])) {
                        $rowMemberDate = $row[$dateColumn];
                        // Try to parse the date
                        $parsedDate = $this->parseDate($rowMemberDate);
                        if ($parsedDate && $this->isValidDate($parsedDate)) {
                            $rowMemberDate = $parsedDate;
                        } else {
                            $errors[] = "Row " . ($totalRows + 1) . ": Invalid date format or out of range for {$email} (value: {$rowMemberDate})";
                            $bar->advance();
                            continue;
                        }
                    } else {
                        $rowMemberDate = $memberDate;
                    }

                    // Validate the final date before saving
                    if (!$this->isValidDate($rowMemberDate)) {
                        $errors[] = "Row " . ($totalRows + 1) . ": Invalid date value for {$email} (value: {$rowMemberDate})";
                        $bar->advance();
                        continue;
                    }

                    // Find user by email from pre-fetched collection
                    $user = $users->get($email);

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
                        } else {
                            $user->member_date = $rowMemberDate;
                            $user->save();
                            $updated++;
                        }
                    } else {
                        $notFound++;
                    }
                    
                    $bar->advance();
                }
                
                // Free memory after each chunk
                unset($chunkRows, $emails, $users);
                gc_collect_cycles();
                
                $chunkTime = round(microtime(true) - $chunkStart, 2);
                $this->line("\nChunk {$chunkNumber} processed in {$chunkTime}s. Memory: " . $this->formatBytes(memory_get_usage(true)));
            }
            
            if (isset($bar)) {
                $bar->finish();
                $this->newLine(2);
            }

            if (!$dryRun) {
                DB::commit();
            }

            $processTime = round(microtime(true) - $processStartTime, 2);
            $totalTime = round(microtime(true) - $startTime, 2);
            $this->info("\n=== Summary ===");
            $this->info("Total rows processed: {$totalRows}");
            $this->info("Processing time: {$processTime}s");
            $this->info("Total time: {$totalTime}s");
            $this->info("Peak memory usage: " . $this->formatBytes(memory_get_peak_usage(true)));
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
        // Use read-only mode for better performance
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true); // Only read data, skip formatting
        $reader->setReadEmptyCells(false); // Skip empty cells
        
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet */
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        // Get headers (first row) - limit to reasonable column count
        $headers = [];
        $maxColumns = 50; // Reasonable limit to avoid scanning too many columns
        
        for ($col = 1; $col <= $maxColumns; $col++) {
            $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            $cellValue = $worksheet->getCell($cellCoordinate)->getValue();
            if (empty($cellValue) && $col > 10) {
                // Stop if we hit empty columns after column 10
                break;
            }
            $headers[] = trim($cellValue ?? '');
        }

        // Get data rows - read cell by cell to properly detect dates
        $highestRow = $worksheet->getHighestDataRow();
        
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $isEmpty = true;
            
            foreach ($headers as $colIndex => $header) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $cellCoordinate = $colLetter . $row;
                $cell = $worksheet->getCell($cellCoordinate);
                
                // Get the actual cell value
                $cellValue = $cell->getValue();
                
                // Check if cell is formatted as a date or contains a date
                if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    // Convert Excel date serial number to PHP DateTime
                    try {
                        $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cellValue);
                        $cellValue = $dateValue->format('m/d/Y'); // Format as m/d/Y to match Excel display
                    } catch (\Exception $e) {
                        // If conversion fails, try to format as string
                        $cellValue = trim($cell->getFormattedValue() ?? '');
                    }
                } elseif (is_numeric($cellValue) && $cellValue > 1 && $cellValue < 1000000) {
                    // Check if it might be an Excel date serial number (reasonable range: 1 to 1,000,000)
                    // Excel dates start from 1900-01-01 (serial 1) to around 9999-12-31 (serial ~2,958,465)
                    try {
                        // Only try if it's in a reasonable date range
                        if ($cellValue >= 1 && $cellValue <= 2958465) {
                            $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cellValue);
                            $cellValue = $dateValue->format('m/d/Y');
                        } else {
                            $cellValue = trim($cellValue ?? '');
                        }
                    } catch (\Exception $e) {
                        // Not a date, keep as is
                        $cellValue = trim($cellValue ?? '');
                    }
                } else {
                    $cellValue = trim($cellValue ?? '');
                }
                
                if (!empty($cellValue)) {
                    $isEmpty = false;
                }
                
                $rowData[$header] = $cellValue;
            }
            
            if (!$isEmpty) {
                $rows[] = $rowData;
            }
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

        $dateString = trim($dateString);

        // If it's a pure number without separators and > 10000, it's likely not a date
        // (could be an ID or other numeric value)
        if (is_numeric($dateString) && (float)$dateString > 10000 && strpos($dateString, '/') === false && strpos($dateString, '-') === false) {
            // Check if it might be an Excel date serial number (reasonable range)
            // Excel dates: 1 = 1900-01-01, ~2958465 = 9999-12-31
            if ((float)$dateString >= 1 && (float)$dateString <= 2958465) {
                try {
                    $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$dateString);
                    $parsed = $dateValue->format('Y-m-d');
                    // Validate the parsed date is reasonable
                    $year = (int)$dateValue->format('Y');
                    if ($year >= 1900 && $year <= 2100) {
                        return $parsed;
                    }
                } catch (\Exception $e) {
                    // Not a valid Excel date
                }
            }
            // If it's a large number that's not in Excel date range, reject it
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
            $date = \DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                $parsed = $date->format('Y-m-d');
                // Validate the parsed date matches input and is reasonable
                $year = (int)$date->format('Y');
                if ($year >= 1900 && $year <= 2100) {
                    return $parsed;
                }
            }
        }

        // Try strtotime as fallback (handles various formats)
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            $parsed = date('Y-m-d', $timestamp);
            $year = (int)date('Y', $timestamp);
            // Validate year is reasonable
            if ($year >= 1900 && $year <= 2100) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Validate that a date string is valid and within reasonable range
     */
    private function isValidDate($dateString)
    {
        if (empty($dateString)) {
            return false;
        }

        // Check if it's a valid date format (Y-m-d)
        $date = \DateTime::createFromFormat('Y-m-d', $dateString);
        if ($date === false) {
            return false;
        }

        // Check if the parsed date matches the input (to catch invalid dates like 2024-13-45)
        if ($date->format('Y-m-d') !== $dateString) {
            return false;
        }

        // Check if year is within reasonable range (1900 to 2100)
        $year = (int) $date->format('Y');
        if ($year < 1900 || $year > 2100) {
            return false;
        }

        return true;
    }

    /**
     * Get file headers without loading entire file
     */
    private function getFileHeaders($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return [];
            }
            $headers = fgetcsv($handle);
            fclose($handle);
            return $headers ? array_map('trim', $headers) : [];
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                throw new \Exception("PhpSpreadsheet is required for Excel files");
            }
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $headers = [];
            $maxColumns = 50;
            for ($col = 1; $col <= $maxColumns; $col++) {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
                $cellValue = $worksheet->getCell($cellCoordinate)->getValue();
                if (empty($cellValue) && $col > 10) {
                    break;
                }
                $headers[] = trim($cellValue ?? '');
            }
            
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            return $headers;
        }

        return [];
    }

    /**
     * Read file in chunks to save memory
     */
    private function readFileInChunks($filePath, $chunkSize, $headers)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->readCsvInChunks($filePath, $chunkSize, $headers);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->readExcelInChunks($filePath, $chunkSize, $headers);
        }

        throw new \Exception("Unsupported file format");
    }

    /**
     * Read CSV in chunks
     */
    private function readCsvInChunks($filePath, $chunkSize, $headers)
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return;
        }

        // Skip header row
        fgetcsv($handle);

        $chunk = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }
            
            $row = array_combine($headers, array_map('trim', $data));
            if ($row) {
                $chunk[] = $row;
                
                if (count($chunk) >= $chunkSize) {
                    yield $chunk;
                    $chunk = [];
                }
            }
        }

        // Yield remaining rows
        if (!empty($chunk)) {
            yield $chunk;
        }

        fclose($handle);
    }

    /**
     * Read Excel in chunks
     */
    private function readExcelInChunks($filePath, $chunkSize, $headers)
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \Exception("PhpSpreadsheet is required for Excel files");
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestDataRow();
        
        $chunk = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            $isEmpty = true;
            
            foreach ($headers as $colIndex => $header) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                $cellCoordinate = $colLetter . $row;
                $cell = $worksheet->getCell($cellCoordinate);
                
                $cellValue = $cell->getValue();
                
                if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                    try {
                        $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cellValue);
                        $cellValue = $dateValue->format('m/d/Y');
                    } catch (\Exception $e) {
                        $cellValue = trim($cell->getFormattedValue() ?? '');
                    }
                } elseif (is_numeric($cellValue) && $cellValue > 1 && $cellValue < 1000000) {
                    try {
                        if ($cellValue >= 1 && $cellValue <= 2958465) {
                            $dateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cellValue);
                            $cellValue = $dateValue->format('m/d/Y');
                        } else {
                            $cellValue = trim($cellValue ?? '');
                        }
                    } catch (\Exception $e) {
                        $cellValue = trim($cellValue ?? '');
                    }
                } else {
                    $cellValue = trim($cellValue ?? '');
                }
                
                if (!empty($cellValue)) {
                    $isEmpty = false;
                }
                
                $rowData[$header] = $cellValue;
            }
            
            if (!$isEmpty) {
                $chunk[] = $rowData;
                
                if (count($chunk) >= $chunkSize) {
                    yield $chunk;
                    $chunk = [];
                    gc_collect_cycles();
                }
            }
        }
        
        if (!empty($chunk)) {
            yield $chunk;
        }
        
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * Estimate total rows in file (for progress bar)
     */
    private function estimateTotalRows($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $lineCount = 0;
            $handle = fopen($filePath, 'r');
            if ($handle) {
                while (fgets($handle) !== false) {
                    $lineCount++;
                }
                fclose($handle);
            }
            return max(1, $lineCount - 1); // Subtract header
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                return 1000; // Default estimate
            }
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestDataRow();
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            return max(1, $highestRow - 1); // Subtract header
        }

        return 1000; // Default estimate
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

