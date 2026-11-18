<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    users: { type: Array, required: true },
    conversations: { type: Array, required: true },
});

const page = usePage();

function startConversation(userId) {
    router.post(`/chat/users/${userId}/conversation`);
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <Head title="Messages" />

    <main class="min-h-screen bg-slate-100 px-4 py-8 sm:px-6">
        <div class="mx-auto grid min-h-[calc(100vh-4rem)] max-w-6xl overflow-hidden rounded-3xl bg-white shadow-xl shadow-slate-200/70 md:grid-cols-[340px_1fr]">
            <aside class="border-b border-slate-100 bg-slate-50 p-6 md:border-b-0 md:border-r">
                <header class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Connect</p>
                        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Messages</h1>
                    </div>
                    <button class="text-sm font-medium text-slate-500 hover:text-slate-900" @click="logout">Sign out</button>
                </header>

                <div class="mt-8">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Recent chats</h2>
                    <div v-if="conversations.length" class="space-y-2">
                        <Link
                            v-for="conversation in conversations"
                            :key="conversation.id"
                            :href="`/chat/${conversation.id}`"
                            class="block rounded-2xl border border-slate-100 bg-white p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40"
                        >
                            <span class="block font-medium text-slate-800">{{ conversation.user.name }}</span>
                            <span class="mt-1 block truncate text-sm text-slate-500">{{ conversation.last_message || 'Start a conversation' }}</span>
                        </Link>
                    </div>
                    <p v-else class="rounded-2xl border border-dashed border-slate-200 p-4 text-sm text-slate-500">
                        Your conversations will appear here.
                    </p>
                </div>

                <div class="mt-8">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">People</h2>
                    <div v-if="users.length" class="space-y-1">
                        <button
                            v-for="user in users"
                            :key="user.id"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-indigo-50"
                            @click="startConversation(user.id)"
                        >
                            <span class="flex size-10 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700">
                                {{ user.name.slice(0, 1).toUpperCase() }}
                            </span>
                            <span class="font-medium text-slate-700">{{ user.name }}</span>
                        </button>
                    </div>
                    <p v-else class="text-sm text-slate-500">No other accounts yet. Share the registration page to get started.</p>
                </div>
            </aside>

            <section class="flex min-h-[420px] flex-col items-center justify-center px-6 py-12 text-center">
                <div class="flex size-16 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">✦</div>
                <h2 class="mt-5 text-xl font-semibold text-slate-900">Your conversations, in real time</h2>
                <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                    Choose someone from the list to start a private chat. New messages arrive without refreshing the page.
                </p>
                <p class="mt-8 text-xs text-slate-400">Signed in as {{ page.props.auth.user.name }}</p>
            </section>
        </div>
    </main>
</template>
