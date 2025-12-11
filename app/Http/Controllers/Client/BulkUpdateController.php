<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulkUpdateController extends Controller
{
    use Response;

    /**
     * Bulk update member_date from uploaded Excel/CSV file
     */
    public function bulkUpdateMemberDate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // Max 10MB
                'member_date' => 'nullable|date_format:Y-m-d',
                'email_column' => 'nullable|string',
                'date_column' => 'nullable|string',
                'dry_run' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            $file = $request->file('file');
            $memberDate = $request->input('member_date') ?: date('Y-m-d');
            $emailColumn = $request->input('email_column', 'email');
            $dateColumn = $request->input('date_column');
            $dryRun = $request->boolean('dry_run', false);

            // Read the file
            $rows = $this->readFile($file);

            if (empty($rows)) {
                return $this->error('No data found in file', 400);
            }

            // Get headers
            $headers = array_keys($rows[0]);

            // Check if email column exists
            if (!in_array($emailColumn, $headers)) {
                return $this->error("Email column '{$emailColumn}' not found in file. Available columns: " . implode(', ', $headers), 400);
            }

            $updated = 0;
            $notFound = [];
            $errors = [];
            $wouldUpdate = [];

            if (!$dryRun) {
                DB::beginTransaction();
            }

            try {
                foreach ($rows as $index => $row) {
                    $email = trim($row[$emailColumn] ?? '');

                    if (empty($email)) {
                        $errors[] = "Row " . ($index + 2) . ": Empty email";
                        continue;
                    }

                    // If date column is provided and exists in file, use that date, otherwise use the input date
                    $rowMemberDate = null;
                    if ($dateColumn && isset($row[$dateColumn]) && !empty($row[$dateColumn])) {
                        $rowMemberDate = $this->parseDate($row[$dateColumn]);
                        if (!$rowMemberDate) {
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
                            $currentDate = $user->member_date ? $user->member_date->format('Y-m-d') : null;
                            $wouldUpdate[] = [
                                'email' => $email,
                                'name' => $user->name ?? 'N/A',
                                'current_date' => $currentDate,
                                'new_date' => $rowMemberDate,
                            ];
                            $updated++;
                        } else {
                            $user->member_date = $rowMemberDate;
                            $user->save();
                            $updated++;
                        }
                    } else {
                        $notFound[] = $email;
                    }
                }

                if (!$dryRun) {
                    DB::commit();
                }

                $response = [
                    'total_rows' => count($rows),
                    'updated' => $updated,
                    'not_found' => count($notFound),
                    'not_found_emails' => $notFound,
                    'errors' => count($errors),
                    'error_details' => $errors,
                ];

                if ($dryRun) {
                    $response['dry_run'] = true;
                    $response['would_update'] = $wouldUpdate;
                    $response['message'] = 'Dry run completed. No changes were made.';
                } else {
                    $response['dry_run'] = false;
                    $response['message'] = 'Bulk update completed successfully.';
                }

                return $this->success($response['message'], 200, $response);

            } catch (\Exception $e) {
                if (!$dryRun) {
                    DB::rollBack();
                }
                return $this->error('Update failed: ' . $e->getMessage(), 500, [
                    'exception' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ]);
            }

        } catch (\Throwable $e) {
            return $this->error('File processing failed', 500, [
                'exception' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ]);
        }
    }

    /**
     * Read file (CSV or Excel)
     */
    private function readFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        if ($extension === 'csv' || $extension === 'txt') {
            return $this->readCsv($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->readExcel($filePath, $extension);
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
            throw new \Exception("Could not open file");
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return [];
        }

        $headers = array_map('trim', $headers);

        // Read data rows
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
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
     * Read Excel file
     */
    private function readExcel($filePath, $extension)
    {
        // Check if PhpSpreadsheet is available
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return $this->readExcelWithPhpSpreadsheet($filePath);
        }

        // Fallback: Convert to CSV if possible
        throw new \Exception("Excel files require PhpSpreadsheet. Please install: composer require phpoffice/phpspreadsheet");
    }

    /**
     * Read Excel using PhpSpreadsheet
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

