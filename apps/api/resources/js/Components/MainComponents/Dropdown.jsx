import React from 'react';

export default function Dropdown({
    label = '',
    name,
    value,
    onChange,
    options = [],
    className = '',
    error = '',
}) {
    return (
        <div className={`mb-4 ${className}`}>
            {label && (
                <label
                    htmlFor={name}
                    className="block text-sm font-medium text-gray-700 mb-1"
                >
                    {label}
                </label>
            )}

            <select
                name={name}
                id={name}
                value={value}
                onChange={onChange}
                className={`block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm ${error ? 'border-red-500' : ''
                    }`}
            >
                <option value="">Selecione...</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>

            {error && <p className="text-sm text-red-600 mt-1">{error}</p>}
        </div>
    );
}
