import { createApp } from 'vue';
import CommentsSection from './Components/Comments/CommentsSection.vue';

const mountEl = document.getElementById('comments-app');

if (mountEl) {
    const app = createApp(CommentsSection, {
        initialComments: JSON.parse(mountEl.dataset.comments || '[]'),
        articleSlug: mountEl.dataset.articleSlug,
        submitUrl: mountEl.dataset.submitUrl,
        user: mountEl.dataset.user ? JSON.parse(mountEl.dataset.user) : null,
        googleClientId: mountEl.dataset.googleClientId || '',
        paginationLinks: mountEl.dataset.paginationLinks || ''
    });

    app.mount('#comments-app');
}
