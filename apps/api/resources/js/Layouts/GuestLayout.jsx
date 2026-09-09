import ApplicationLogo from '@/Components/ApplicationLogo';
import { GuestHeader, ClientHeader, AdminHeader } from '@/Components/Header';
import { Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const { props } = usePage();
    const user = props.auth.user;

    return (
        <>
            {!user && <GuestHeader />}
            {user && user.type === 'client' && <ClientHeader />}
            {user && user.type === 'admin' && <AdminHeader />}

            {/* <GuestHeader></GuestHeader> */}

            <main className="flex-1 container p-4">
                {children}
            </main>
        </>
    );
}
