import ApplicationLogo from '@/Components/ApplicationLogo';
import { GuestHeader, ClientHeader, AdminHeader } from '@/Components/Header';
import { GuestFooter, ClientFooter, AdminFooter } from '@/Components/MainComponents/Footer';
import { DefaultMainContent } from '@/Components/MainComponents/MainContent';
import { Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const { props } = usePage();
    const user = props.auth.user;

    return (
        <>
            {!user && <GuestHeader />}
            {user && user.type === 'client' && <ClientHeader />}
            {user && user.type === 'admin' && <AdminHeader />}

            <DefaultMainContent children={children} />

            {!user && <GuestFooter />}
            {user && user.type === 'client' && <ClientFooter />}
            {user && user.type === 'admin' && <AdminFooter />}
        </>
    );
}
