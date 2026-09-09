import { Link } from "@inertiajs/react";
import { LogOut } from 'lucide-react';

export function GuestFooter() {
    return (
        <>
            <div className="main-footer">
                <div className="container">
                    <h1>Footer - Guest</h1>
                    <div className="row">
                        <div className="col-md-4">Col 1</div>
                        <div className="col-md-4">Col 2</div>
                        <div className="col-md-4">Col 3</div>
                    </div>
                </div>
            </div>
        </>
    );
}

export function ClientFooter() {
    return (
        <>
            <div className="main-footer">
                <div className="container">
                    <h1>Footer - Client</h1>
                    <div className="row">
                        <div className="col-md-4">Col 1</div>
                        <div className="col-md-4">Col 2</div>
                        <div className="col-md-4">Col 3</div>
                    </div>
                </div>
            </div>
        </>
    );
}

export function AdminFooter() {
    return (
        <>
            <div className="main-footer">
                <div className="container">
                    <h1>Footer - Admin</h1>
                    <div className="row">
                        <div className="col-md-4">Col 1</div>
                        <div className="col-md-4">Col 2</div>
                        <div className="col-md-4">Col 3</div>
                    </div>
                </div>
            </div>
        </>
    );
}
