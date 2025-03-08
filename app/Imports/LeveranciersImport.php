<?php

namespace App\Imports;

use App\Models\Leveranciers;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeveranciersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return Leveranciers::updateOrCreate([
            'naam' => $row['leveranciersnaam'],
        ], [
            'telefoon' => $row['telefoonnummer_tbv_order'] ?? null,
            'email' => $row['e_mail_adres_voor_orders'] ?? null,
            'franco' => str_replace(',', '.', $row['franco_orderbedrag']) ?? null,
            'adres_straat' => $row['goederenadres'] ?? null,
            'adres_postcode' => $row['goederenadres_postcode'] ?? null,
            'adres_plaatsnaam' => $row['goederenadres_plaatsnaam'] ?? null,
            'adres_land' => $row['goederenadres_land'] ?? null,
        ]);
    }
}
