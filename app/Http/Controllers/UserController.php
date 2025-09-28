<?php

namespace App\Http\Controllers;

use App\Models\User; // <-- 1. Import model User
use Illuminate\Http\Request;
use Inertia\Inertia; // <-- 2. Import Inertia

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 3. Ambil data pengguna dari database (dengan paginasi)
        $users = User::paginate(10);

        // 4. Kirim data ke komponen React bernama 'Users/Index'
        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
