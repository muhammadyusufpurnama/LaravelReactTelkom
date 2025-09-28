// resources/js/Components/Header.jsx

import React from 'react';
import { FiSearch, FiBell } from 'react-icons/fi';

// 1. Terima prop 'pageHeader', bukan 'title'
export default function Header({ pageHeader }) {
    return (
        <header className="bg-white shadow-sm">
            <div className="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">

                {/* 2. Render 'pageHeader' di dalam div agar bisa berdampingan dengan ikon */}
                <div className="flex-1">
                    {pageHeader}
                </div>

                {/* Ikon-ikon ini tetap berada di sebelah kanan */}
                <div className="flex items-center space-x-4 ml-4">
                    <FiSearch className="text-gray-500 hover:text-gray-800 cursor-pointer" size={22} />
                    <FiBell className="text-gray-500 hover:text-gray-800 cursor-pointer" size={22} />
                </div>
            </div>
        </header>
    );
}
