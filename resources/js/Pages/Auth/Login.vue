<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <Head title="Sign in" />

    <main class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-12">
        <section class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl shadow-slate-200/60">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">Connect</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900">Welcome back</h1>
            <p class="mt-2 text-sm text-slate-500">Sign in to continue your conversations.</p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <label class="block text-sm font-medium text-slate-700">
                    Email
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                    >
                    <span v-if="form.errors.email" class="mt-1 block text-sm text-rose-600">{{ form.errors.email }}</span>
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Password
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                    >
                    <span v-if="form.errors.password" class="mt-1 block text-sm text-rose-600">{{ form.errors.password }}</span>
                </label>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Remember me
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                >
                    {{ form.processing ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                New here?
                <Link href="/register" class="font-semibold text-indigo-600 hover:text-indigo-700">Create an account</Link>
            </p>
        </section>
    </main>
</template>
