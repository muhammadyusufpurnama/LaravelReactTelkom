// resources/js/Layouts/AuthenticatedLayout.jsx

import React, { useState, useEffect, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import Sidebar from '@/Components/Sidebar';
import Header from '@/Components/Header';
import { Toaster } from 'react-hot-toast';

function usePrevious(value) {
    const ref = useRef();
    useEffect(() => {
        ref.current = value;
    });
    return ref.current;
}

export default function AuthenticatedLayout({ header, children }) {
    const { auth, component } = usePage().props; // [FIX 1] Ambil nama komponen saat ini
    const user = auth?.user;

    const [isSidebarOpen, setIsSidebarOpen] = useState(true);
    const toggleSidebar = () => setIsSidebarOpen(prevState => !prevState);

    const [isCmsMode, setIsCmsMode] = useState(() => {
        return localStorage.getItem('isCmsMode') === 'true';
    });
    const prevIsCmsMode = usePrevious(isCmsMode);

    useEffect(() => {
        localStorage.setItem('isCmsMode', isCmsMode);
    }, [isCmsMode]);

    // [FIX 2] EFEK UNTUK SINKRONISASI SAAT LOGIN
    // Efek ini berjalan hanya sekali saat layout dimuat.
    useEffect(() => {
        // Logika: Jika halaman yang dimuat adalah dashboard utama,
        // ini kemungkinan besar terjadi setelah login.
        // Kita paksa mode CMS menjadi nonaktif untuk memastikan state awal yang bersih.
        if (component === 'DashboardDigitalProduct') {
            if (isCmsMode) {
                console.log("Login detected on dashboard, forcing User Mode.");
                setIsCmsMode(false);
            }
        }
    }, []); // <-- Dependency array kosong berarti hanya berjalan sekali saat mount

    // Efek untuk navigasi saat mode di-toggle (TETAP SAMA)
    useEffect(() => {
        if (prevIsCmsMode !== undefined && prevIsCmsMode !== isCmsMode) {
            if (isCmsMode) {
                router.visit(route('admin.analysisDigitalProduct.index'));
            } else {
                router.visit(route('dashboard'));
            }
        }
    }, [isCmsMode, prevIsCmsMode]);

    const toggleCmsMode = () => {
        setIsCmsMode(prevMode => !prevMode);
    };

    if (!user) {
        return (
            <div className="flex h-screen items-center justify-center bg-gray-100">
                <div className="text-lg font-semibold text-gray-600">
                    Authenticating...
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-100 font-sans">
            <Toaster position="top-center" reverseOrder={false} />

            <Sidebar
                user={user}
                isSidebarOpen={isSidebarOpen}
                toggleSidebar={toggleSidebar}
                isCmsMode={isCmsMode}
            />

            <div className={`transition-all duration-300 ease-in-out ${isSidebarOpen ? 'ml-64' : 'ml-20'}`}>
                <Header
                    user={user}
                    pageHeader={header}
                    isCmsMode={isCmsMode}
                    toggleCmsMode={toggleCmsMode}
                />

                <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
