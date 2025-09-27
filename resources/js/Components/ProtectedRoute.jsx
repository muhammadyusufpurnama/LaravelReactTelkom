// src/components/ProtectedRoute.js
import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

// Untuk halaman yang hanya bisa diakses user yang sudah login (user biasa & admin)
export const ProtectedRoute = () => {
    const { token } = useAuth();

    // Jika tidak ada token (belum login), lempar ke halaman login
    return token ? <Outlet /> : <Navigate to="/login" />;
};

// Untuk halaman yang HANYA bisa diakses oleh ADMIN
export const AdminRoute = () => {
    const { token, user } = useAuth();

    // Cek apakah sudah login DAN rolenya adalah 'admin'
    if (token && user?.role === 'admin') {
        return <Outlet />;
    } else {
        // Jika bukan, lempar ke halaman "unauthorized" atau halaman lain
        return <Navigate to="/unauthorized" />;
    }
};
