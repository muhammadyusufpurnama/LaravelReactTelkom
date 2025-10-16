// resources/js/Components/DropdownCheckbox.jsx

import React, { useState, useRef, useEffect } from 'react';

export default function DropdownCheckbox({ title, options, selectedOptions, onSelectionChange }) {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);

    // Menutup dropdown saat klik di luar komponen
    useEffect(() => {
        function handleClickOutside(event) {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        }
        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, [dropdownRef]);

    const handleCheckboxChange = (option) => {
        const newSelection = selectedOptions.includes(option)
            ? selectedOptions.filter(item => item !== option)
            : [...selectedOptions, option];
        onSelectionChange(newSelection);
    };

    const selectAll = () => onSelectionChange(options);
    const deselectAll = () => onSelectionChange([]);

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Konten lainnya (button, div dropdown) tidak berubah sama sekali */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-between items-center"
            >
                <span>{`${title} (${selectedOptions.length}/${options.length})`}</span>
                <svg className={`w-5 h-5 ml-2 -mr-1 transition-transform duration-200 ${isOpen ? 'transform rotate-180' : ''}`} fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
            </button>

            {isOpen && (
                <div className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg">
                    <div className="p-2 flex justify-between border-b">
                        <button onClick={selectAll} className="text-xs text-blue-600 hover:underline">Pilih Semua</button>
                        <button onClick={deselectAll} className="text-xs text-blue-600 hover:underline">Kosongkan</button>
                    </div>
                    <div className="p-2 max-h-48 overflow-y-auto">
                        {options.map(option => (
                            <label key={option} className="flex items-center w-full px-2 py-1 space-x-2 text-sm text-gray-700 rounded hover:bg-gray-100">
                                <input
                                    type="checkbox"
                                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    checked={selectedOptions.includes(option)}
                                    onChange={() => handleCheckboxChange(option)}
                                />
                                <span>{option}</span>
                            </label>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
