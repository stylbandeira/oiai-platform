// components/Logo.tsx
import { useState } from 'react';

interface LogoProps {
    className?: string;
    src?: string;
    alt?: string;
}

export function CustomLogo({
    className = "w-8 h-8",
    src = "/logos/logo_secondary.svg",
    alt = "Oiaí"
}: LogoProps) {
    const [error, setError] = useState(false);

    if (error) {
        return (
            <div className={`${className} bg-primary rounded-full flex items-center justify-center`}>
                <span className="text-white font-bold text-sm">SS</span>
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            className={className}
            onError={() => setError(true)}
        />
    );
}