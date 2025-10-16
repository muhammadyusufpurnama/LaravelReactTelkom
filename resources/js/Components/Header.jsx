// resources/js/Components/Header.jsx

import React from 'react';
import { MdAdminPanelSettings, MdExitToApp } from 'react-icons/md';

// ================= [MODIFIKASI 1] =================
// Terima prop 'toggleCmsMode' dari layout
export default function Header({ user, pageHeader, isCmsMode, toggleCmsMode }) {
    return (
        <header className="bg-white shadow-sm sticky top-0 z-10">
            <div className="max-w-full mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <div className="flex-1">
                    {pageHeader}
                </div>

                {/* ================= [MODIFIKASI 2] ================= */}
                {/* Ganti <Link> dengan <button> untuk mengubah state, bukan pindah halaman */}
                <div className="flex items-center space-x-4 ml-4">
                    {user.role === 'admin' && (
                        <button
                            onClick={toggleCmsMode} // Panggil fungsi toggle saat diklik
                            className={`flex items-center space-x-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ${isCmsMode
                                ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                : 'bg-blue-100 text-blue-700 hover:bg-blue-200'
                                }`}
                        >
                            {isCmsMode ? <MdExitToApp size={18} /> : <MdAdminPanelSettings size={18} />}
                            <span>{isCmsMode ? 'Keluar Mode Admin' : 'Masuk Mode Admin'}</span>
                        </button>
                    )}
                </div>
                {/* ==================================================== */}

            </div>
        </header>
    );
}
