<?php

namespace App\Imports;

use App\Models\Leveranciers;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VoorraadImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {

    }
}
