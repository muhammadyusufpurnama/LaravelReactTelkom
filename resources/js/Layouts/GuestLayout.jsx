import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
<<<<<<< HEAD
        // [DIUBAH] Hapus 'flex' dan 'items-center' dari sini untuk mengontrol posisi secara manual
        <div className="relative min-h-screen pt-6 sm:pt-0 overflow-hidden">
            {/* Background Image & Overlay */}
            <div className="absolute inset-0">
                <div
                    className="absolute inset-0 bg-cover bg-center filter blur-sm"
                    style={{
                        backgroundImage: "url('/images/tlt surabaya.jpg')",
                        transform: 'scale(1.1)',
                    }}
                ></div>
                <div className="absolute inset-0 bg-black opacity-30"></div>
            </div>

            {/* Content Card (Centered) */}
            {/* [DIUBAH] Gunakan flexbox di sini untuk memusatkan kartu, pastikan z-index lebih tinggi */}
            <div className="relative z-10 min-h-screen flex flex-col sm:justify-center items-center">
                <div className="w-full sm:max-w-md mt-6 px-6 py-8 bg-white/70 shadow-2xl overflow-hidden sm:rounded-lg backdrop-blur-sm border border-white/20">
                    <div className="mb-6">
                        <Logo />
                    </div>
                    {children}
                </div>
=======
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20 fill-current text-gray-500" />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {children}
>>>>>>> parent of 4b36d59 (membuat galaksi tampilan user)
            </div>
        </div>
    );
}
