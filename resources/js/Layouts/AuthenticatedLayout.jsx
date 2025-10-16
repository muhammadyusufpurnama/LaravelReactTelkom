// resources/js/Layouts/AuthenticatedLayout.jsx

// [MODIFIKASI 1] Impor 'useEffect' dan 'useRef'
import React, { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import Sidebar from '@/Components/Sidebar';
import Header from '@/Components/Header';
import { usePage } from '@inertiajs/react';
import { Toaster } from 'react-hot-toast';

// [MODIFIKASI 2] Tambahkan helper hook ini di dalam file yang sama atau impor dari file terpisah.
// Hook ini berguna untuk mendapatkan nilai state dari render sebelumnya.
function usePrevious(value) {
    const ref = useRef();
    useEffect(() => {
        ref.current = value;
    });
    return ref.current;
}

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth?.user;

    const [isSidebarOpen, setIsSidebarOpen] = useState(true);
    const toggleSidebar = () => setIsSidebarOpen(prevState => !prevState);

    const [isCmsMode, setIsCmsMode] = useState(() => {
        return localStorage.getItem('isCmsMode') === 'true';
    });
    const prevIsCmsMode = usePrevious(isCmsMode);

    // Efek ini akan menyimpan state ke localStorage setiap kali berubah
    useEffect(() => {
        localStorage.setItem('isCmsMode', isCmsMode);
    }, [isCmsMode]);

    // [MODIFIKASI 3] Logika navigasi sekarang dipindah ke useEffect.
    // Efek ini akan mengawasi perubahan 'isCmsMode' dan mengarahkan pengguna.
    useEffect(() => {
        // Cek jika state benar-benar berubah (bukan render awal)
        if (prevIsCmsMode !== undefined && prevIsCmsMode !== isCmsMode) {
            if (isCmsMode) {
                // Transisi dari mode User -> Admin
                router.visit(route('admin.analysisDigitalProduct.index'));
            } else {
                // Transisi dari mode Admin -> User
                router.visit(route('dashboard'));
            }
        }
    }, [isCmsMode, prevIsCmsMode]); // Jalankan efek saat isCmsMode berubah


    // [MODIFIKASI 4] Fungsi toggle sekarang menjadi sangat sederhana.
    // Tugasnya HANYA mengubah state.
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
                    toggleCmsMode={toggleCmsMode} // Prop ini tetap dikirim ke Header
                />

                <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
