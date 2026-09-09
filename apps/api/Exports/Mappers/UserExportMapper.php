<?php

namespace App\Exports\Mappers;

class UserExportMapper
{
    public function columns(): array
    {
        return [
            'ID' => 'id',
            'Nome' => 'name',
            'Email' => 'email',
            'CPF' => 'cpf',
            'Tipo' => fn($user) => match ($user->type) {
                'client' => 'Cliente',
                'company' => 'Empresa',
                'admin' => 'Administrador',
                default => $user->type,
            },
            'Pontos' => 'points',
            'Reputação' => 'reputation',
            'Status' => fn($user) => match ($user->status) {
                'active' => 'Ativo',
                'inactive' => 'Inativo',
                'suspended' => 'Suspenso',
                default => $user->status,
            },
            'Data de Criação' => fn($user) => $user->created_at?->format('d/m/Y H:i:s'),
            'Data de Exclusão' => fn($user) => $user->deleted_at?->format('d/m/Y H:i:s') ?? '-',
        ];
    }
}
