import { useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link } from '@inertiajs/react';

export default function Authenticated({ auth, header, children }) {
    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const user = props.auth.user;

    return (
        <>
            {!user && <GuestHeader />}
            {user && user.type === 'client' && <ClientHeader />}
            {user && user.type === 'admin' && <AdminHeader />}

            <main className="flex-1 container p-4">
                {children}
            </main>
        </>
    );
}
