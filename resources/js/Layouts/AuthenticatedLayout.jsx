// resources/js/Layouts/AuthenticatedLayout.jsx

import React from 'react';
import Sidebar from '@/Components/Sidebar';
import Header from '@/Components/Header';
import { usePage } from '@inertiajs/react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth?.user;

    if (!user) {
        return (
            <div className="flex h-screen items-center justify-center bg-gray-100">
                <div className="text-lg font-semibold text-gray-600">
                    Authenticating...
                </div>
            </div>
        );
    }

    // Jika user ada, kita render layout lengkapnya.
    return (
        // Pembungkus utama, kita gunakan min-h-screen untuk memastikan tinggi minimal
        <div className="min-h-screen bg-gray-100 font-sans">

            {/* Sidebar akan berada di sisi kiri (menggunakan 'fixed' dari file Sidebar.jsx) */}
            <Sidebar user={user} />

            {/* Kontainer untuk konten utama di sebelah kanan sidebar */}
            {/* [PERBAIKAN] Tambahkan class 'ml-64' di sini */}
            <div className="flex flex-1 flex-col ml-64">

                {/* Header berada di atas konten utama */}
                <Header user={user} pageHeader={header} />

                {/* Konten utama halaman (children) */}
                <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
