// resources/js/Pages/Users/Create.jsx

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'user', // Default role saat form dibuka
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('superadmin.users.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">Create New User</h2>
                    <Link href={route('superadmin.users.index')} className="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Cancel
                    </Link>
                </div>
            }
        >
            <Head title="Create User" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="p-6">
                            <div>
                                <InputLabel htmlFor="name" value="Name" />
                                <TextInput id="name" name="name" value={data.name} className="mt-1 block w-full" isFocused={true} onChange={(e) => setData('name', e.target.value)} required />
                                <InputError message={errors.name} className="mt-2" />
                            </div>
                            <div className="mt-4">
                                <InputLabel htmlFor="email" value="Email" />
                                <TextInput id="email" type="email" name="email" value={data.email} className="mt-1 block w-full" onChange={(e) => setData('email', e.target.value)} required />
                                <InputError message={errors.email} className="mt-2" />
                            </div>
                            <div className="mt-4">
                                <InputLabel htmlFor="password" value="Password" />
                                <TextInput id="password" type="password" name="password" value={data.password} className="mt-1 block w-full" onChange={(e) => setData('password', e.target.value)} required />
                                <InputError message={errors.password} className="mt-2" />
                            </div>
                            <div className="mt-4">
                                <InputLabel htmlFor="password_confirmation" value="Confirm Password" />
                                <TextInput id="password_confirmation" type="password" name="password_confirmation" value={data.password_confirmation} className="mt-1 block w-full" onChange={(e) => setData('password_confirmation', e.target.value)} required />
                                <InputError message={errors.password_confirmation} className="mt-2" />
                            </div>
                            <div className="mt-4">
                                <InputLabel htmlFor="role" value="Role" />
                                <select id="role" name="role" value={data.role} className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" onChange={(e) => setData('role', e.target.value)}>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <InputError message={errors.role} className="mt-2" />
                            </div>
                            <div className="flex items-center justify-end mt-6">
                                <PrimaryButton disabled={processing}>
                                    Create User
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
