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

    const isDesktop = () => window.innerWidth >= 1024;

    const [isSidebarOpen, setIsSidebarOpen] = useState(isDesktop());
    const toggleSidebar = () => setIsSidebarOpen(prevState => !prevState);

    useEffect(() => {
        const handleResize = () => {
            // Jika layar berubah jadi desktop, buka sidebar.
            // Jika berubah jadi mobile, tutup sidebar.
            if (isDesktop()) {
                setIsSidebarOpen(true);
            } else {
                setIsSidebarOpen(false);
            }
        };

        window.addEventListener('resize', handleResize);
        // Cleanup listener saat komponen dibongkar
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    const [isCmsMode, setIsCmsMode] = useState(() => {
        return localStorage.getItem('isCmsMode') === 'true';
    });
    const prevIsCmsMode = usePrevious(isCmsMode);

    const handleLogout = () => {
        // Logika ini sekarang akan dijalankan dari komponen induk
        localStorage.setItem('isCmsMode', 'false');
        console.log('CMS mode set to false from Layout. Logging out...');
        router.post(route('logout'));
    };

    useEffect(() => {
        localStorage.setItem('isCmsMode', isCmsMode);
    }, [isCmsMode]);

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
                onLogout={handleLogout}
            />

            {/* Overlay Gelap untuk Mobile */}
            {/* Muncul saat sidebar terbuka DAN layar BUKAN desktop */}
            {isSidebarOpen && !isDesktop() && (
                <div
                    onClick={toggleSidebar}
                    className="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
                    aria-hidden="true"
                ></div>
            )}

            {/* Konten utama tidak lagi diberi margin di mobile */}
            {/* Margin hanya berlaku di layar besar (lg) */}
            <div className={`transition-all duration-300 ease-in-out ${isSidebarOpen ? 'lg:ml-64' : 'lg:ml-20'}`}>
                <Header
                    user={user}
                    pageHeader={header}
                    isCmsMode={isCmsMode}
                    toggleCmsMode={toggleCmsMode}
                    toggleSidebar={toggleSidebar} // <-- Prop ini diteruskan ke Header
                />

                <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
