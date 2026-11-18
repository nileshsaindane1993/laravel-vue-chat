<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useConnectionStatus, useEcho } from '@laravel/echo-vue';
import AppearanceToggle from '../../Components/AppearanceToggle.vue';

const props = defineProps({
    conversation: { type: Object, required: true },
    otherUser: { type: Object, required: true },
    currentUser: { type: Object, required: true },
    messages: { type: Array, required: true },
});

const status = useConnectionStatus();
const otherUserIsTyping = ref(false);
const readMessageIds = ref(new Set());
const messageList = ref(null);
const composer = ref(null);
const form = useForm({ body: '' });
let typingTimeout;
let lastTypingWhisperAt = 0;
let isMarkingMessagesRead = false;

const { channel } = useEcho(
    `conversations.${props.conversation.id}`,
    'MessageSent',
    () => {},
);

function clearTypingIndicator() {
    otherUserIsTyping.value = false;
    window.clearTimeout(typingTimeout);
}

channel().listenForWhisper('typing', (event) => {
    if (Number(event.id) !== props.otherUser.id) {
        return;
    }

    otherUserIsTyping.value = true;
    window.clearTimeout(typingTimeout);
    typingTimeout = window.setTimeout(clearTypingIndicator, 1600);
});

channel().listenForWhisper('stop-typing', (event) => {
    if (Number(event.id) === props.otherUser.id) {
        clearTypingIndicator();
    }
});

channel().listen('MessagesRead', (event) => {
    const updatedReadMessageIds = new Set(readMessageIds.value);

    event.message_ids.forEach((messageId) => {
        updatedReadMessageIds.add(Number(messageId));
    });

    readMessageIds.value = updatedReadMessageIds;
});

function scrollToLatest() {
    nextTick(() => {
        if (messageList.value) {
            messageList.value.scrollTop = messageList.value.scrollHeight;
        }
    });
}

function markMessagesAsRead() {
    if (document.visibilityState !== 'visible' || isMarkingMessagesRead) {
        return;
    }

    const hasUnreadIncomingMessages = props.messages.some((message) => (
        message.sender.id !== props.currentUser.id
        && !message.read_at
        && !readMessageIds.value.has(message.id)
    ));

    if (!hasUnreadIncomingMessages) {
        return;
    }

    isMarkingMessagesRead = true;
    router.post(`/chat/${props.conversation.id}/read`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isMarkingMessagesRead = false;
        },
    });
}

function isMessageSeen(message) {
    return Boolean(message.read_at) || readMessageIds.value.has(message.id);
}

function sendTyping() {
    resizeComposer();

    if (!form.body.trim()) {
        stopTyping();
        return;
    }

    const now = Date.now();

    if (now - lastTypingWhisperAt < 800) {
        return;
    }

    lastTypingWhisperAt = now;
    channel().whisper('typing', {
        id: props.currentUser.id,
    });
}

function resizeComposer() {
    if (!composer.value) {
        return;
    }

    composer.value.style.height = 'auto';
    composer.value.style.height = `${Math.min(composer.value.scrollHeight, 144)}px`;
}

function handleComposerInput() {
    resizeComposer();
    sendTyping();
}

function stopTyping() {
    channel().whisper('stop-typing', {
        id: props.currentUser.id,
    });
}

function sendMessage() {
    if (!form.body.trim() || form.processing) {
        return;
    }

    stopTyping();
    form.post(`/chat/${props.conversation.id}/messages`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            nextTick(resizeComposer);
        },
    });
}

function logout() {
    router.post('/logout');
}

function connectionLabel(connectionStatus) {
    return {
        connected: 'Realtime connected',
        connecting: 'Connecting…',
        reconnecting: 'Reconnecting…',
        disconnected: 'Disconnected',
        failed: 'Connection failed',
    }[connectionStatus] ?? 'Connection status unavailable';
}

function messageDateLabel(value) {
    const date = new Date(value);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
        return 'Today';
    }

    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'long',
        day: 'numeric',
        year: date.getFullYear() === today.getFullYear() ? undefined : 'numeric',
    }).format(date);
}

