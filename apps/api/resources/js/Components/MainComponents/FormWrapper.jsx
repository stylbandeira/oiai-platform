import { useForm } from '@inertiajs/react';

export default function FormWrapper({ model = {}, fields, onSubmit }) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        ...model,
    });

    const getNestedValue = (obj, path) => {
        const result = path.split('.').reduce((acc, part) => acc?.[part], obj);
        return typeof result === 'object' ? '' : result ?? '';
    };

    const handleChange = (e) => {
        const { name, value } = e.target;

        if (name.includes('.')) {
            const keys = name.split('.');
            setData((prevData) => {
                const updated = { ...prevData };
                let current = updated;

                for (let i = 0; i < keys.length - 1; i++) {
                    if (!current[keys[i]] || typeof current[keys[i]] !== 'object') {
                        current[keys[i]] = {};
                    }
                    current = current[keys[i]];
                }

                current[keys[keys.length - 1]] = value;
                return updated;
            });
        } else {
            setData(name, value);
        }
    };

    const handleChangeFile = (e, name) => {
        const file = e.target.files[0];

        if (name.includes('.')) {
            const keys = name.split('.');
            setData((prevData) => {
                const updated = { ...prevData };
                let current = updated;

                for (let i = 0; i < keys.length - 1; i++) {
                    if (!current[keys[i]]) current[keys[i]] = {};
                    current = current[keys[i]];
                }

                current[keys[keys.length - 1]] = file;
                return updated;
            });
        } else {
            setData(name, file);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        onSubmit({ data, post, put });
    };

    return (
        <div className="bg-background p-4 rounded-lg">
            <form onSubmit={submit}>
                {fields.map((field) => (
                    <div key={field.name} className="mb-4">
                        <label htmlFor={field.name} className="block font-semibold mb-1">
                            {field.label}
                        </label>
                        {field.type === 'image' ? (
                            <input
                                id={field.name}
                                name={field.name}
                                type="file"
                                accept="image/*"
                                onChange={(e) => handleChangeFile(e, field.name)}
                                className="border rounded w-full p-2"
                            />
                        ) : (
                            < input
                                id={field.name}
                                name={field.name}
                                type={field.type || 'text'}
                                value={getNestedValue(data, field.name)}
                                onChange={handleChange}
                                className="border rounded w-full p-2"
                            />
                        )}
                        {errors[field.name] && (
                            <p className="text-red-500 text-sm mt-1">{errors[field.name]}</p>
                        )}
                    </div>
                ))}

                <button
                    type="submit"
                    disabled={processing}
                    className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                >
                    Salvar
                </button>
            </form>
        </div>
    );
}
