<?php

declare(strict_types=1);

namespace App\Helpers\ProjectExpense;

use App\Models\ProjectActivityPlan;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelQuarterExtractor
{
    private const QUARTER_MAP = [
        'पहिलो' => 1,
        'दोस्रो' => 2,
        'तेस्रो' => 3,
        'चौथो' => 4,
    ];

    public function extractQuarterFromExcel(UploadedFile $file): ?int
    {
        try {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
            $header = trim((string) $sheet->getCell('A3')->getValue());

            foreach (self::QUARTER_MAP as $word => $num) {
                if ($this->containsQuarterKeywords($header, $word)) {
                    return $num;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function containsQuarterKeywords(string $header, string $word): bool
    {
        return strpos($header, $word) !== false && strpos($header, 'त्रैमास') !== false;
    }
}
