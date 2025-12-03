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

    const composeQuestionContent = () => {
        const sections = [
            { label: 'Problem', value: templateFields.problem?.value?.trim() },
            { label: 'Context', value: templateFields.context?.value?.trim() },
            { label: 'Attempts', value: templateFields.attempts?.value?.trim() },
            { label: 'Expected Outcome', value: templateFields.expected?.value?.trim() },
        ];

        return sections
            .filter((section) => section.value)
            .map((section) => `${section.label}:
${section.value}`)
            .join('\n\n');
    };

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
        const composedContent = composeQuestionContent();
        if (!composedContent.trim()) {
            alert('Please describe your problem before posting.');
            return;
        }

        const formData = new FormData(askQuestionForm);
        formData.set('content', composedContent);
        formData.delete('problem_statement');
        formData.delete('context_details');
        formData.delete('attempts');
        formData.delete('expected_outcome');
        formData.append('sub_action', 'createQuestion');
        
        try {
            const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=view&id=' + result.question_id;
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
            const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
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
                const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
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
                const response = await fetch(QUESTIONS_BASE_PATH + 'index.php?controller=Popular&action=handleAjax', {
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

    // Toggle answers dropdown like post comments
    document.querySelectorAll('.toggle-answers-btn').forEach((btn) => {
        const targetIds = (btn.dataset.targets || btn.dataset.target || '')
            .split(' ')
            .map((id) => id.trim())
            .filter(Boolean);
        const targets = targetIds
            .map((id) => document.getElementById(id))
            .filter(Boolean);
        if (!targets.length) return;

        const labelNode = btn.querySelector('span');
        const labelShow = btn.dataset.labelShow || 'Show';
        const labelHide = btn.dataset.labelHide || 'Hide';

        const setState = (expanded) => {
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            targets.forEach((target) => target.classList.toggle('collapsed', !expanded));
            if (labelNode) {
                labelNode.textContent = expanded ? labelHide : labelShow;
            }
        };

        const initialExpanded = targets.some((target) => !target.classList.contains('collapsed'));
        setState(initialExpanded);

        btn.addEventListener('click', () => {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            setState(!expanded);
        });
    });
});
