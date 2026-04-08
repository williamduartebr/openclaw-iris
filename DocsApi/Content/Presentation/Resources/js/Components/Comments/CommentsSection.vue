<template>
    <div class="border-t border-gray-200 pt-12">
        <h3 class="font-['Montserrat'] font-bold text-2xl mb-8 text-gray-900">
            Comentários ({{ totalComments }})
        </h3>

        <!-- Comment Form / Auth Prompt -->
        <div class="mb-10 p-6 bg-gray-50 rounded-lg">
            <template v-if="user">
                <CommentForm
                    :article-slug="articleSlug"
                    @success="handleNewComment"
                />
            </template>
            <template v-else>
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                    <div class="text-gray-600">
                        O que você achou deste artigo?
                    </div>
                    <button @click="showAuthModal = true"
                            class="w-full sm:w-auto px-6 py-2 bg-blue-800 text-white font-medium rounded-lg hover:bg-blue-900 transition shadow-sm">
                        Escrever comentário
                    </button>
                </div>
            </template>
        </div>

        <!-- Comments List -->
        <div class="space-y-10">
            <template v-if="comments.length > 0">
                <transition-group
                    enter-active-class="transition duration-500 ease-out"
                    enter-from-class="transform translate-y-4 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition duration-300 ease-in"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform translate-y-4 opacity-0"
                >
                    <CommentItem
                        v-for="comment in comments"
                        :key="comment.id"
                        :comment="comment"
                        :user="user"
                        :article-slug="articleSlug"
                        @open-auth="showAuthModal = true"
                        @comment-deleted="handleCommentDeleted"
                    />
                </transition-group>
            </template>
            <template v-else>
                <div class="text-center py-12 text-gray-400">
                     <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-4 opacity-50">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                    <p class="font-medium">Seja o primeiro a compartilhar seus pensamentos!</p>
                </div>
            </template>

            <!-- Pagination -->
            <div v-if="paginationLinks" class="mt-12 pt-6 border-t border-gray-100" v-html="paginationLinks"></div>
        </div>

        <!-- Auth Modal -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showAuthModal" class="fixed inset-0 z-[9999] flex items-center justify-center px-4">
                 <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="showAuthModal = false"></div>
                 <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-0 overflow-hidden transform transition-all animate-fade-in-up">

                <button @click="showAuthModal = false" class="absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 transition z-10 rounded-full hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="p-8 text-center">
                    <div class="relative w-20 h-20 mx-auto mb-6">
                        <div class="absolute inset-0 bg-blue-100 rounded-full animate-pulse"></div>
                        <div class="relative w-full h-full bg-linear-to-br from-blue-50 to-white rounded-full flex items-center justify-center border border-blue-100 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-blue-800">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                    </div>

                    <h3 class="text-2xl font-['Montserrat'] font-bold text-gray-900 mb-3">
                        Faça parte da discussão
                    </h3>

                    <p class="text-gray-600 mb-8 text-sm leading-relaxed px-4">
                        Utilize sua conta Google para entrar, comentar e interagir com nossa comunidade.
                    </p>

                    <GoogleLogin
                        v-if="googleClientId"
                        :client-id="googleClientId"
                        @login-success="handleGoogleLogin"
                    />

                    <!-- Fallback Email Link -->
                     <a href="/entrar" class="flex items-center justify-center w-full py-2.5 px-4 mt-4 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg transition hover:border-gray-400 text-sm">
                        Continuar com E-mail
                    </a>
                </div>
             </div>
             </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, provide } from 'vue';
import CommentItem from './CommentItem.vue';
import CommentForm from './CommentForm.vue';
import GoogleLogin from './GoogleLogin.vue';

const props = defineProps({
    initialComments: { type: Array, default: () => [] },
    paginationLinks: { type: String, default: '' },
    articleSlug: { type: String, required: true },
    submitUrl: { type: String, required: true },
    user: { type: Object, default: null },
    googleClientId: { type: String, default: '' }
});

provide('submitUrl', props.submitUrl);

const comments = ref(props.initialComments);
const showAuthModal = ref(false);

const totalComments = computed(() => {
    let count = comments.value.length;
    comments.value.forEach(c => {
        if(c.replies) count += c.replies.length;
    });
    return count;
});

const handleNewComment = (data) => {
    if (data.is_approved) {
        comments.value.unshift(data.comment);
    }
};

const handleCommentDeleted = (commentId) => {
    comments.value = comments.value.filter(c => c.id !== commentId);
};

const handleGoogleLogin = (response) => {
    fetch("/auth/google/callback", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({ credential: response.credential })
    })
    .then(res => res.json())
    .then(data => {
        window.location.reload();
    })
    .catch(err => {
        console.error("Login failed", err);
        alert("Falha no login Google");
    });
};

onMounted(() => {
    window.addEventListener('open-auth-modal', () => {
        showAuthModal.value = true;
    });
});
</script>
