// components/forms/FormFields.tsx
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ReactNode } from "react";

interface FormInputProps {
    label: string;
    name: string;
    value?: string;
    checked?: boolean;
    onChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    placeholder?: string;
    type?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    icon?: ReactNode;
}

export function FormInput({
    label,
    name,
    value,
    checked,
    onChange,
    placeholder,
    type = "text",
    required = false,
    disabled = false,
    error,
    icon
}: FormInputProps) {

    if (type === "checkbox") {
        return (
            <div className="space-y-2 mt-5">
                <div className="flex items-center gap-2">
                    <Input
                        id={name}
                        name={name}
                        type="checkbox"
                        checked={checked}
                        onChange={onChange}
                        disabled={disabled}
                        className="accent-green-600"
                    />
                    <Label htmlFor={name}>
                        {label}
                        {required && " *"}
                    </Label>
                </div>

                {error && <p className="text-red-500 text-sm">{error}</p>}
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <Label htmlFor={name}>{label}{required && " *"}</Label>
            <div className="relative">
                {icon && (
                    <div className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">
                        {icon}
                    </div>
                )}
                <Input
                    id={name}
                    name={name}
                    type={type}
                    value={type !== "checkbox" ? value : undefined}
                    checked={type === "checkbox" ? checked : undefined}
                    onChange={onChange}
                    placeholder={placeholder}
                    className={icon ? "pl-10" : ""}
                    required={required}
                    disabled={disabled}
                />
            </div>
            {error && <p className="text-red-500 text-sm">{error}</p>}
        </div>
    );
}

interface FormTextareaProps {
    label: string;
    name: string;
    value: string;
    onChange: (e: React.ChangeEvent<HTMLTextAreaElement>) => void;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    rows?: number;
    icon?: ReactNode;
}

export function FormTextarea({
    label,
    name,
    value,
    onChange,
    placeholder,
    required = false,
    disabled = false,
    error,
    rows = 4,
    icon
}: FormTextareaProps) {
    return (
        <div className="space-y-2">
            <Label htmlFor={name}>{label}{required && " *"}</Label>
            <div className="relative">
                {icon && (
                    <div className="absolute left-3 top-3 text-muted-foreground">
                        {icon}
                    </div>
                )}
                <Textarea
                    id={name}
                    name={name}
                    value={value}
                    onChange={onChange}
                    placeholder={placeholder}
                    className={icon ? "pl-10" : ""}
                    required={required}
                    disabled={disabled}
                    rows={rows}
                />
            </div>
            {error && <p className="text-red-500 text-sm">{error}</p>}
        </div>
    );
}

interface FormSelectProps {
    label: string;
    name: string;
    value: string;
    onValueChange: (value: string) => void;
    options: { value: string; label: string }[];
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
}

export function FormSelect({
    label,
    name,
    value,
    onValueChange,
    options,
    placeholder = "Selecione uma opção",
    required = false,
    disabled = false,
    error
}: FormSelectProps) {
    return (
        <div className="space-y-2">
            <Label htmlFor={name}>{label}{required && " *"}</Label>
            <Select
                value={value}
                onValueChange={onValueChange}
                disabled={disabled}
            >
                <SelectTrigger>
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {error && <p className="text-red-500 text-sm">{error}</p>}
        </div>
    );
}