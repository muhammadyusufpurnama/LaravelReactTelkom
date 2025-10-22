import React, { useState, useEffect, useRef } from 'react';

// === KONFIGURASI KUNCI ANDA DI SINI ===
const API_KEY = "AIzaSyAkmzM7_1RaYINqh2teeh7B9hjXvePAR1I"; // Ganti dengan API Key Anda
const CLIENT_ID = "925269201183-0k8j9rsb3aoh9693bl4k34bksu7fjdul.apps.googleusercontent.com"; // Ganti dengan Client ID Anda
const SCOPES = "https://www.googleapis.com/auth/drive.file";

function GoogleDriveUploader() {
    // --- KUSTOMISASI HOST MELALUI URL ---
    const urlParams = new URLSearchParams(window.location.search);
    const customHost = urlParams.get('host');
    const DEFAULT_HOST = "www.googleapis.com";
    const TARGET_HOST = customHost || DEFAULT_HOST; // Variabel ini tetap digunakan untuk tes konektivitas
    // ------------------------------------

    const [gapi, setGapi] = useState(null);
    const [google, setGoogle] = useState(null);
    const [tokenClient, setTokenClient] = useState(null);
    const [isSignedIn, setIsSignedIn] = useState(false);
    const [selectedFile, setSelectedFile] = useState(null);
    const [status, setStatus] = useState("Silakan login untuk memulai.");
    const [isConnectivityRunning, setIsConnectivityRunning] = useState(false);
    const [isUploadRunning, setIsUploadRunning] = useState(false);
    const [connectivityResults, setConnectivityResults] = useState(null);
    const [uploadResults, setUploadResults] = useState(null);
    const [error, setError] = useState(null);
    const fileInputRef = useRef(null);

    // Efek untuk memuat script Google API
    useEffect(() => {
        const scriptGapi = document.createElement('script');
        scriptGapi.src = 'https://apis.google.com/js/api.js';
        scriptGapi.async = true;
        scriptGapi.defer = true;
        scriptGapi.onload = () => {
            window.gapi.load('client', async () => {
                await window.gapi.client.init({
                    apiKey: API_KEY,
                    discoveryDocs: ['https://www.googleapis.com/discovery/v1/apis/drive/v3/rest'],
                });
                setGapi(window.gapi);
            });
        };
        document.body.appendChild(scriptGapi);

        const scriptGsi = document.createElement('script');
        scriptGsi.src = 'https://accounts.google.com/gsi/client';
        scriptGsi.async = true;
        scriptGsi.defer = true;
        scriptGsi.onload = () => {
            const client = window.google.accounts.oauth2.initTokenClient({
                client_id: CLIENT_ID,
                scope: SCOPES,
                callback: (tokenResponse) => {
                    if (tokenResponse && tokenResponse.access_token) {
                        setIsSignedIn(true);
                        setStatus("Login berhasil. Anda sekarang bisa memulai tes.");
                    }
                },
            });
            setTokenClient(client);
            setGoogle(window.google);
        };
        document.body.appendChild(scriptGsi);
    }, []);

    const handleAuthClick = () => {
        if (tokenClient) {
            tokenClient.requestAccessToken({ prompt: 'consent' });
        }
    };

    const handleSignoutClick = () => {
        const token = gapi.client.getToken();
        if (token !== null && google) {
            google.accounts.oauth2.revoke(token.access_token, () => {
                gapi.client.setToken('');
                setIsSignedIn(false);
                setSelectedFile(null);
                setConnectivityResults(null);
                setUploadResults(null);
                setError(null);
                setStatus("Anda sudah logout.");
            });
        }
    };

    const handleFileChange = (event) => {
        if (event.target.files.length > 0) {
            setSelectedFile(event.target.files[0]);
            setStatus(`File dipilih: ${event.target.files[0].name}. Siap untuk tes upload.`);
        }
    };

    // Fungsi tes latensi yang bisa dipakai ulang
    async function performPingTest(attempts = 5) {
        const latencies = [];
        for (let i = 0; i < attempts; i++) {
            const startTime = performance.now();
            try {
                await gapi.client.drive.about.get({ fields: "user" });
                const endTime = performance.now();
                latencies.push(endTime - startTime);
                setStatus(`Melakukan tes latensi (${i + 1}/${attempts})...`);
            } catch (err) {
                console.error(`Ping attempt ${i + 1} failed:`, err);
            }
            if (i < attempts - 1) await new Promise((resolve) => setTimeout(resolve, 500));
        }
        if (latencies.length === 0) return null;
        const lossPercentage = ((attempts - latencies.length) / attempts) * 100;
        return {
            min: Math.min(...latencies), max: Math.max(...latencies),
            avg: latencies.reduce((a, b) => a + b) / latencies.length,
            transmitted: attempts, received: latencies.length,
            loss: `${lossPercentage.toFixed(2)}%`,
        };
    }

    // --- FUNGSI UNTUK TES KONEKTIVITAS ---
    // (Menggunakan TARGET_HOST yang bisa dikustomisasi)
    const runConnectivityTest = async () => {
        setIsConnectivityRunning(true);
        setError(null);
        setConnectivityResults(null);
        setUploadResults(null);
        setStatus("Memulai tes konektivitas...");

        try {
            const ipResponse = await fetch("https://api.ipify.org?format=json");
            const ipData = await ipResponse.json();

            const pingResults = await performPingTest(5);
            if (!pingResults) throw new Error("Tes latensi gagal. Pastikan Anda login dan terhubung ke internet.");

            setConnectivityResults({
                targetHost: TARGET_HOST,
                ip: ipData.ip,
                transmitted: pingResults.transmitted,
                received: pingResults.received,
                loss: pingResults.loss,
                minTime: `${pingResults.min.toFixed(0)} ms`,
                maxTime: `${pingResults.max.toFixed(0)} ms`,
                avgTime: `${pingResults.avg.toFixed(0)} ms`,
            });
            setStatus("Tes konektivitas selesai.");
        } catch (err) {
            setError(err.message);
            setStatus("Tes konektivitas gagal.");
        } finally {
            setIsConnectivityRunning(false);
        }
    };

    // --- FUNGSI UNTUK TES UPLOAD ---
    // (Selalu menggunakan DEFAULT_HOST, mengabaikan kustomisasi)
    const runUploadTest = async () => {
        if (!selectedFile) {
            setError("Silakan pilih file terlebih dahulu untuk tes upload.");
            return;
        }

        setIsUploadRunning(true);
        setError(null);
        setUploadResults(null);
        setConnectivityResults(null);
        setStatus(`Mengunggah "${selectedFile.name}"...`);

        try {
            const metadata = { name: selectedFile.name, mimeType: selectedFile.type, parents: ['root'] };
            const form = new FormData();
            form.append('metadata', new Blob([JSON.stringify(metadata)], { type: 'application/json' }));
            form.append('file', selectedFile);

            const uploadStartTime = performance.now();

            // --- PERUBAHAN DI SINI ---
            // Secara eksplisit menggunakan DEFAULT_HOST untuk memastikan selalu ke Google API
            const uploadResponse = await fetch(`https://${DEFAULT_HOST}/upload/drive/v3/files?uploadType=multipart`, {
                method: 'POST',
                headers: new Headers({ 'Authorization': `Bearer ${gapi.client.getToken().access_token}` }),
                body: form,
            });
            // --- AKHIR PERUBAHAN ---

            if (!uploadResponse.ok) throw new Error(`Upload gagal, status: ${uploadResponse.status}`);
            const uploadEndTime = performance.now();
            const durationMs = uploadEndTime - uploadStartTime;
            const speedMbps = ((selectedFile.size * 8) / (durationMs / 1000) / 1000000).toFixed(2);

            setUploadResults({
                fileName: selectedFile.name,
                fileSize: `${(selectedFile.size / 1024 / 1024).toFixed(2)} MB`,
                speedMbps: `${speedMbps} Mbps`,
                duration: `${(durationMs / 1000).toFixed(2)} detik`,
            });
            setStatus("Tes upload selesai.");
        } catch (err) {
            setError(err.message);
            setStatus("Tes upload gagal.");
        } finally {
            setIsUploadRunning(false);
        }
    };

    const buttonClasses = "inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50";

    return (
        <>
            <h2 className="text-lg font-medium text-gray-900 border-b pb-3 mb-4">
                Alat Tes Jaringan ke Google Drive
            </h2>

            {!isSignedIn ? (
                <div className="text-center">
                    <button className={buttonClasses} onClick={handleAuthClick} disabled={!tokenClient || !gapi}>
                        Login dengan Google
                    </button>
                </div>
            ) : (
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-4">
                        <button
                            className={`${buttonClasses} bg-green-600 hover:bg-green-500`}
                            onClick={runConnectivityTest}
                            disabled={isConnectivityRunning || isUploadRunning}
                        >
                            {isConnectivityRunning ? "Menguji..." : "Mulai Tes Konektivitas"}
                        </button>
                        <div className="flex items-center gap-4">
                            <button
                                className={`${buttonClasses} bg-gray-600 hover:bg-gray-500`}
                                onClick={() => fileInputRef.current.click()}
                                disabled={isConnectivityRunning || isUploadRunning}
                            >
                                Pilih File...
                            </button>
                            <button
                                className={`${buttonClasses} bg-blue-600 hover:bg-blue-500`}
                                onClick={runUploadTest}
                                disabled={isUploadRunning || isConnectivityRunning || !selectedFile}
                            >
                                {isUploadRunning ? "Mengunggah..." : "Mulai Tes Upload"}
                            </button>
                        </div>
                        <button className={`${buttonClasses} bg-red-600 hover:bg-red-500 ml-auto`} onClick={handleSignoutClick}>
                            Logout
                        </button>
                    </div>
                    {selectedFile && (
                        <div className="text-sm text-gray-600">
                            File terpilih: <span className="font-semibold">{selectedFile.name}</span> ({(selectedFile.size / 1024 / 1024).toFixed(2)} MB)
                        </div>
                    )}
                </div>
            )}

            <div id="result" className="mt-6 p-4 bg-gray-100 rounded-lg shadow-inner min-h-[100px]">
                <p className="font-semibold text-gray-800">
                    Status: <span className="font-normal text-gray-600">{status}</span>
                </p>

                {error && <p className="mt-2 text-red-600 font-bold">Error: {error}</p>}

                {connectivityResults && (
                    <div className="mt-4 pt-4 border-t border-gray-200">
                        <h3 className="text-md font-semibold text-gray-800 mb-2">Hasil Tes Konektivitas</h3>
                        <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                            <p><strong>Host Target:</strong> {connectivityResults.targetHost}</p>
                            <p><strong>Rata-rata Waktu:</strong> {connectivityResults.avgTime}</p>
                            <p><strong>IP Anda:</strong> {connectivityResults.ip}</p>
                            <p><strong>Waktu Min:</strong> {connectivityResults.minTime}</p>
                            <p><strong>Paket Terkirim:</strong> {connectivityResults.transmitted}</p>
                            <p><strong>Waktu Max:</strong> {connectivityResults.maxTime}</p>
                            <p><strong>Paket Diterima:</strong> {connectivityResults.received}</p>
                            <p><strong>Packet Loss:</strong> {connectivityResults.loss}</p>
                        </div>
                    </div>
                )}

                {uploadResults && (
                    <div className="mt-4 pt-4 border-t border-gray-200">
                        <h3 className="text-md font-semibold text-gray-800 mb-2">Hasil Tes Upload</h3>
                        <div className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                            {/* --- PERUBAHAN DI SINI --- */}
                            <p><strong>Host Target:</strong> {DEFAULT_HOST}</p>
                            <p><strong>Nama File:</strong> {uploadResults.fileName}</p>
                            <p><strong>Kecepatan Upload:</strong> {uploadResults.speedMbps}</p>
                            <p><strong>Ukuran File:</strong> {uploadResults.fileSize}</p>
                            <p><strong>Durasi Upload:</strong> {uploadResults.duration}</p>
                        </div>
                    </div>
                )}
            </div>
            <input type="file" ref={fileInputRef} onChange={handleFileChange} style={{ display: 'none' }} />
        </>
    );
}

export default GoogleDriveUploader;
