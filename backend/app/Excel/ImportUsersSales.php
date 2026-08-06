<?php

namespace App\Excel;

use App\Models\UserSale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportUsersSales implements WithHeadingRow, ToCollection
{

    public function collection(Collection $collection)
    {
        $filtered = $collection->filter(fn($row) => $row->filter()->isNotEmpty());
        foreach ($filtered as $key => $row) {
            $row = $row->toArray();
            $validation = Validator::make($row, $this->rules());
            if ($validation->fails()) {
                $key = $key + 2;
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "row_$key" => $validation->errors()->all(),
                ]);
            }
            UserSale::query()->create([
                'oid' => $row['oid'],
                'sale_date' => Carbon::make($row['year'] . '-' . $row['month'])->format('Y-m'),
                'total_sale' => $row['total'],
                'product_name' => isset($row['product_name']) ? $row['product_name'] : null,
            ]);
        }
        DB::commit();
    }

    private function rules(): array
    {
        return [
            'oid' => 'required|exists:users,oid',
            'year' => 'required|integer',
            'month' => 'required|integer',
            'total' => 'required|numeric'
        ];
    }
}
