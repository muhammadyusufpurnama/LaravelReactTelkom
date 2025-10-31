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
    const { auth } = usePage().props;
    const user = auth?.user;

    const isDesktop = () => window.innerWidth >= 1024;
    const [isSidebarOpen, setIsSidebarOpen] = useState(isDesktop());
    const toggleSidebar = () => setIsSidebarOpen(prevState => !prevState);

    useEffect(() => {
        const handleResize = () => {
            if (isDesktop()) {
                setIsSidebarOpen(true);
            } else {
                setIsSidebarOpen(false);
            }
        };
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    const [isCmsMode, setIsCmsMode] = useState(() => {
        return localStorage.getItem('isCmsMode') === 'true';
    });
    const prevIsCmsMode = usePrevious(isCmsMode);

    useEffect(() => {
        localStorage.setItem('isCmsMode', isCmsMode);
    }, [isCmsMode]);

    useEffect(() => {
        if (prevIsCmsMode !== undefined && prevIsCmsMode !== isCmsMode) {
            if (isCmsMode) {
                router.visit(route('admin.analysisDigitalProduct.index'));
            } else {
                router.visit(route('dashboard'));
            }
        }
    }, [isCmsMode, prevIsCmsMode]);

    // ==========================================================
    // == FUNGSI LOGOUT YANG SUDAH DIPERBAIKI ==
    // ==========================================================
    const handleLogout = () => {
        // Cek jika user adalah admin dan sedang dalam CMS mode
        if (user?.role === 'admin' && isCmsMode) {
            // Aksi 1: Kirim request ke server untuk keluar dari CMS Mode.
            // Pastikan Anda punya rute bernama 'cms.exit' di routes/web.php
            router.post(route('cms.exit'), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    // Aksi 2 (jika berhasil): Hapus localStorage dan lakukan logout.
                    localStorage.setItem('isCmsMode', 'false');
                    router.post(route('logout'));
                },
                onError: (errors) => {
                    // Jika gagal, tetap paksa logout demi keamanan.
                    console.error("Gagal keluar dari CMS Mode, melanjutkan logout...", errors);
                    localStorage.setItem('isCmsMode', 'false');
                    router.post(route('logout'));
                }
            });
        } else {
            // Jika bukan admin dalam CMS mode, langsung hapus localStorage dan logout.
            localStorage.setItem('isCmsMode', 'false');
            router.post(route('logout'));
        }
    };

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
                onLogout={handleLogout} // Prop ini sekarang menjalankan logika yang benar
            />

            {isSidebarOpen && !isDesktop() && (
                <div
                    onClick={toggleSidebar}
                    className="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
                    aria-hidden="true"
                ></div>
            )}

            <div className={`transition-all duration-300 ease-in-out ${isSidebarOpen ? 'lg:ml-64' : 'lg:ml-20'}`}>
                <Header
                    user={user}
                    pageHeader={header}
                    isCmsMode={isCmsMode}
                    toggleCmsMode={toggleCmsMode}
                    toggleSidebar={toggleSidebar}
                />

                <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
