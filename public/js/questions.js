const QUESTIONS_BASE_PATH = (typeof window !== 'undefined' && window.BASE_PATH)
    ? (window.BASE_PATH.endsWith('/') ? window.BASE_PATH : `${window.BASE_PATH}/`)
    : (document.querySelector('base')?.href || './');

document.addEventListener('DOMContentLoaded', function() {
    const askQuestionBtn = document.getElementById('askQuestionBtn');
    const askQuestionModal = document.getElementById('askQuestionModal');
    const closeModal = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const askQuestionForm = document.getElementById('askQuestionForm');
    const answerForm = document.getElementById('answerForm');
    const searchInput = document.getElementById('searchInput');
    const templateChips = document.querySelectorAll('.template-chip');
    const titleInput = document.getElementById('questionTitleInput');
    const templatedTextareas = askQuestionForm ? askQuestionForm.querySelectorAll('textarea[data-maxlength]') : [];
    const templateFields = askQuestionForm ? {
        problem: askQuestionForm.querySelector('[name="problem_statement"]'),
        context: askQuestionForm.querySelector('[name="context_details"]'),
        attempts: askQuestionForm.querySelector('[name="attempts"]'),
        expected: askQuestionForm.querySelector('[name="expected_outcome"]'),
    } : {};

    const updateCharCount = (field) => {
        if (!field || !askQuestionForm) return;
        const target = askQuestionForm.querySelector(`.char-count[data-for="${field.name}"]`);
        if (!target) return;
        const limit = parseInt(field.dataset.maxlength || field.getAttribute('maxlength') || '0', 10);
        const length = field.value.trim().length;
        target.textContent = limit ? `${length} / ${limit}` : `${length} chars`;
        target.classList.toggle('over-limit', limit && length > limit);
    };

    templatedTextareas.forEach((textarea) => {
        updateCharCount(textarea);
        textarea.addEventListener('input', () => updateCharCount(textarea));
    });

    const composeQuestionContent = () => templateFields.problem?.value?.trim() || '';

    const applyTemplateChip = (chip, forceValue = false) => {
        if (!chip || !titleInput) return;
        templateChips.forEach((btn) => btn.classList.remove('active'));
        chip.classList.add('active');
        const prefix = chip.dataset.templatePrefix || '';
        titleInput.setAttribute('placeholder', prefix ? `${prefix} ...` : 'Summarize your question');
        const shouldPrefill = forceValue || !titleInput.value.trim() || titleInput.dataset.prefilled === 'true';
        if (shouldPrefill && prefix) {
            titleInput.value = `${prefix} `;
            titleInput.dataset.prefilled = 'true';
        }
        if (!prefix) {
            titleInput.dataset.prefilled = 'false';
        }
    };

    templateChips.forEach((chip, index) => {
        chip.addEventListener('click', () => applyTemplateChip(chip, true));
        if (index === 0 && !chip.classList.contains('active')) {
            chip.classList.add('active');
        }
    });

    if (templateChips.length) {
        applyTemplateChip(document.querySelector('.template-chip.active') || templateChips[0], true);
    }

    titleInput?.addEventListener('input', () => {
        if (titleInput.value.trim().length) {
            titleInput.dataset.prefilled = 'false';
        }
    });

    const resetQuestionTemplate = () => {
        if (!askQuestionForm) return;
        askQuestionForm.reset();
        templatedTextareas.forEach((textarea) => updateCharCount(textarea));
        if (templateChips.length) {
            applyTemplateChip(templateChips[0], true);
        } else if (titleInput) {
            titleInput.dataset.prefilled = 'false';
        }
    };
    
    // Open modal
    askQuestionBtn?.addEventListener('click', () => {
        resetQuestionTemplate();
        askQuestionModal?.classList.add('active');
    });
    
    // Close modal
    const hideAskModal = () => {
        askQuestionModal?.classList.remove('active');
    };

    closeModal?.addEventListener('click', () => {
        hideAskModal();
    });

    cancelBtn?.addEventListener('click', () => {
        hideAskModal();
    });
    
    // Close on outside click
    askQuestionModal?.addEventListener('click', (e) => {
        if (e.target === askQuestionModal) {
            hideAskModal();
        }
    });
    
    // Submit question
    askQuestionForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const problem = templateFields.problem?.value?.trim() || '';
        if (!problem) {
            alert('Please describe your problem before posting.');
            return;
        }

        const formData = new FormData(askQuestionForm);
        formData.set('content', problem);
        formData.delete('problem_statement');
        formData.delete('context_details');
        formData.delete('attempts');
        formData.delete('expected_outcome');
        formData.append('sub_action', 'createQuestion');

        try {
            const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=QnA&action=handleAjax', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = QUESTIONS_BASE_PATH + 'index.php?controller=QnA&action=view&id=' + result.question_id;
            } else {
                alert(result.message || 'Failed to post question');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    });
    
    // Submit answer
    answerForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(answerForm);
        formData.append('sub_action', 'createAnswer');
        
        try {
            const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=QnA&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Reload to show new answer
                location.reload();
            } else {
                alert(result.message || 'Failed to post answer');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    });
    
    // Vote on questions
    document.querySelectorAll('.vote-btn[data-question-id]').forEach(btn => {
        btn.addEventListener('click', async function() {
            const questionId = this.dataset.questionId;
            const voteType = this.classList.contains('upvote') ? 'upvote' : 'downvote';
            
            const formData = new FormData();
            formData.append('sub_action', 'voteQuestion');
            formData.append('question_id', questionId);
            formData.append('vote_type', voteType);
            
            try {
                const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=QnA&action=handleAjax', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Reload to update vote counts
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
    
    // Vote on answers
    document.querySelectorAll('.vote-btn[data-answer-id]').forEach(btn => {
        btn.addEventListener('click', async function() {
            const answerId = this.dataset.answerId;
            const voteType = this.classList.contains('upvote') ? 'upvote' : 'downvote';
            
            const formData = new FormData();
            formData.append('sub_action', 'voteAnswer');
            formData.append('answer_id', answerId);
            formData.append('vote_type', voteType);
            
            try {
                const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=QnA&action=handleAjax', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Reload to update vote counts
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
    
    // Question search (client-side filter + optional server refresh on Enter)
    const questionCards = Array.from(document.querySelectorAll('.question-card'));
    const searchEmptyState = document.getElementById('questionSearchEmpty');
    const initialSearchValue = searchInput?.value?.trim() || new URLSearchParams(window.location.search).get('search') || '';

    const applyQuestionSearch = (rawQuery) => {
        if (!questionCards.length) return;
        const query = (rawQuery || '').trim().toLowerCase();
        let matches = 0;

        questionCards.forEach((card) => {
            const haystack = (card.dataset.searchText || '').toLowerCase();
            const isMatch = !query || haystack.includes(query);
            card.style.display = isMatch ? '' : 'none';
            card.classList.toggle('question-card--hidden', !isMatch);
            if (isMatch) matches += 1;
        });

        if (searchEmptyState) {
            const shouldShowEmpty = query.length > 0 && matches === 0;
            searchEmptyState.style.display = shouldShowEmpty ? 'flex' : 'none';
        }
    };

    if (searchInput) {
        searchInput.value = initialSearchValue;
        applyQuestionSearch(initialSearchValue);
    }

    searchInput?.addEventListener('input', (event) => {
        applyQuestionSearch(event.target.value);
    });

    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            const query = event.currentTarget.value.trim();
            const url = new URL(window.location.href);
            if (query) {
                url.searchParams.set('search', query);
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }
    });


    // Question card menu (3 dots)
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.question-menu-trigger');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();

            const wrap = trigger.closest('.question-menu-wrap');
            document.querySelectorAll('.question-menu-wrap.open').forEach((item) => {
                if (item !== wrap) item.classList.remove('open');
            });
            wrap?.classList.toggle('open');
            return;
        }

        const menuItem = event.target.closest('.question-menu-item');
        if (menuItem) {
            const wrap = menuItem.closest('.question-menu-wrap');
            if (wrap) {
                wrap.classList.remove('open');
            }
        }

        if (!event.target.closest('.question-menu-wrap')) {
            document.querySelectorAll('.question-menu-wrap.open').forEach((item) => item.classList.remove('open'));
        }
    });

    // Edit question from card menu
    document.querySelectorAll('.edit-question').forEach((btn) => {
        btn.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const questionId = btn.dataset.questionId;
            const card = btn.closest('.question-card');
            if (!questionId || !card) return;

            const currentTitle = (card.querySelector('.question-title a')?.textContent || '').trim();
            const currentContent = (card.querySelector('.question-excerpt')?.textContent || '').trim();

            const newTitle = window.prompt('Edit question title:', currentTitle);
            if (newTitle === null) return;

            const newContent = window.prompt('Edit question content:', currentContent);
            if (newContent === null) return;

            const formData = new FormData();
            formData.append('sub_action', 'editQuestion');
            formData.append('question_id', questionId);
            formData.append('title', newTitle.trim());
            formData.append('content', newContent.trim());
            formData.append('category', 'General');
            formData.append('topics', '');

            try {
                const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to update question');
                }
            } catch (error) {
                console.error('Edit question failed:', error);
                alert('Failed to update question');
            }
        });
    });

    // Delete question from card menu
    document.querySelectorAll('.delete-question').forEach((btn) => {
        btn.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const questionId = btn.dataset.questionId;
            if (!questionId) return;
            if (!window.confirm('Delete this question?')) return;

            const formData = new FormData();
            formData.append('sub_action', 'deleteQuestion');
            formData.append('question_id', questionId);

            try {
                const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to delete question');
                }
            } catch (error) {
                console.error('Delete question failed:', error);
                alert('Failed to delete question');
            }
        });
    });

    const inlineAnswerToggles = document.querySelectorAll('.toggle-inline-answers');
    const loadedPanels = new Set();

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

    const renderAnswerNode = (answer, level = 0) => {
        const id = Number(answer.answer_id || 0);
        const author = `${answer.first_name || ''} ${answer.last_name || ''}`.trim() || 'Unknown';
        const profile = answer.profile_picture || `${QUESTIONS_BASE_PATH}public/images/default-avatar.png`;
        const currentUserId = Number(window.USER_ID || 0);
        const canModerate =
            currentUserId === Number(answer.user_id || 0) ||
            currentUserId === Number(answer.question_user_id || 0);
        const replies = Array.isArray(answer.replies) ? answer.replies.map((reply) => renderAnswerNode(reply, level + 1)).join('') : '';
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
                    ${level === 0 ? `<button class="comment-action reply-btn" data-answer-id="${id}"><i class="uil uil-corner-up-left"></i><span>Reply</span></button>` : '<span></span>'}
                    <div class="question-menu-wrap answer-menu-wrap">
                        <button type="button" class="question-menu-trigger answer-menu-trigger" aria-label="Answer menu">
                            <i class="uil uil-ellipsis-h"></i>
                        </button>
                        <div class="question-menu">
                            ${canModerate ? `<button type="button" class="question-menu-item edit-answer-btn" data-answer-id="${id}"><i class="uil uil-edit"></i> Edit</button><button type="button" class="question-menu-item delete-answer-btn" data-answer-id="${id}"><i class="uil uil-trash-alt"></i> Delete</button>` : ''}
                            <button type="button" class="question-menu-item report-trigger" data-report-type="answer" data-target-id="${id}" data-target-label="${escapeHtml(author)} answer">
                                <i class="uil uil-exclamation-circle"></i> Report
                            </button>
                        </div>
                    </div>
                </div>
                ${level === 0 ? `
                <div class="reply-form" id="reply-form-${id}">
                    <div class="reply-input-container">
                        <input type="text" class="reply-input" placeholder="Write a reply..." data-answer-id="${id}">
                        <button class="reply-submit-btn" data-answer-id="${id}"><i class="uil uil-message"></i></button>
                    </div>
                </div>
                ` : ''}
                ${replies ? `<div class="comment-replies">${replies}</div>` : ''}
            </div>
        `;
    };

    const setAnswerLabel = (toggleBtn, count) => {
        const label = `${Math.max(0, count)} answers`;
        toggleBtn.dataset.answerCount = String(Math.max(0, count));
        toggleBtn.innerHTML = `<i class="uil uil-comment"></i> ${label}`;
    };

    const getAnswerCountFromLabel = (toggleBtn) => {
        const stored = Number(toggleBtn.dataset.answerCount || 0);
        if (!Number.isNaN(stored) && stored >= 0) return stored;
        const text = toggleBtn.textContent || '';
        const match = text.match(/(\d+)\s+answers?/i);
        return match ? parseInt(match[1], 10) : 0;
    };

    const fetchAnswers = async (questionId) => {
        const formData = new FormData();
        formData.append('sub_action', 'getAnswers');
        formData.append('question_id', questionId);

        const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch answers (${response.status})`);
        }

        let data;
        try {
            data = await response.json();
        } catch (error) {
            throw new Error('Invalid server response while loading answers');
        }

        return data;
    };

    const submitAnswer = async (questionId, content, parentAnswerId = '') => {
        const formData = new FormData();
        formData.append('sub_action', 'createAnswer');
        formData.append('question_id', questionId);
        formData.append('content', content);
        if (parentAnswerId) formData.append('parent_answer_id', parentAnswerId);

        const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
            method: 'POST',
            body: formData
        });
        return response.json();
    };

    const ensureRepliesWrapper = (parentEl) => {
        let wrap = parentEl.querySelector(':scope > .comment-replies');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'comment-replies';
            parentEl.appendChild(wrap);
        }
        return wrap;
    };

    const insertAnswerInPanel = (panelEl, answer) => {
        const container = panelEl.querySelector('.inline-answers-list');
        if (!container) return;

        const placeholder = container.querySelector('.no-comments');
        if (placeholder) placeholder.remove();

        const parentId = Number(answer.parent_answer_id || 0);
        const html = renderAnswerNode(answer, parentId > 0 ? Number(panelEl.querySelector(`.comment[data-answer-id="${parentId}"]`)?.dataset.level || 0) + 1 : 0);

        if (parentId > 0) {
            const parent = panelEl.querySelector(`.comment[data-answer-id="${parentId}"]`);
            if (parent) {
                const repliesWrap = ensureRepliesWrapper(parent);
                repliesWrap.insertAdjacentHTML('beforeend', html);
                return;
            }
        }

        container.insertAdjacentHTML('beforeend', html);
    };

    const loadInlineAnswers = async (panelEl) => {
        const questionId = panelEl.dataset.questionId;
        const listEl = panelEl.querySelector('.inline-answers-list');
        if (!questionId || !listEl) return false;

        listEl.innerHTML = '<div class="no-comments">Loading answers...</div>';
        let result;
        try {
            result = await fetchAnswers(questionId);
        } catch (error) {
            console.error('Failed to load answers:', error);
            listEl.innerHTML = '<div class="no-comments">Failed to load answers. Please try again.</div>';
            return false;
        }

        if (!result.success) {
            listEl.innerHTML = '<div class="no-comments">Failed to load answers.</div>';
            return false;
        }

        const answers = Array.isArray(result.answers) ? result.answers : [];
        if (!answers.length) {
            listEl.innerHTML = '<div class="no-comments">No answers yet. Be the first to answer!</div>';
            return true;
        }

        listEl.innerHTML = answers.map((answer) => renderAnswerNode(answer, 0)).join('');
        return true;
    };

    inlineAnswerToggles.forEach((toggleBtn) => {
        toggleBtn.addEventListener('click', async (event) => {
            event.preventDefault();
            const targetId = toggleBtn.dataset.target;
            const panel = targetId ? document.getElementById(targetId) : null;
            if (!panel) return;

            const isCollapsed = panel.classList.contains('collapsed');
            if (isCollapsed) {
                panel.classList.remove('collapsed');
                panel.classList.add('active');
                panel.setAttribute('aria-hidden', 'false');
                toggleBtn.setAttribute('aria-expanded', 'true');

                if (!loadedPanels.has(targetId)) {
                    const loaded = await loadInlineAnswers(panel);
                    if (loaded) {
                        loadedPanels.add(targetId);
                    }
                }
            } else {
                panel.classList.add('collapsed');
                panel.classList.remove('active');
                panel.setAttribute('aria-hidden', 'true');
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.querySelectorAll('.close-inline-answers').forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            const panel = btn.closest('.inline-answers-panel');
            if (!panel) return;
            panel.classList.add('collapsed');
            panel.classList.remove('active');
            panel.setAttribute('aria-hidden', 'true');

            const panelId = panel.id;
            const toggleBtn = document.querySelector(`.toggle-inline-answers[data-target="${panelId}"]`);
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        });
    });

    document.querySelectorAll('.inline-answer-form').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const panel = form.closest('.inline-answers-panel');
            if (!panel) return;

            const questionId = form.querySelector('[name="question_id"]')?.value;
            const parentField = form.querySelector('[name="parent_answer_id"]');
            const contentField = form.querySelector('[name="content"]');
            const content = contentField?.value.trim() || '';
            if (!questionId || !content) return;

            const result = await submitAnswer(questionId, content, parentField?.value || '');
            if (!result.success || !result.answer) {
                alert(result.message || 'Failed to post answer');
                return;
            }

            insertAnswerInPanel(panel, result.answer);
            if (contentField) contentField.value = '';
            if (parentField) parentField.value = '';

            if (!result.answer.parent_answer_id) {
                const toggleBtn = document.querySelector(`.toggle-inline-answers[data-target="${panel.id}"]`);
                if (toggleBtn) {
                    const count = getAnswerCountFromLabel(toggleBtn);
                    setAnswerLabel(toggleBtn, count + 1);
                }
            }
        });
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
            const panel = replySubmit.closest('.inline-answers-panel');
            const replyForm = document.getElementById(`reply-form-${answerId}`);
            const input = replyForm?.querySelector('.reply-input');
            const questionId = panel?.dataset.questionId;
            const content = input?.value.trim() || '';
            if (!questionId || !content) return;

            const result = await submitAnswer(questionId, content, answerId);
            if (!result.success || !result.answer) {
                alert(result.message || 'Failed to post reply');
                return;
            }

            if (panel) insertAnswerInPanel(panel, result.answer);
            if (input) input.value = '';
            if (replyForm) replyForm.classList.remove('active');
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

            const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
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
            if (!window.confirm('Delete this answer?')) return;

            const answerEl = document.querySelector(`.comment[data-answer-id="${answerId}"]`);
            const isReply = !!answerEl?.closest('.comment-replies');

            const formData = new FormData();
            formData.append('sub_action', 'deleteAnswer');
            formData.append('answer_id', answerId);

            const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (!result.success) {
                alert(result.message || 'Failed to delete answer');
                return;
            }

            const panel = answerEl?.closest('.inline-answers-panel');
            if (answerEl) answerEl.remove();

            if (!isReply && panel) {
                const toggleBtn = document.querySelector(`.toggle-inline-answers[data-target="${panel.id}"]`);
                if (toggleBtn) {
                    const count = getAnswerCountFromLabel(toggleBtn);
                    setAnswerLabel(toggleBtn, Math.max(0, count - 1));
                }
            }
        }
    });

});
