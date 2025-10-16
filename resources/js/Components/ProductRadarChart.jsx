// resources/js/Components/ProductRadarChart.jsx

import React from 'react';
import { Radar } from 'react-chartjs-2';

// Opsi konfigurasi untuk chart
const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        // Konfigurasi untuk mengecilkan tooltip saat di-hover
        tooltip: {
            padding: 8,
            titleFont: {
                size: 12,
            },
            bodyFont: {
                size: 11,
            },
        },
        // Konfigurasi untuk posisi dan spasi legenda
        legend: {
            position: 'bottom',
            labels: {
                boxWidth: 15,
                padding: 20, // Menambah jarak horizontal antar item legenda
            }
        },
    },
    // Menambahkan padding di dalam area chart untuk memberi ruang
    layout: {
        padding: {
            top: 20,
            bottom: 20
        }
    },
    scales: {
        r: {
            angleLines: {
                display: false
            },
            suggestedMin: 0,
        }
    },
};

export default function ProductRadarChart({ data }) {
    // Tampilkan pesan jika tidak ada data
    if (!data || data.length === 0) {
        return <div className="flex items-center justify-center h-full text-gray-500">Tidak ada data untuk ditampilkan.</div>;
    }

    // Ambil label (nama Witel) dari objek data pertama
    const labels = Object.keys(data[0] || {}).filter(key => key !== 'product_name');

    // Siapkan data untuk chart
    const chartData = {
        labels: labels,
        datasets: data.map((productData, index) => {
            // Palet warna yang akan digunakan berulang
            const colors = ['#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#3b82f6'];
            const color = colors[index % colors.length];

            return {
                label: productData.product_name,
                data: labels.map(label => productData[label] || 0),
                backgroundColor: `${color}33`, // Warna area dengan transparansi
                borderColor: color,
                borderWidth: 2,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: color
            };
        }),
    };

    // Render komponen Radar dengan data dan opsi yang sudah disiapkan
    return <Radar data={chartData} options={options} />;
}
