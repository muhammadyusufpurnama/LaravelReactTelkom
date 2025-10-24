// resources/js/Components/WitelPieChart.jsx

import React from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts';

const WitelPieChart = ({ data }) => {
    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884d8', '#DD4477', '#66AA00'];

    // Cek apakah semua nilai dalam data adalah 0
    const allValuesAreZero = data?.every(item => item.value === 0);

    return (
        <ResponsiveContainer width="100%" height={300}>
            {/* Tampilkan pesan jika data tidak ada ATAU semua nilainya 0 */}
            {(!data || data.length === 0 || allValuesAreZero) ? (
                <div className="flex items-center justify-center h-full text-gray-500 text-center">
                    <p>Tidak ada data untuk ditampilkan<br />sesuai filter yang dipilih.</p>
                </div>
            ) : (
                <PieChart>
                    <Pie
                        data={data}
                        cx="50%"
                        cy="50%"
                        labelLine={false}
                        outerRadius={100} // Sedikit diperbesar agar lebih terlihat
                        fill="#8884d8"
                        dataKey="value"
                        nameKey="nama_witel"
                        label={({ name, percent }) => `${(percent * 100).toFixed(0)}%`}
                    >
                        {data.map((entry, index) => (
                            <Cell
                                key={`cell-${index}`}
                                fill={COLORS[index % COLORS.length]}
                                stroke="none" // Tambahan: Menghilangkan garis antar slice
                            />
                        ))}
                    </Pie>
                    <Tooltip formatter={(value, name) => [value.toLocaleString('id-ID'), name]} />
                    <Legend iconType="circle" iconSize={10} />
                </PieChart>
            )}
        </ResponsiveContainer>
    );
};

export default WitelPieChart;
