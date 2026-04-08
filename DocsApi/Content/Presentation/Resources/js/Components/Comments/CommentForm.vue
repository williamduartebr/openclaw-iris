<template>
    <div class="mb-4">
        <form @submit.prevent="submitComment">
            <div class="mb-4">
                <label v-if="!parentId" for="content" class="block text-sm font-medium text-gray-700 mb-2">
                    Deixe seu comentário
                </label>
                <div class="relative">
                    <textarea
                        v-model="form.content"
                        :id="parentId ? `content-${parentId}` : 'content'"
                        rows="3"
                        class="w-full px-4 py-3 rounded-lg border bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                        :class="[
                            (errors.content || hasUrlWarning || charCount > 500)
                                ? 'border-red-500 focus:ring-red-500'
                                : 'border-gray-300'
                        ]"
                        :placeholder="parentId ? 'Escreva uma resposta...' : 'O que você achou deste artigo?'"
                        required
                        :disabled="loading || showSuccessMessage"
                        maxlength="600"
                        minlength="3"
                        @paste.prevent="handlePaste"
                    ></textarea>

                    <div class="absolute bottom-2 right-3 text-xs font-medium transition-colors"
                         :class="{ 'text-red-500': charCount > 500, 'text-gray-400': charCount <= 500 }">
                        {{ charCount }}/600
                    </div>
                </div>
                <p v-if="hasUrlWarning" class="mt-1 text-sm text-red-600 font-medium">
                    Links não são permitidos nos comentários.
                </p>
                <p v-if="errors.content" class="mt-1 text-sm text-red-600">{{ errors.content }}</p>
            </div>

            <div class="flex justify-end gap-2 items-center">
                <slot name="actions">
                    <button
                        v-if="parentId"
                        type="button"
                        @click="$emit('cancel')"
                        class="px-3 py-1 text-xs font-medium text-gray-500 hover:text-gray-700"
                        :disabled="loading || showSuccessMessage"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="bg-blue-800 text-white font-medium hover:bg-blue-900 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        :class="[
                            parentId ? 'px-4 py-1.5 text-xs rounded-lg' : 'px-6 py-2 rounded-lg',
                            showSuccessMessage ? 'bg-green-600 hover:bg-green-700' : ''
                        ]"
                        :disabled="loading || showSuccessMessage || hasUrlWarning || hasMinLengthWarning || charCount === 0"
                    >
                        <span v-if="showSuccessMessage">
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 inline-block">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Seu comentário foi publicado
                        </span>
                        <span v-else>
                             {{ loading ? 'Enviando...' : (parentId ? 'Responder' : 'Publicar comentário') }}
                        </span>
                    </button>
                </slot>
            </div>
        </form>

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
    </div>
</template>

<script setup>
import NotificationModal from '@shared/Components/NotificationModal.vue';
import { reactive, ref, inject, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    articleSlug: {
        type: String,
        required: true
    },
    parentId: {
        type: [String, Number],
        default: null
    }
});

const submitUrl = inject('submitUrl');
const emit = defineEmits(['success', 'cancel']);

const form = reactive({
    content: '',
    parent_id: props.parentId
});

const loading = ref(false);
const showSuccessMessage = ref(false);
const hasUrlWarning = ref(false);
const showPasteAlert = ref(false);
const errors = reactive({});

const charCount = computed(() => form.content.length);

const handlePaste = (e) => {
    showPasteAlert.value = true;
};

// Watch content for URLs to show proactive warning
watch(() => form.content, (newContent) => {
    checkForUrl(newContent);
    if (errors.content) errors.content = '';
});

const checkForUrl = (text) => {
    const urlPattern = /(https?:\/\/|ftp:\/\/|www\.)[^\s]+|[a-z0-9]+\s*[\.,]\s*(com|br|net|org)\b/i;
    hasUrlWarning.value = urlPattern.test(text);
};

const hasMinLengthWarning = computed(() => {
    return charCount.value > 0 && charCount.value < 3;
});

const submitComment = async () => {
    if (hasUrlWarning.value) {
        errors.content = 'Links não são permitidos nos comentários.';
        return;
    }

    if (charCount.value < 3) {
        errors.content = 'O comentário deve ter no mínimo 3 caracteres.';
        return;
    }

    loading.value = true;
    errors.content = '';

    try {
        const response = await axios.post(submitUrl, form, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (response.data.success) {
            if (!response.data.is_approved) {
                 window.dispatchEvent(new CustomEvent('flash-message', {
                    detail: {
                        message: response.data.message,
                        type: 'warning'
                    }
                }));
                form.content = '';
                emit('success', response.data);
            } else {
                showSuccessMessage.value = true;
                setTimeout(() => {
                    showSuccessMessage.value = false;
                    form.content = '';
                    emit('success', response.data);
                }, 2000);

                 window.dispatchEvent(new CustomEvent('flash-message', {
                    detail: {
                        message: response.data.message,
                        type: 'success'
                    }
                }));
            }
        }
    } catch (error) {
        if (error.response && error.response.status === 422) {
            errors.content = error.response.data.errors.content?.[0] || 'Erro de validação.';
        } else if (error.response && error.response.status === 429) {
            const msg = error.response.data.errors?.content?.[0] || error.response.data.message || 'Muitas tentativas. Tente novamente mais tarde.';
            errors.content = msg;
        } else if (error.response && error.response.status === 401) {
            window.dispatchEvent(new Event('open-auth-modal'));
        } else {
            errors.content = 'Erro ao enviar comentário. Tente novamente.';
        }
    } finally {
        loading.value = false;
    }
};
</script>
