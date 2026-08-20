<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrganizationsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    protected Collection $organizations;

    public function __construct(Collection $organizations)
    {
        $this->organizations = $organizations;
    }

    public function collection(): Collection
    {
        return $this->organizations;
    }

    public function headings(): array
    {
        return [
            'Legal Name',
            'Code',
            'Category',
            'Organization Type',
            'Diocese / Parent Organization',
            'District',
            'City',
            'Country',
            'Primary Contact Name',
            'Contact Email',
            'Contact Phone',
            'Status',
            'Verified',
            'Departments',
            'Units',
            'Sites',
            'Active Members',
            'Created At',
        ];
    }

    public function map($organization): array
    {
        return [
            $organization->legal_name,
            $organization->code,
            $organization->category,
            $organization->organization_type,
            $organization->parentOrganization?->display_name ?? ($organization->is_super ? 'Head office' : ''),
            $organization->district,
            $organization->city,
            $organization->country,
            $organization->primary_contact_name,
            $organization->contact_email,
            $organization->contact_phone,
            $organization->is_active ? 'Active' : 'Inactive',
            $organization->is_verified ? 'Yes' : 'No',
            $organization->departments_count,
            $organization->organization_units_count,
            $organization->sites_count,
            $organization->active_members_count,
            $organization->created_at?->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'K' => NumberFormat::FORMAT_TEXT, // Contact Phone
        ];
    }
}
