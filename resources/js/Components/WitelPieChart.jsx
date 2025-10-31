// resources/js/Components/WitelPieChart.jsx

import React from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts';

const WitelPieChart = ({ data }) => {
    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884d8', '#DD4477', '#66AA00'];

    // Cek apakah data tidak ada, kosong, atau semua nilainya 0
    const allValuesAreZero = data?.every(item => item.value === 0);
    const isDataEmpty = !data || data.length === 0 || allValuesAreZero;

    // 1. Pindahkan pengecekan ke luar.
    // Jika data kosong, kembalikan elemen div biasa untuk pesan.
    if (isDataEmpty) {
        return (
            <div className="flex items-center justify-center w-full h-[300px] text-gray-500 text-center">
                {/* Beri tinggi yang sama dengan chart agar layout card tidak rusak */}
                <p>Tidak ada data untuk ditampilkan<br />sesuai filter yang dipilih.</p>
            </div>
        );
    }

    // 2. Jika ada data, baru render ResponsiveContainer dan chart-nya.
    return (
        <ResponsiveContainer width="100%" height={300}>
            <PieChart>
                <Pie
                    data={data}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    outerRadius={100}
                    fill="#8884d8"
                    dataKey="value"
                    nameKey="nama_witel"
                    label={({ name, percent }) => `${(percent * 100).toFixed(0)}%`}
                >
                    {data.map((entry, index) => (
                        <Cell
                            key={`cell-${index}`}
                            fill={COLORS[index % COLORS.length]}
                            stroke="none"
                        />
                    ))}
                </Pie>
                <Tooltip formatter={(value, name) => [value.toLocaleString('id-ID'), name]} />
                <Legend iconType="circle" iconSize={10} />
            </PieChart>
        </ResponsiveContainer>
    );
};

export default WitelPieChart;
