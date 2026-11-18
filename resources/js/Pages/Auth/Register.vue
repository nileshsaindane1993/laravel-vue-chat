<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppearanceToggle from '../../Components/AppearanceToggle.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel Vue Chat';

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
    <Head :title="appName" />

    <main class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-12 dark:bg-slate-950">
        <div class="fixed right-4 top-4 z-40">
            <AppearanceToggle />
        </div>
        <section class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl shadow-slate-200/60 dark:bg-slate-900 dark:shadow-black/30">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Connect</p>
            <h1 class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">Create your account</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Join the conversation in a few seconds.</p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Name
                    <input
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        placeholder="Enter Name"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-950"
                    >
                    <span v-if="form.errors.name" class="mt-1 block text-sm text-rose-600 dark:text-rose-400">{{ form.errors.name }}</span>
                </label>

                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Email
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="Enter Email"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-950"
                    >
                    <span v-if="form.errors.email" class="mt-1 block text-sm text-rose-600 dark:text-rose-400">{{ form.errors.email }}</span>
                </label>

                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Password
                    <input
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Enter Password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-950"
                    >
                    <span v-if="form.errors.password" class="mt-1 block text-sm text-rose-600 dark:text-rose-400">{{ form.errors.password }}</span>
                </label>

                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Confirm Password
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Confirm Password"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-indigo-950"
                    >
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full cursor-pointer rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                >
                    {{ form.processing ? 'Creating account…' : 'Create account' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
                Already registered?
                <Link href="/login" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">Sign in</Link>
            </p>
        </section>
    </main>
</template>
