<?php

namespace App\Exports;
use App\Models\Province;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProvinceParishExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths
{
    protected $province;

    public function __construct($province)
    {
        $this->province = $province;
        set_time_limit(8000000);
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G
    }
    public function collection()
    {
        $provinces = Province::where('id', $this->province)->with('parishes')->get();
        $data = [];
        // dd($provinces);
        foreach ($provinces as $province) {
            // Add a row for the province
            $data[] = [
                'province' => $province->description,
                'parish' => '',
                'registered' => '',
                'approved' => '',
                'value' => '',
            ];

            foreach ($province->parishes as $parish) {
                $parishId = $parish->id;
                $registered = getAvailableAssets()->where('parish_id', $parishId)->count() ;
                $approved = getAvailableAssets()->where('parish_id', $parishId)->where('approval_status', 2)->count();
                $value = getAvailableAssets()->where('parish_id', $parishId)->where('approval_status', 2)->sum('amount_purchased');

                $data[] = [
                    'province' => '',
                    'parish' => $parish->description,
                    'registered' => $registered,
                    'approved' => $approved,
                    'value' => $value,
                ];
            }
        }
        // dd($data);
        return collect($data);
    }

    public function headings(): array
    {
        return [
            'Province',
            'Parish',
            'Registered Assets',
            'Approved Assets',
            'Total Value of Approved Assets',
        ];
    }

    public function map($row): array
    {
       // dd($row);
        if($row['parish'] == ""){
            return [
                $row['province'],
                "",
                "",
               "",
                "",
            ];
        }else{
            // dd($row);
            return [
                $row['province'],
                $row['parish'],
                (string) $row['registered'],
                (string) $row['approved'],
                (string) $row['value'],
            ];
        }

    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 25,
            'C' => 18,
            'D' => 18,
            'E' => 28,
        ];
    }
}
