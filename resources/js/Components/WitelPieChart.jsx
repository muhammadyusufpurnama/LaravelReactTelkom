// resources/js/Components/WitelPieChart.jsx

import React from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts';

const WitelPieChart = ({ data }) => {
    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884d8'];

    return (
        <ResponsiveContainer width="100%" height={300}>
            {data && data.length > 0 ? (
                <PieChart>
                    <Pie
                        data={data}
                        cx="50%"
                        cy="50%"
                        labelLine={false}
                        outerRadius={80}
                        fill="#8884d8"
                        dataKey="value"
                        nameKey="nama_witel" // Pastikan properti ini sesuai dengan data dari controller
                        label={({ name, percent }) => `${(percent * 100).toFixed(0)}%`}
                    >
                        {data.map((entry, index) => (
                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                        ))}
                    </Pie>
                    <Tooltip formatter={(value, name) => [value, name]} />
                    <Legend iconType="circle" iconSize={10} />
                </PieChart>
            ) : (
                <div className="flex items-center justify-center h-full text-gray-500">
                    Tidak ada data untuk ditampilkan.
                </div>
            )}
        </ResponsiveContainer>
    );
};

export default WitelPieChart;
