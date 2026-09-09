import { useState } from 'react';
import { Link, router } from '@inertiajs/react';

export default function DataTable({ columns, data, baseRoute, perPageOptions = [10, 25, 50] }) {
    const [selected, setSelected] = useState([]);
    const [itemsPerPage, setItemsPerPage] = useState(perPageOptions[0]);

    const toggleSelect = (id) => {
        setSelected((prev) =>
            prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
        );
    };

    const handleBulkToggleStatus = () => {
        if (selected.length === 0) return;
        router.post(`${baseRoute}/bulk-toggle`, { ids: selected });
    };

    const toggleStatus = (id) => {
        router.post(`${baseRoute}/${id}/toggle-status`);
    };

    return (
        <div className="space-y-4">
            <div className="overflow-x-auto border rounded-md">
                <table className="min-w-full text-sm text-left">
                    <thead className="bg-gray-100">
                        <tr>
                            <th className="px-4 py-2">
                                <input
                                    type="checkbox"
                                    checked={selected.length === data.length}
                                    onChange={() => {
                                        setSelected(
                                            selected.length === data.length
                                                ? []
                                                : data.map((item) => item.id)
                                        );
                                    }}
                                />
                            </th>
                            {columns.map((col) => (
                                <th key={col.key} className="px-4 py-2 font-semibold">
                                    {col.label}
                                </th>
                            ))}
                            <th className="px-4 py-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.map((item) => (
                            <tr
                                key={item.id}
                                className={
                                    item.deleted_at ? 'bg-gray-100 text-gray-400' : 'hover:bg-gray-50'
                                }
                            >
                                <td className="px-4 py-2">
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(item.id)}
                                        onChange={() => toggleSelect(item.id)}
                                    />
                                </td>
                                {columns.map((col) => (
                                    <td key={col.key} className="px-4 py-2">
                                        {item[col.key]}
                                    </td>
                                ))}
                                <td className="px-4 py-2 text-right space-x-2">
                                    <Link
                                        href={`${baseRoute}/${item.id}/edit`}
                                        className="text-blue-600 hover:underline"
                                    >
                                        Editar
                                    </Link>
                                    <button
                                        onClick={() => toggleStatus(item.id)}
                                        className="text-red-600 hover:underline"
                                    >
                                        {item.deleted_at ? 'Habilitar' : 'Desabilitar'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex justify-between items-center">
                <div>
                    <select
                        value={itemsPerPage}
                        onChange={(e) => setItemsPerPage(Number(e.target.value))}
                        className="border rounded px-2 py-1"
                    >
                        {perPageOptions.map((option) => (
                            <option key={option} value={option}>
                                {option} por página
                            </option>
                        ))}
                    </select>
                </div>

                <button
                    onClick={handleBulkToggleStatus}
                    className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                >
                    Habilitar/Desabilitar Selecionados
                </button>
            </div>

            {/* AQUI VOCÊ PODE ADICIONAR SUA LÓGICA DE PAGINAÇÃO */}
            {/* Substitua abaixo conforme necessário com dados reais */}
            <div className="text-center mt-4">
                {/* Exemplo: <Pagination links={paginationLinks} /> */}
            </div>
        </div>
    );
}
