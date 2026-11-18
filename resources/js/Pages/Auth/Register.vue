<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register');
}
</script>

<template>
    <Head title="Create account" />

    <main class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-12">
        <section class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl shadow-slate-200/60">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">Connect</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900">Create your account</h1>
            <p class="mt-2 text-sm text-slate-500">Join the conversation in a few seconds.</p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <label class="block text-sm font-medium text-slate-700">
                    Name
                    <input
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                    >
                    <span v-if="form.errors.name" class="mt-1 block text-sm text-rose-600">{{ form.errors.name }}</span>
                </label>

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
                        autocomplete="new-password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                    >
                    <span v-if="form.errors.password" class="mt-1 block text-sm text-rose-600">{{ form.errors.password }}</span>
                </label>

                <label class="block text-sm font-medium text-slate-700">
                    Confirm password
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                    >
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                >
                    {{ form.processing ? 'Creating account…' : 'Create account' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already registered?
                <Link href="/login" class="font-semibold text-indigo-600 hover:text-indigo-700">Sign in</Link>
            </p>
        </section>
    </main>
</template>
