<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PersonsExportAdvanced implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $persons;
     public function __construct(Collection $persons)
    {
        $this->persons = $persons;
    }

    /**
     * Return collection of persons
     */
    public function collection()
    {
        return $this->persons;
    }

    /**
     * Define headings for the Excel file
     */
    public function headings(): array
    {
        return [
            'Person ID',
            'Full Name',
            'Given Name',
            'Middle Name',
            'Family Name',
            'Date of Birth',
            'Age',
            'Gender',
            'Primary Phone',
            'Primary Email',
            'National ID',
            'Classifications',
            'Address',
            'City',
            'District',
            'Country',
            'Organizations',
            'Status',
            'Created Date',
            'Updated Date',
        ];
    }

    /**
     * Map each person to export format
     */
    public function map($person): array
    {
        return [
            $person->person_id,
            $person->full_name,
            $person->given_name,
            $person->middle_name,
            $person->family_name,
            $person->date_of_birth ? $person->date_of_birth->format('Y-m-d') : '',
            $person->age ?? '',
            $person->gender ? ucfirst($person->gender) : '',
            $person->primaryPhone() ? $person->primaryPhone()->number : '',
            $person->primaryEmail() ? $person->primaryEmail()->email : '',
            $person->nationalId() ? $person->nationalId()->identifier : '',
            $person->classification ? implode(', ', array_map('ucfirst', $person->classification)) : '',
            $person->address,
            $person->city,
            $person->district,
            $person->country,
            $this->getOrganizationsString($person),
            ucfirst($person->status),
            $person->created_at->format('Y-m-d H:i:s'),
            $person->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // Person ID
            'B' => 25, // Full Name
            'C' => 15, // Given Name
            'D' => 15, // Middle Name
            'E' => 15, // Family Name
            'F' => 12, // Date of Birth
            'G' => 8,  // Age
            'H' => 10, // Gender
            'I' => 15, // Primary Phone
            'J' => 25, // Primary Email
            'K' => 15, // National ID
            'L' => 20, // Classifications
            'M' => 30, // Address
            'N' => 15, // City
            'O' => 15, // District
            'P' => 15, // Country
            'Q' => 30, // Organizations
            'R' => 10, // Status
            'S' => 18, // Created Date
            'T' => 18, // Updated Date
        ];
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Auto-filter for the header row
                $sheet->setAutoFilter('A1:T1');
                
                // Freeze the header row
                $sheet->freezePane('A2');
                
                // Add borders to all data
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Alternate row colors
                for ($row = 2; $row <= $highestRow; $row++) {
                    if ($row % 2 == 0) {
                        $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F2F2F2');
                    }
                }
                
                // Center align specific columns
                $sheet->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Person ID
                $sheet->getStyle('F:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Date of Birth
                $sheet->getStyle('G:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Age
                $sheet->getStyle('H:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Gender
                $sheet->getStyle('R:R')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Status
                
                // Wrap text for address and Organizations columns
                $sheet->getStyle('M:M')->getAlignment()->setWrapText(true); // Address
                $sheet->getStyle('Q:Q')->getAlignment()->setWrapText(true); // Organizations
            },
        ];
    }

    /**
     * Get Organizations string for a person
     */
    private function getOrganizationsString($person): string
    {
        if (!$person->Organizations || $person->Organizations->isEmpty()) {
            return '';
        }

        $orgStrings = [];
        foreach ($person->Organizations as $Organization) {
            $orgString = $Organization->name;
            
            if ($Organization->pivot->role_title) {
                $orgString .= ' (' . $Organization->pivot->role_title . ')';
            } elseif ($Organization->pivot->role_type) {
                $orgString .= ' (' . ucfirst($Organization->pivot->role_type) . ')';
            }
            
            $orgStrings[] = $orgString;
        }

        return implode('; ', $orgStrings);
    }
}
