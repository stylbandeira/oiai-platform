import { Link } from "@inertiajs/react";
import { LogOut } from 'lucide-react';

export function DefaultMainContent({ children }) {

    return (
        <>
            <main className="flex-1 container p-4 main-content">
                {children}
            </main>
        </>
    );
}
