import { Link } from "@inertiajs/react";
import { LogOut } from 'lucide-react';
import ApplicationLogo from "./ApplicationLogo";

export function GuestHeader() {
    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <nav className="navbar navbar-expand-lg bg-body-tertiary">
            <div className="container d-flex justify-content-between align-items-center">
                <div>
                    <ApplicationLogo />
                </div>
                <div>
                    <div className="navbar-collapse" id="navbarNav">
                        <ul className="navbar-nav font-primary">
                            <li className="nav-item">
                                <a className="nav-link" href="/login">Login</a>
                            </li>

                            <li className="nav-item">
                                <a className="nav-link" href="/register">Register</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    );
}

export function ClientHeader() {
    return (
        <nav className="navbar navbar-expand-lg bg-body-tertiary">
            <div className="container d-flex justify-content-between align-items-center">
                <div>
                    <ApplicationLogo />
                </div>
                <div>
                    <div className="navbar-collapse" id="navbarNav">
                        <ul className="navbar-nav font-primary">

                            <li className="nav-item">
                                <a className="nav-link" href="/listas">Perfil</a>
                            </li>

                            <li className="nav-item">
                                <a className="nav-link" href="/listas">Listas</a>
                            </li>

                            <li className="nav-item">
                                <a className="nav-link" href="/logout">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    );
}

export function AdminHeader() {
    return (
        <nav className="navbar navbar-expand-lg bg-body-tertiary">
            <div className="container d-flex justify-content-between align-items-center">
                <div>
                    <ApplicationLogo />
                </div>
                <div>
                    <div className="navbar-collapse" id="navbarNav">
                        <ul className="navbar-nav font-primary">
                            <li className="nav-item">
                                <a className="nav-link" href="/empresas">Empresas</a>
                            </li>

                            <li className="nav-item">
                                <a className="nav-link" href="/produtos">Produtos</a>
                            </li>

                            <li className="nav-item">
                                <a className="nav-link" href="/listas">Listas</a>
                            </li>

                            <li className="nav-item">
                                <a className="nav-link" href="/logout">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    );
}
