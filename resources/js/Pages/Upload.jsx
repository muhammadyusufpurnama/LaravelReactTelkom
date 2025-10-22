// File: resources/js/Pages/Upload.jsx

import GuestLayout from '@/Layouts/GuestLayout'; // Menggunakan GuestLayout agar ada styling dasar
import GoogleDriveUploader from '@/Components/GoogleDriveUploader';
import { Head } from '@inertiajs/react';

export default function Upload() { // <-- Tidak lagi menerima 'auth'
    return (
        // Menggunakan GuestLayout atau div sederhana
        <GuestLayout>
            <Head title="Cek Konektivitas Google" />

            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6 text-gray-900">
                    <GoogleDriveUploader />
                </div>
            </div>
        </GuestLayout>
    );
}
