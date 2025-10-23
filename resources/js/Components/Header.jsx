// resources/js/Components/Header.jsx

import React from 'react';
// 1. Import ikon menu
import { MdAdminPanelSettings, MdExitToApp } from 'react-icons/md';
import { FiMenu } from 'react-icons/fi';

// 2. Terima prop 'toggleSidebar' dari layout
export default function Header({ user, pageHeader, isCmsMode, toggleCmsMode, toggleSidebar }) {
    return (
        <header className="bg-white shadow-sm sticky top-0 z-20"> {/* Naikkan z-index */}
            <div className="max-w-full mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">

                {/* ================= [MODIFIKASI HP 1] ================= */}
                <div className="flex items-center">
                    {/* 3. Tombol Hamburger hanya untuk mobile */}
                    <button
                        onClick={toggleSidebar}
                        className="lg:hidden mr-4 text-gray-600 hover:text-gray-800"
                        aria-label="Toggle Menu"
                    >
                        <FiMenu size={24} />
                    </button>

                    <div className="flex-1">
                        {pageHeader}
                    </div>
                </div>
                {/* ======================================================= */}


                <div className="flex items-center space-x-4 ml-4">
                    {/* ... (kode tombol Masuk/Keluar Mode Admin tidak berubah) ... */}
                    {user.role === 'admin' && (
                        <button
                            onClick={toggleCmsMode}
                            className={`flex items-center space-x-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 ${isCmsMode
                                ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                : 'bg-blue-100 text-blue-700 hover:bg-blue-200'
                                }`}
                        >
                            {isCmsMode ? <MdExitToApp size={18} /> : <MdAdminPanelSettings size={18} />}
                            <span className="hidden sm:inline">{isCmsMode ? 'Keluar Mode Admin' : 'Masuk Mode Admin'}</span>
                        </button>
                    )}
                </div>
            </div>
        </header>
    );
}
