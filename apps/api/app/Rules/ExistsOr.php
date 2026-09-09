<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ExistsOr implements Rule
{
    protected string $table;
    protected array $columns;

    public function __construct(string $table, array $columns)
    {
        $this->table = $table;
        $this->columns = $columns;
    }

    public function passes($attribute, $value): bool
    {
        return DB::table($this->table)
            ->where(function ($query) use ($value) {
                foreach ($this->columns as $col) {
                    $query->orWhere($col, $value);
                }
            })
            ->exists();
    }

    public function message(): string
    {
        return "O valor informado para :attribute não existe em {$this->table}.";
    }
}
