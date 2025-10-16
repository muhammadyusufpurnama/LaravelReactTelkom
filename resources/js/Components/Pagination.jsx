// resources/js/Components/Pagination.jsx

import React from 'react';
import { Link } from '@inertiajs/react';

export default function Pagination({ links = [] }) {
    // Jangan tampilkan apa-apa jika hanya ada sedikit link (misal: prev, 1, next)
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap justify-center items-center mt-6 space-x-1">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url ?? '#'}
                    className={`
                        px-3 py-2 text-sm border rounded
                        transition-colors duration-200
                        ${link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-100'}
                        ${!link.url ? 'text-gray-400 cursor-not-allowed bg-gray-50' : ''}
                    `}
                    // Gunakan dangerouslySetInnerHTML untuk merender label seperti '&laquo; Previous'
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    as="button"
                    disabled={!link.url}
                    preserveScroll
                />
            ))}
        </div>
    );
}
