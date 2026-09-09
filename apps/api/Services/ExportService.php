<?php
// app/Services/ExportService.php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Exporta uma coleção para CSV
     */
    public function exportToCSV(
        Collection $data,
        array $columns,
        string $filename,
        string $delimiter = ';'
    ): StreamedResponse {
        $filename = $filename . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(
            function () use ($data, $columns, $delimiter) {
                $handle = fopen('php://output', 'w');

                // BOM para UTF-8
                fwrite($handle, "\xEF\xBB\xBF");

                // Cabeçalhos
                fputcsv($handle, array_keys($columns), $delimiter);

                // Dados
                foreach ($data as $item) {
                    $row = [];
                    foreach ($columns as $key => $value) {
                        $row[] = $this->getValue($item, $value);
                    }
                    fputcsv($handle, $row, $delimiter);
                }

                fclose($handle);
            },
            200,
            $headers
        );
    }

    /**
     * Obtém o valor do item baseado na configuração da coluna
     */
    private function getValue($item, $columnConfig): string
    {
        // Se for um callable, executa a função
        if (is_callable($columnConfig)) {
            return $columnConfig($item) ?? 'N/A';
        }

        // Se for uma relação (ex: 'category.name')
        if (str_contains($columnConfig, '.')) {
            return data_get($item, $columnConfig) ?? 'N/A';
        }

        // Se for um atributo simples
        return $item->{$columnConfig} ?? 'N/A';
    }

    /**
     * Exportação rápida para casos simples
     */
    public function simpleExport(
        Collection $data,
        array $columns,
        string $filename
    ): StreamedResponse {
        return $this->exportToCSV($data, $columns, $filename);
    }
}
