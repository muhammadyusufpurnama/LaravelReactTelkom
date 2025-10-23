import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

export default function MergeExcel({ auth, lastMergeResult }) {
    const { props } = usePage();
    const { flash, errors } = props;

    const { data, setData, post, processing, reset } = useForm({
        files: null,
    });

    // Prioritaskan data baru dari flash message.
    // Jika tidak ada, gunakan data dari session (lastMergeResult).
    const displayedMergeResult = flash.mergeResult || lastMergeResult;

    const selectedFiles = data.files ? Array.from(data.files) : [];

    const handleFileChange = (e) => {
        const files = e.target.files;

        if (files && files.length > 0) {
            if (files.length > 20) {
                alert(`Anda memilih ${files.length} file. Maksimal 20 file yang diperbolehkan.`);
                e.target.value = null;
                setData('files', null);
                return;
            }
        }

        setData('files', files);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!data.files || data.files.length === 0) {
            alert('Pilih setidaknya satu file untuk digabungkan.');
            return;
        }

        post(route('admin.merge-excel.merge'), {
            onSuccess: () => {
                reset('files');
                const fileInput = document.getElementById('files-input');
                if (fileInput) fileInput.value = null;
            },
            preserveScroll: true,
        });
    };

    const handleDownload = () => {
        if (displayedMergeResult && displayedMergeResult.download_url) {
            window.location.href = displayedMergeResult.download_url;
        } else {
            alert("Gagal memulai unduhan: URL download tidak ditemukan.");
        }
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Merge Excel Files</h2>}
        >
            <Head title="Merge Excel Files" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <p className="mb-4 text-gray-600">
                                Unggah beberapa file Excel (.xlsx, .xls, .csv) untuk digabungkan. Header akan diambil dari file pertama.
                            </p>

                            {flash.success && (
                                <div className="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                                    {flash.success}
                                </div>
                            )}
                            {flash.error && (
                                <div className="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                    {flash.error}
                                </div>
                            )}
                            {errors.files && (<p className="mt-1 text-sm text-red-600">{errors.files}</p>)}
                            {Object.keys(errors).filter(key => key.startsWith('files.')).map(key => (
                                <p key={key} className="mt-1 text-sm text-red-600">{errors[key]}</p>
                            ))}


                            {displayedMergeResult && (
                                <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <h3 className="text-lg font-semibold text-blue-800">File Siap Diunduh!</h3>
                                            <p className="text-blue-600 mt-1">File: {displayedMergeResult.file_name}</p>
                                        </div>
                                        <button onClick={handleDownload} className="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg">
                                            Download File
                                        </button>
                                    </div>
                                </div>
                            )}

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div>
                                    <label htmlFor="files-input" className="block text-sm font-medium text-gray-700 mb-2">Pilih File</label>
                                    <input
                                        id="files-input"
                                        type="file"
                                        multiple
                                        accept=".xlsx,.xls,.csv"
                                        onChange={handleFileChange}
                                        className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        disabled={processing}
                                    />
                                    <p className="mt-1 text-xs text-gray-500">
                                        Format yang didukung: .xlsx, .xls, .csv (Maksimal 20 file, 20MB per file)
                                    </p>
                                </div>

                                {selectedFiles.length > 0 && (
                                    <div className="border p-4 bg-gray-50 rounded-lg">
                                        <h3 className="text-sm font-medium mb-3">File yang dipilih ({selectedFiles.length}):</h3>
                                        <ul className="space-y-2 max-h-48 overflow-y-auto">
                                            {selectedFiles.map((file, index) => (
                                                <li key={index} className="flex justify-between items-center text-sm">
                                                    <span className="font-medium truncate pr-4">• {file.name}</span>
                                                    <span className="text-gray-500">{formatFileSize(file.size)}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                <button
                                    type="submit"
                                    disabled={processing || selectedFiles.length === 0}
                                    className={`px-6 py-3 rounded-md font-medium text-white transition-colors ${processing || selectedFiles.length === 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'}`}
                                >
                                    {processing ? 'Memproses...' : 'Gabungkan & Mulai Proses'}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

//for CI