function messageTime(value) {
    return new Intl.DateTimeFormat(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

watch(() => props.messages, () => {
    scrollToLatest();
    markMessagesAsRead();
});
watch(() => form.body, resizeComposer);
onMounted(() => {
    scrollToLatest();
    document.addEventListener('visibilitychange', markMessagesAsRead);
    markMessagesAsRead();
});
onUnmounted(() => {
    window.clearTimeout(typingTimeout);
    document.removeEventListener('visibilitychange', markMessagesAsRead);
});
</script>

<template>
    <Head :title="`Chat with ${otherUser.name}`" />

    <main class="min-h-dvh bg-gradient-to-br from-slate-100 via-indigo-50/60 to-slate-100 p-0 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900 sm:p-4 lg:p-8">
        <div class="mx-auto flex h-dvh max-w-6xl flex-col overflow-hidden bg-white shadow-xl shadow-slate-300/40 dark:bg-slate-900 dark:shadow-black/40 sm:h-[calc(100dvh-2rem)] sm:rounded-3xl lg:h-[calc(100dvh-4rem)]">
            <header class="z-10 flex shrink-0 items-center justify-between border-b border-slate-100 bg-white/95 px-4 py-3 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 sm:px-7 sm:py-4">
                <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                    <Link
                        href="/chat"
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-indigo-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-indigo-300"
                        aria-label="Back to messages"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h11" />
                        </svg>
                    </Link>
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-100 to-violet-100 font-semibold text-indigo-700 ring-4 ring-indigo-50 dark:from-indigo-950 dark:to-violet-950 dark:text-indigo-300 dark:ring-indigo-950/60">
                        {{ otherUser.name.slice(0, 1).toUpperCase() }}
                    </span>
                    <div class="min-w-0">
                        <h1 class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ otherUser.name }}</h1>
                        <p class="mt-1 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400" aria-live="polite">
                            <span
                                class="size-2 shrink-0 rounded-full"
                                :class="status === 'connected' ? 'bg-emerald-500' : status === 'failed' || status === 'disconnected' ? 'bg-rose-400' : 'animate-pulse bg-amber-400'"
                            />
                            {{ connectionLabel(status) }}
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <AppearanceToggle />
                    <button class="cursor-pointer rounded-xl px-3 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100" @click="logout">
                        Sign out
                    </button>
                </div>
            </header>

            <section
                ref="messageList"
                class="flex-1 space-y-6 overflow-y-auto bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-50/50 via-slate-50 to-slate-50 px-4 py-6 dark:bg-slate-950 dark:from-slate-950 dark:via-slate-950 dark:to-slate-950 sm:px-8 sm:py-8"
                aria-label="Conversation messages"
                aria-live="polite"
            >
                <div v-if="!messages.length" class="flex min-h-full flex-col items-center justify-center px-4 py-12 text-center">
                    <div class="relative flex size-20 items-center justify-center rounded-[1.75rem] bg-white text-indigo-600 shadow-lg shadow-indigo-100/70 ring-1 ring-indigo-100 dark:bg-slate-900 dark:text-indigo-300 dark:shadow-black/30 dark:ring-slate-700">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-9" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 9h9m-9 4h5.25M21 12a8.25 8.25 0 0 1-8.25 8.25 8.2 8.2 0 0 1-4.1-1.09L3 20.25l1.09-5.65A8.2 8.2 0 0 1 3.75 12a8.25 8.25 0 1 1 17.25 0Z" />
                        </svg>
                        <span class="absolute -right-1 -top-1 size-4 rounded-full border-4 border-slate-50 bg-emerald-400" />
                    </div>
                    <p class="mt-6 text-lg font-semibold tracking-tight text-slate-900 dark:text-slate-100">Start a conversation</p>
                    <p class="mt-2 max-w-xs text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Send {{ otherUser.name }} a message. Your private conversation will appear here in real time.
                    </p>
                    <span class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-slate-500 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:text-slate-400 dark:shadow-black/20 dark:ring-slate-700">
                        <span class="size-1.5 rounded-full bg-indigo-400" />
                        Messages are private
                    </span>
                </div>

                <article
                    v-for="(message, index) in messages"
                    :key="message.id"
                    class="space-y-3"
                >
                    <div
                        v-if="index === 0 || messageDateLabel(message.created_at) !== messageDateLabel(messages[index - 1].created_at)"
                        class="flex justify-center"
                    >
                        <span class="rounded-full bg-white/80 px-3 py-1 text-[11px] font-medium text-slate-400 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-800/90 dark:text-slate-400 dark:shadow-black/20 dark:ring-slate-700">
                            {{ messageDateLabel(message.created_at) }}
                        </span>
                    </div>

                    <div class="flex items-end gap-2.5" :class="message.sender.id === currentUser.id ? 'justify-end' : 'justify-start'">
                        <span
                            v-if="message.sender.id !== currentUser.id"
                            class="mb-5 flex size-8 shrink-0 items-center justify-center rounded-full bg-white text-xs font-semibold text-indigo-600 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-800 dark:text-indigo-300 dark:shadow-black/20 dark:ring-slate-700"
                            :aria-label="otherUser.name"
                        >
                            {{ otherUser.name.slice(0, 1).toUpperCase() }}
                        </span>
                        <div class="flex max-w-[85%] flex-col gap-1.5 sm:max-w-[72%]" :class="message.sender.id === currentUser.id ? 'items-end' : 'items-start'">
                            <span class="px-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                {{ message.sender.id === currentUser.id ? 'You' : otherUser.name }}
                            </span>
                            <div
                                class="rounded-2xl px-4 py-3 shadow-sm sm:px-5"
                                :class="message.sender.id === currentUser.id
                                    ? 'rounded-br-md bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-indigo-200/50 dark:shadow-black/30'
                                    : 'rounded-bl-md bg-white text-slate-700 ring-1 ring-slate-200/70 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700'"
                            >
                                <p class="whitespace-pre-wrap break-words text-sm leading-6">{{ message.body }}</p>
                            </div>
                            <div class="flex items-center gap-1 px-1">
                                <time class="text-[10px] text-slate-400 dark:text-slate-500">{{ messageTime(message.created_at) }}</time>
                                <span
                                    v-if="message.sender.id === currentUser.id"
                                    class="inline-flex"
                                    :class="isMessageSeen(message) ? 'text-sky-300' : 'text-white/70'"
                                    :aria-label="isMessageSeen(message) ? 'Seen' : 'Sent'"
                                    :title="isMessageSeen(message) ? 'Seen' : 'Sent'"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                                        <path v-if="isMessageSeen(message)" d="m3 12 4 4L17 6" />
                                        <path v-if="isMessageSeen(message)" d="m9 12 4 4 8-10" />
                                        <path v-else d="m6 12 4 4L18 8" />
                                    </svg>
                                    <span class="sr-only">{{ isMessageSeen(message) ? 'Seen' : 'Sent' }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </article>

            </section>

            <div
                v-if="otherUserIsTyping"
                class="flex shrink-0 items-center gap-2.5 border-t border-slate-100 bg-white px-5 pb-2 pt-3 dark:border-slate-800 dark:bg-slate-900 sm:px-8"
                role="status"
                aria-live="polite"
            >
                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                    {{ otherUser.name.slice(0, 1).toUpperCase() }}
                </span>
                <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ otherUser.name }} is typing</span>
                <span class="flex items-center gap-1" aria-hidden="true">
                    <span class="size-1 animate-bounce rounded-full bg-indigo-300 [animation-delay:-0.3s]" />
                    <span class="size-1 animate-bounce rounded-full bg-indigo-400 [animation-delay:-0.15s]" />
                    <span class="size-1 animate-bounce rounded-full bg-indigo-500" />
                </span>
            </div>

            <form class="shrink-0 border-t border-slate-100 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 sm:px-7 sm:py-4" @submit.prevent="sendMessage">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 transition focus-within:border-indigo-300 focus-within:bg-white focus-within:ring-4 focus-within:ring-indigo-100 dark:border-slate-700 dark:bg-slate-800 dark:focus-within:border-indigo-700 dark:focus-within:bg-slate-800 dark:focus-within:ring-indigo-950">
                    <div class="flex items-end gap-2">
                        <textarea
                            ref="composer"
                            v-model="form.body"
                            rows="1"
                            maxlength="5000"
                            placeholder="Write a message"
                            aria-label="Write a message"
                            class="max-h-36 min-h-10 flex-1 resize-none overflow-y-auto border-0 bg-transparent px-3 py-2.5 text-sm leading-5 text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500"
                            @input="handleComposerInput"
                            @blur="stopTyping"
                            @keydown.enter.exact.prevent="sendMessage"
                        />
                        <button
                            type="submit"
                            :disabled="form.processing || !form.body.trim()"
                            aria-label="Send message"
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none dark:disabled:bg-slate-700 dark:disabled:text-slate-500"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 14-7-4.5 14-2.5-6.5L5 12Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 12 19 5" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.body" class="px-3 pt-1 text-sm text-rose-600">{{ form.errors.body }}</p>
                </div>
                <p class="mt-2 px-1 text-[11px] text-slate-400 dark:text-slate-500">Enter to send <span class="px-1">·</span> Shift + Enter for a new line</p>
            </form>
        </div>
    </main>
</template>
