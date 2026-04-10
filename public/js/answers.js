const ANSWERS_BASE_PATH = (typeof window !== 'undefined' && window.BASE_PATH)
    ? (window.BASE_PATH.endsWith('/') ? window.BASE_PATH : `${window.BASE_PATH}/`)
    : (document.querySelector('base')?.href || './');

document.addEventListener('DOMContentLoaded', () => {
    const answerForm = document.getElementById('answerForm');
    const answersPanel = document.getElementById('answersPanel');
    const answersContainer = answersPanel ? answersPanel.querySelector('.comments-container') : null;
    const toggleBtn = document.querySelector('.toggle-answers-btn[data-targets="answersPanel"]');
    const closeBtn = document.getElementById('closeAnswersBtn');

    if (!answerForm || !answersPanel || !answersContainer || !toggleBtn) return;

    const questionId = answerForm.querySelector('[name="question_id"]')?.value;
    const countSpan = toggleBtn.querySelector('span');

    const getCount = () => {
        const txt = countSpan?.textContent || '';
        const m = txt.match(/View all (\d+) answers/);
        return m ? parseInt(m[1], 10) : 0;
    };

    const setCount = (count) => {
        const label = `View all ${Math.max(0, count)} answers`;
        toggleBtn.dataset.labelShow = label;
        if (countSpan) countSpan.textContent = label;
    };

    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    };

    const getTimeAgo = (timestamp) => {
        const date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) return timestamp || '';
        const diffMs = Date.now() - date.getTime();
        const mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return `${mins}m ago`;
        const hours = Math.floor(mins / 60);
        if (hours < 24) return `${hours}h ago`;
        const days = Math.floor(hours / 24);
        if (days < 7) return `${days}d ago`;
        return date.toLocaleDateString();
    };

    const renderAnswer = (answer, level = 0) => {
        const id = Number(answer.answer_id || 0);
        const author = `${answer.first_name || ''} ${answer.last_name || ''}`.trim() || 'Unknown';
        const profile = answer.profile_picture || `${ANSWERS_BASE_PATH}public/images/default-avatar.png`;
        const currentUserId = Number(window.USER_ID || 0);
        const canModerate = currentUserId === Number(answer.user_id || 0);
        const replies = Array.isArray(answer.replies) ? answer.replies.map(r => renderAnswer(r, level + 1)).join('') : '';
        const replyStyle = level > 0 ? ' style="margin-left: 40px;"' : '';

        return `
            <div class="comment${level > 0 ? ' reply' : ''}" data-answer-id="${id}" data-level="${level}"${replyStyle}>
                <div class="comment-header-info">
                    <img src="${profile}" class="comment-avatar" alt="${escapeHtml(author)}">
                    <span class="comment-author">${escapeHtml(author)}</span>
                    <span class="comment-time">${escapeHtml(getTimeAgo(answer.created_at || ''))}</span>
                    ${answer.is_accepted ? '<span class="answer-badge">Accepted</span>' : ''}
                </div>
                <div class="comment-text">${escapeHtml(answer.content || '').replace(/\n/g, '<br>')}</div>
                <div class="comment-actions">
                    ${level === 0 ? `<button class="comment-action reply-btn" data-answer-id="${id}"><i class="fas fa-reply"></i><span>Reply</span></button>` : ''}
                    ${canModerate ? `<button class="comment-action edit-answer-btn" data-answer-id="${id}">Edit</button><button class="comment-action delete-answer-btn" data-answer-id="${id}">Delete</button>` : ''}
                </div>
                ${level === 0 ? `
                <div class="reply-form" id="reply-form-${id}">
                    <div class="reply-input-container">
                        <input type="text" class="reply-input" placeholder="Write a reply..." data-answer-id="${id}">
                        <button class="reply-submit-btn" data-answer-id="${id}"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
                ` : ''}
                ${replies ? `<div class="comment-replies">${replies}</div>` : ''}
            </div>
        `;
    };

    const insertAnswer = (answer) => {
        const parentId = Number(answer.parent_answer_id || 0);
        const parentLevel = parentId > 0 ? Number(document.querySelector(`.comment[data-answer-id="${parentId}"]`)?.dataset.level || 0) + 1 : 0;
        const html = renderAnswer(answer, parentLevel);
        if (parentId > 0) {
            const parent = answersContainer.querySelector(`.comment[data-answer-id="${parentId}"]`);
            if (parent) {
                let repliesWrap = parent.querySelector('.comment-replies');
                if (!repliesWrap) {
                    repliesWrap = document.createElement('div');
                    repliesWrap.className = 'comment-replies';
                    parent.appendChild(repliesWrap);
                }
                repliesWrap.insertAdjacentHTML('beforeend', html);
                return;
            }
        }
        answersContainer.insertAdjacentHTML('beforeend', html);
    };

    const submitAnswer = async (content, parentAnswerId = '') => {
        const formData = new FormData();
        formData.append('sub_action', 'createAnswer');
        formData.append('question_id', questionId);
        formData.append('content', content);
        if (parentAnswerId) formData.append('parent_answer_id', parentAnswerId);

        const response = await fetch(ANSWERS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
            method: 'POST',
            body: formData
        });
        return response.json();
    };

    const openAnswers = () => {
        answersPanel.classList.remove('collapsed');
        answersPanel.classList.add('active');
        toggleBtn.setAttribute('aria-expanded', 'true');
    };

    const closeAnswers = () => {
        answersPanel.classList.add('collapsed');
        answersPanel.classList.remove('active');
        toggleBtn.setAttribute('aria-expanded', 'false');
    };

    const initialExpanded = new URLSearchParams(window.location.search).get('open_answers') === '1';
    if (initialExpanded) openAnswers();

    toggleBtn.addEventListener('click', () => {
        if (answersPanel.classList.contains('collapsed')) {
            openAnswers();
        } else {
            closeAnswers();
        }
    });

    closeBtn?.addEventListener('click', closeAnswers);

    answerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const contentField = answerForm.querySelector('textarea[name="content"]');
        const parentField = answerForm.querySelector('[name="parent_answer_id"]');
        const content = contentField?.value.trim() || '';
        if (!content) return;

        const result = await submitAnswer(content, parentField?.value || '');
        if (result.success && result.answer) {
            insertAnswer(result.answer);
            contentField.value = '';
            if (parentField) parentField.value = '';
            if (!parentField?.value) {
                setCount(getCount() + 1);
            }
        } else {
            alert(result.message || 'Failed to post answer');
        }
    });

    document.addEventListener('click', async (event) => {
        const replyBtn = event.target.closest('.reply-btn');
        if (replyBtn) {
            event.preventDefault();
            const answerId = replyBtn.dataset.answerId;
            const replyForm = document.getElementById(`reply-form-${answerId}`);
            if (replyForm) replyForm.classList.toggle('active');
            return;
        }

        const replySubmit = event.target.closest('.reply-submit-btn');
        if (replySubmit) {
            event.preventDefault();
            const answerId = replySubmit.dataset.answerId;
            const replyForm = document.getElementById(`reply-form-${answerId}`);
            const input = replyForm?.querySelector('.reply-input');
            const content = input?.value.trim() || '';
            if (!content) return;

            const result = await submitAnswer(content, answerId);
            if (result.success && result.answer) {
                insertAnswer(result.answer);
                if (input) input.value = '';
                if (replyForm) replyForm.classList.remove('active');
            } else {
                alert(result.message || 'Failed to post reply');
            }
            return;
        }

        const editBtn = event.target.closest('.edit-answer-btn');
        if (editBtn) {
            event.preventDefault();
            const answerId = editBtn.dataset.answerId;
            const answerEl = document.querySelector(`.comment[data-answer-id="${answerId}"]`);
            const textEl = answerEl?.querySelector('.comment-text');
            const current = (textEl?.textContent || '').trim();
            const next = window.prompt('Edit answer:', current);
            if (next === null) return;

            const formData = new FormData();
            formData.append('sub_action', 'editAnswer');
            formData.append('answer_id', answerId);
            formData.append('content', next.trim());

            const response = await fetch(ANSWERS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success && textEl) {
                textEl.innerHTML = escapeHtml(next.trim()).replace(/\n/g, '<br>');
            } else {
                alert(result.message || 'Failed to update answer');
            }
            return;
        }

        const deleteBtn = event.target.closest('.delete-answer-btn');
        if (deleteBtn) {
            event.preventDefault();
            const answerId = deleteBtn.dataset.answerId;
            if (!confirm('Delete this answer?')) return;

            const answerEl = document.querySelector(`.comment[data-answer-id="${answerId}"]`);
            const isTopLevel = !!answerEl && !answerEl.closest('.comment.replies');

            const formData = new FormData();
            formData.append('sub_action', 'deleteAnswer');
            formData.append('answer_id', answerId);

            const response = await fetch(ANSWERS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                if (answerEl) answerEl.remove();
                if (isTopLevel) setCount(Math.max(0, getCount() - 1));
            } else {
                alert(result.message || 'Failed to delete answer');
            }
        }
    });
});