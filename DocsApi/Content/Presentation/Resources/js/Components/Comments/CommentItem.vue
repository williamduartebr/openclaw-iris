<template>
    <div class="group" :id="`comment-${comment.id}`">
        <!-- Top Level Comment / Reply Content -->
        <div class="flex gap-4 relative">
            <div class="shrink-0 z-10">
                <div :class="[
                    'rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-semibold border-2 border-white shadow-sm overflow-hidden',
                    isReply ? 'w-8 h-8 text-xs' : 'w-10 h-10'
                ]">
                    <img
                        v-if="comment.user.avatar_url"
                        :src="comment.user.avatar_url"
                        :alt="comment.user.name"
                        class="w-full h-full object-cover"
                    />
                    <span v-else>{{ comment.user.name.charAt(0) }}</span>
                </div>
            </div>
            <div class="flex-grow">
                <div class="flex items-center gap-2 mb-1.5">
                    <span :class="['font-semibold text-gray-900', isReply ? 'text-sm' : 'text-[0.95rem]']">
                        {{ comment.user.abbreviated_name || comment.user.name }}
                    </span>
                    <span class="text-xs text-gray-400">• <span :title="comment.created_at">{{ formatDate(comment.created_at) }}</span></span>

                    <span v-if="comment.is_official" class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 text-[10px] font-semibold uppercase tracking-wide">
                        Oficial
                    </span>
                </div>
                <!-- Content or Edit Form -->
                <div v-if="isEditing" class="mb-3">
                <div class="relative">
                    <textarea
                        v-model="editContent"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm mb-2 resize-none pr-16"
                        :class="{'border-red-500 focus:border-red-500': editCharCount > 500 || (editCharCount > 0 && editCharCount < 3)}"
                        rows="3"
                        maxlength="600"
                        minlength="3"
                        @paste.prevent="handlePaste"
                    ></textarea>
                    <div class="absolute bottom-4 right-2 text-xs font-medium transition-colors"
                         :class="{ 'text-red-500': editCharCount > 500, 'text-gray-400': editCharCount <= 500 }">
                        {{ editCharCount }}/600
                    </div>
                </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="updateComment"
                            :disabled="isSubmittingEdit || editCharCount < 3"
                            class="px-3 py-1 bg-blue-800 text-white text-xs font-medium rounded-lg hover:bg-blue-900 disabled:opacity-50"
                        >
                            {{ isSubmittingEdit ? 'Salvando...' : 'Salvar' }}
                        </button>
                        <button
                            @click="cancelEdit"
                            class="px-3 py-1 bg-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-300"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
                <div v-else :class="['text-gray-700 leading-relaxed mb-3 whitespace-pre-wrap break-all', isReply ? 'text-sm' : 'text-[0.95rem]']" v-text="comment.content"></div>

                <div class="flex items-center gap-4">
                    <!-- Reply Button -->
                    <button
                        @click="toggleReplyForm"
                        class="text-sm font-medium text-gray-500 hover:text-blue-800 flex items-center gap-1.5 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z" />
                        </svg>
                        Responder
                    </button>

                    <!-- Edit/Delete Actions (5 min window) -->
                    <div v-if="canEdit" class="flex items-center gap-2 text-xs">
                         <span class="text-orange-500 font-medium" title="Tempo restante para editar/excluir">
                            {{ formattedRemainingTime }}
                        </span>
                        <button @click="startEdit" class="text-gray-400 hover:text-blue-800 font-medium">Editar</button>
                        <button @click="deleteComment" class="text-gray-400 hover:text-red-600 font-medium">Excluir</button>
                    </div>
                </div>

                <!-- Reply Form -->
                <transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="transform -translate-y-2 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform -translate-y-2 opacity-0"
                >
                    <div v-if="showReplyForm" class="mt-4 pl-4 border-l-2 border-gray-100 origin-top">
                        <div v-if="user" class="flex gap-3">
                            <div class="shrink-0">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 font-semibold text-xs">
                                    {{ user.name.charAt(0) }}
                                </div>
                            </div>
                            <div class="flex-grow">
                                <CommentForm
                                    :article-slug="articleSlug"
                                    :parent-id="comment.id"
                                    @success="handleReplySuccess"
                                    @cancel="showReplyForm = false"
                                />
                            </div>
                        </div>
                        <div v-else class="p-3 bg-gray-50 rounded text-sm text-gray-500">
                             <button @click="$emit('open-auth')" class="font-medium text-blue-800 hover:underline">Entre</button> para responder.
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Nested Replies (Recursive) -->
        <div v-if="comment.replies && comment.replies.length > 0" class="relative mt-2">
            <!-- Connection Line -->
            <div v-if="!isReply" class="absolute left-5 top-0 bottom-6 w-px bg-gray-200 -z-0"></div>

            <div class="space-y-6 pt-2">
                <transition-group
                    enter-active-class="transition duration-500 ease-out"
                    enter-from-class="transform translate-x-4 opacity-0"
                    enter-to-class="transform translate-x-0 opacity-100"
                    leave-active-class="transition duration-300 ease-in"
                    leave-from-class="transform translate-x-0 opacity-100"
                    leave-to-class="transform translate-x-4 opacity-0"
                >
                    <div v-for="reply in comment.replies" :key="reply.id" class="relative ml-12">
                         <!-- Curve Line -->
                        <div class="absolute -left-7 top-5 w-6 h-px bg-gray-200"></div>

                        <CommentItem
                             :comment="reply"
                             :user="user"
                             :article-slug="articleSlug"
                             :is-reply="true"
                             @comment-deleted="handleReplyDeleted"
                        />
                    </div>
                </transition-group>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <NotificationModal
            :show="showDeleteConfirm"
            title="Excluir Comentário"
            message="Tem certeza que deseja excluir este comentário? Esta ação não pode ser desfeita."
            confirm-text="Sim, excluir"
            cancel-text="Cancelar"
            variant="danger"
            @confirm="confirmDelete"
            @cancel="showDeleteConfirm = false"
        />

        <!-- Paste Blocked Alert -->
        <NotificationModal
            :show="showPasteAlert"
            title="Ação Bloqueada"
            message="Colar texto não é permitido. Por favor, digite seu comentário manualmente."
            confirm-text="Entendi"
            variant="warning"
            :single-button="true"
            @confirm="showPasteAlert = false"
            @cancel="showPasteAlert = false"
        />

        <!-- Error/Validation Modal -->
        <NotificationModal
            :show="showErrorModal"
            title="Atenção"
            :message="errorMessage"
            confirm-text="Entendi"
            variant="warning"
            :single-button="true"
            @confirm="showErrorModal = false"
            @cancel="showErrorModal = false"
        />
    </div>
