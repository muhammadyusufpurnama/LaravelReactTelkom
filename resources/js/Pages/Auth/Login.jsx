import React, { useEffect, useState } from 'react';
import Checkbox from '../../Components/Checkbox';
import InputError from '../../Components/InputError';
import InputLabel from '../../Components/InputLabel';
import PrimaryButton from '../../Components/PrimaryButton';
import TextInput from '../../Components/TextInput';
import GuestLayout from '../../Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

// --- Komponen untuk Statistik Jaringan (Dropdown Diperbarui) ---
const NetworkStats = () => {
    const [stats, setStats] = useState(null);
    const [error, setError] = useState(null);
    const [isOpen, setIsOpen] = useState(false);

    const toggleDropdown = () => {
        setIsOpen(!isOpen);
    };

    useEffect(() => {
        if (isOpen && !stats && !error) {
            const fetchNetworkStats = async () => {
                try {
                    const response = await fetch('/api/network-stats');
                    if (!response.ok) {
                        throw new Error('Gagal mengambil data jaringan. Pastikan endpoint API sudah siap.');
                    }
                    const data = await response.json();
                    setStats(data);
                } catch (err) {
                    console.error('Error saat mengambil statistik jaringan:', err.message);
                    setError('Tidak dapat memuat statistik jaringan.');
                }
            };
            fetchNetworkStats();
        }
    }, [isOpen, stats, error]);

    return (
        <div className="mt-8 pt-6 border-t border-gray-200 text-sm">
            <button
                type="button"
                onClick={toggleDropdown}
                className="w-full flex justify-between items-center text-left font-bold text-gray-700 focus:outline-none"
            >
                <span>Network Status</span>
                <span>{isOpen ? '▲' : '▼'}</span>
            </button>

            {isOpen && (
                <div className="mt-4 pl-2">
                    {error && <div className="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">{error}</div>}

                    {/* ===== Tampilan Statistik yang Diperbarui ===== */}
                    {stats && !error && (
                        <div className='space-y-1 text-gray-600'>
                            <p><strong>Your IP:</strong> {stats.ip || 'N/A'}</p>
                            <p><strong>Alive:</strong> {stats.alive ? 'Yes' : 'No'}</p>
                            <p><strong>Packets Transmitted:</strong> {stats.transmitted ?? 'N/A'}</p>
                            <p><strong>Received:</strong> {stats.received ?? 'N/A'}</p>
                            <p><strong>Packet Loss:</strong> {stats.loss || 'N/A'}</p>
                            <p><strong>Time:</strong> {stats.time || 'N/A'}</p>
                            <p><strong>Traceroute:</strong> {stats.traceroute || 'N/A'}</p>
                        </div>
                    )}
                    {/* ============================================== */}

                    {!stats && !error && <p className='text-center text-gray-500'>Memuat statistik jaringan...</p>}
                </div>
            )}
        </div>
    );
};


// --- Komponen Login Utama (Tetap Sama) ---
export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-gray-600">
                            Remember me
                        </span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-between">
                    <Link
                        href={route('register')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Don't have an account?
                    </Link>

                    <div className='flex items-center'>
                        {canResetPassword && (
                            <Link
                                href={route('password.request')}
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Forgot your password?
                            </Link>
                        )}

                        <PrimaryButton className="ms-4" disabled={processing}>
                            Log in
                        </PrimaryButton>
                    </div>
                </div>
            </form>

            <NetworkStats />

        </GuestLayout>
    );
}
