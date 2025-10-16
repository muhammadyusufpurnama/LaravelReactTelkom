// resources/js/Components/SessionSubTypeChart.jsx

import React from 'react';
import { Bar } from 'react-chartjs-2';

// Opsi untuk mengonfigurasi chart
const options = {
    indexAxis: 'y', // <-- Ini yang membuat chart menjadi horizontal
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false, // Menghilangkan legenda karena tidak diperlukan
        },
        tooltip: {
            // [FUNGSI UTAMA] Mengaktifkan dan mengonfigurasi tooltip
            enabled: true,
            callbacks: {
                // Mengubah teks di dalam tooltip agar lebih jelas
                label: function (context) {
                    return `Jumlah: ${context.raw}`;
                }
            }
        }
    },
    scales: {
        x: {
            // Opsi untuk sumbu horizontal (jumlah)
            grid: {
                display: false, // Menghilangkan garis grid vertikal
            },
            ticks: {
                display: false, // Menghilangkan label angka di bawah
            },
            border: {
                display: false // Menghilangkan garis sumbu x
            }
        },
        y: {
            // Opsi untuk sumbu vertikal (AO, SO, DO)
            grid: {
                display: false, // Menghilangkan garis grid horizontal
            },
            border: {
                display: false // Menghilangkan garis sumbu y
            }
        }
    }
};

export default function SessionSubTypeChart({ data }) {
    // Tampilkan pesan jika data tidak ada atau kosong
    if (!data || data.length === 0) {
        return <div className="flex items-center justify-center h-full text-gray-500">Tidak ada data untuk ditampilkan.</div>;
    }

    const chartData = {
        // Label untuk setiap bar (AO, SO, DO, dll.)
        labels: data.map(item => item.sub_type),
        datasets: [
            {
                label: 'Jumlah Order',
                // Data (angka) untuk setiap bar
                data: data.map(item => item.total),
                // Fungsi untuk memberikan warna berbeda pada setiap bar
                backgroundColor: (context) => {
                    const subType = context.chart.data.labels[context.dataIndex];
                    if (subType === 'MO') return '#ec4899'; // Pink
                    if (subType === 'AO') return '#8b5cf6'; // Ungu
                    return '#a5b4fc'; // Warna default jika ada sub-type lain
                },
                borderRadius: 10, // Membuat ujung bar menjadi bulat
                barThickness: 20, // Mengatur ketebalan bar
            },
        ],
    };

    return <Bar options={options} data={chartData} />;
}