</template>

<script setup>
import NotificationModal from '@shared/Components/NotificationModal.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import CommentForm from './CommentForm.vue';
import { formatDistanceToNow, differenceInSeconds } from 'date-fns';
import { ptBR } from 'date-fns/locale';
import axios from 'axios';

const props = defineProps({
    comment: {
        type: Object,
        required: true
    },
    user: {
        type: Object,
        default: null
    },
    articleSlug: {
        type: String,
        required: true
    },
    isReply: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['open-auth', 'comment-deleted']);

const showReplyForm = ref(false);
const isEditing = ref(false);
const editContent = ref('');
const remainingTime = ref(0);
let timerInterval = null;
const isSubmittingEdit = ref(false);
const showDeleteConfirm = ref(false);
const showPasteAlert = ref(false);

const editCharCount = computed(() => editContent.value.length);

const handlePaste = () => {
    showPasteAlert.value = true;
};

const toggleReplyForm = () => {
    showReplyForm.value = !showReplyForm.value;
};

const handleReplySuccess = (data) => {
    showReplyForm.value = false;
    if (!props.comment.replies) {
        props.comment.replies = [];
    }
    props.comment.replies.push(data.comment);
};

const handleReplyDeleted = (commentId) => {
    if (props.comment.replies) {
        props.comment.replies = props.comment.replies.filter(r => r.id !== commentId);
    }
};

const formatDate = (dateString) => {
    try {
        return formatDistanceToNow(new Date(dateString), { addSuffix: true, locale: ptBR });
    } catch (e) {
        return dateString;
    }
};

// Edit/Delete Logic
const canEdit = computed(() => {
    return props.comment.is_editable && remainingTime.value > 0;
});

const formattedRemainingTime = computed(() => {
    if (remainingTime.value <= 0) return '';
    const minutes = Math.floor(remainingTime.value / 60);
    const seconds = remainingTime.value % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

const startEdit = () => {
    editContent.value = props.comment.content;
    isEditing.value = true;
};

const cancelEdit = () => {
    isEditing.value = false;
    editContent.value = '';
};

const showErrorModal = ref(false);
const errorMessage = ref('');

const updateComment = async () => {
    isSubmittingEdit.value = true;
    try {
        const response = await axios.put(`/artigos/${props.articleSlug}/comentarios/${props.comment.id}`, {
            content: editContent.value
        });

        props.comment.content = response.data.comment.content;
        props.comment.is_approved = response.data.is_approved;
        isEditing.value = false;

    } catch (error) {
        console.error('Failed to update comment', error);

        if (error.response?.status === 422) {
             const contentErrors = error.response.data.errors?.content;
             if (contentErrors) {
                 errorMessage.value = Array.isArray(contentErrors) ? contentErrors[0] : contentErrors;
                 showErrorModal.value = true;
                 return;
             }
        }

        alert(error.response?.data?.message || 'Erro ao atualizar comentário.');
    } finally {
        isSubmittingEdit.value = false;
    }
};

const deleteComment = () => {
    showDeleteConfirm.value = true;
};

const confirmDelete = async () => {
    showDeleteConfirm.value = false;
    try {
        await axios.delete(`/artigos/${props.articleSlug}/comentarios/${props.comment.id}`);
        emit('comment-deleted', props.comment.id);
    } catch (error) {
        if (error.response && error.response.status === 404) {
            emit('comment-deleted', props.comment.id);
            return;
        }
        console.error('Failed to delete comment', error);
        alert(error.response?.data?.message || 'Erro ao excluir comentário.');
    }
};

const calculateRemainingTime = () => {
    if (!props.comment.created_at || !props.comment.is_editable) return;

    const created = new Date(props.comment.created_at);
    const limit = 300;
    const elapsed = differenceInSeconds(new Date(), created);
    remainingTime.value = Math.max(0, limit - elapsed);
};

onMounted(() => {
    if (props.comment.is_editable) {
        calculateRemainingTime();
        if (remainingTime.value > 0) {
            timerInterval = setInterval(() => {
                calculateRemainingTime();
                if (remainingTime.value <= 0 && timerInterval) {
                    clearInterval(timerInterval);
                }
            }, 1000);
        }
    }
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>
