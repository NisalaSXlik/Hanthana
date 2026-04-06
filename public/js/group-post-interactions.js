// Group Post Interactions - Upvote, Downvote, Bookmark
document.addEventListener('DOMContentLoaded', function() {
    const getCountElement = (button) => {
        if (!button) return null;
        return button.querySelector('.action-count, .interaction-count, small');
    };

    const setBookmarkVisualState = (button, isBookmarked) => {
        if (!button) return;

        const icon = button.querySelector('i');
        button.classList.toggle('bookmarked', !!isBookmarked);
        button.setAttribute('aria-pressed', isBookmarked ? 'true' : 'false');

        if (!icon) return;

        icon.classList.toggle('bookmarked', !!isBookmarked);
        icon.classList.remove('uil-bookmark', 'uil-bookmark-full', 'uis-bookmark', 'uil', 'uis');

        if (isBookmarked) {
            icon.classList.add('uis', 'uis-bookmark');
        } else {
            icon.classList.add('uil', 'uil-bookmark');
        }
    };

    const updateBookmarkStateInKnownLists = (postId, isBookmarked) => {
        const collections = [
            window.PERSONAL_POSTS,
            window.GROUP_POSTS,
            window.SAVED_POSTS,
            window.POSTS,
        ];

        collections.forEach((collection) => {
            if (!Array.isArray(collection)) return;
            collection.forEach((post) => {
                if (!post) return;
                const candidateId = parseInt(post.post_id ?? post.postId ?? 0, 10);
                if (candidateId !== postId) return;

                post.is_bookmarked = isBookmarked ? 1 : 0;
                post.isBookmarked = !!isBookmarked;
            });
        });
    };

    const syncBookmarkStateAcrossPage = (postId, isBookmarked) => {
        const normalizedPostId = parseInt(postId, 10);
        if (!normalizedPostId) return;

        if (typeof window.__hanthanaSetBookmarkState === 'function') {
            window.__hanthanaSetBookmarkState(normalizedPostId, !!isBookmarked);
            return;
        }

        const selector = `.bookmark-btn[data-post-id="${normalizedPostId}"]`;
        document.querySelectorAll(selector).forEach((button) => {
            setBookmarkVisualState(button, !!isBookmarked);
        });

        updateBookmarkStateInKnownLists(normalizedPostId, !!isBookmarked);

        window.dispatchEvent(new CustomEvent('hanthana:bookmark-changed', {
            detail: {
                postId: normalizedPostId,
                bookmarked: !!isBookmarked,
            },
        }));
    };
    
    // ===== UPVOTE & DOWNVOTE FUNCTIONALITY =====
    document.addEventListener('click', async function(e) {
        const voteBtn = e.target.closest('.upvote-btn, .downvote-btn');
        if (!voteBtn) return;

        e.preventDefault();

        const postCard = voteBtn.closest('.group-post-card');
        if (!postCard) return;

        const postId = postCard.dataset.postId;
        const voteType = voteBtn.classList.contains('upvote-btn') ? 'upvote' : 'downvote';

        if (!postId) {
            console.error('Post ID not found');
            return;
        }

        // Get both vote buttons and their counts
        const upvoteBtn = postCard.querySelector('.upvote-btn');
        const downvoteBtn = postCard.querySelector('.downvote-btn');
        const upvoteCount = getCountElement(upvoteBtn);
        const downvoteCount = getCountElement(downvoteBtn);

        // Visual feedback - disable during request
        voteBtn.style.opacity = '0.6';
        voteBtn.style.pointerEvents = 'none';

        try {
            const formData = new FormData();
            formData.append('sub_action', 'vote');
            formData.append('post_id', postId);
            formData.append('vote_type', voteType);

            const response = await fetch(BASE_PATH + 'index.php?controller=Vote&action=handleAjax', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Update UI based on action
                const upvoteIcon = upvoteBtn?.querySelector('i');
                const downvoteIcon = downvoteBtn?.querySelector('i');

                if (data.action === 'added' || data.action === 'changed') {
                    if (voteType === 'upvote') {
                        upvoteIcon?.classList.add('liked');
                        downvoteIcon?.classList.remove('liked');
                    } else {
                        downvoteIcon?.classList.add('liked');
                        upvoteIcon?.classList.remove('liked');
                    }
                } else if (data.action === 'removed') {
                    if (voteType === 'upvote') {
                        upvoteIcon?.classList.remove('liked');
                    } else {
                        downvoteIcon?.classList.remove('liked');
                    }
                }

                // Update counts with animation
                if (upvoteCount && data.upvote_count !== undefined) {
                    animateCount(upvoteCount, data.upvote_count);
                }
                if (downvoteCount && data.downvote_count !== undefined) {
                    animateCount(downvoteCount, data.downvote_count);
                }

                // Show feedback
                if (window.showToast) {
                    const message = data.action === 'removed' ? 'Vote removed' : 
                                  voteType === 'upvote' ? '✓ Upvoted' : '✓ Downvoted';
                    window.showToast(message, 'success');
                }
            } else {
                if (window.showToast) {
                    window.showToast(data.message || '✗ Vote failed', 'error');
                }
            }
        } catch (error) {
            console.error('Vote error:', error);
            if (window.showToast) {
                window.showToast('✗ Network error', 'error');
            }
        } finally {
            // Re-enable button
            voteBtn.style.opacity = '1';
            voteBtn.style.pointerEvents = 'auto';
        }
    });

    // ===== BOOKMARK FUNCTIONALITY =====
    document.addEventListener('click', async function(e) {
        const bookmarkBtn = e.target.closest('.bookmark-btn');
        if (!bookmarkBtn) return;

        e.preventDefault();

        const postCard = bookmarkBtn.closest('.group-post-card');
        if (!postCard) return;

        const postId = postCard.dataset.postId;

        if (!postId) {
            console.error('Post ID not found');
            return;
        }

        // Visual feedback
        const icon = bookmarkBtn.querySelector('i');
        const wasBookmarked = icon?.classList.contains('bookmarked') || bookmarkBtn.classList.contains('bookmarked');
        const nextState = !wasBookmarked;

        // Optimistic UI update across all visible copies.
        syncBookmarkStateAcrossPage(postId, nextState);

        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('action', wasBookmarked ? 'remove' : 'add');

            const response = await fetch(BASE_PATH + 'index.php?controller=Posts&action=bookmark', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                syncBookmarkStateAcrossPage(postId, !!data.bookmarked);
                if (window.showToast) {
                    const message = data.bookmarked ? '✓ Post bookmarked' : 'Bookmark removed';
                    window.showToast(message, 'success');
                }
            } else {
                // Revert on failure
                syncBookmarkStateAcrossPage(postId, wasBookmarked);
                if (window.showToast) {
                    window.showToast(data.message || '✗ Bookmark failed', 'error');
                }
            }
        } catch (error) {
            console.error('Bookmark error:', error);
            // Revert on error
            syncBookmarkStateAcrossPage(postId, wasBookmarked);
            if (window.showToast) {
                window.showToast('✗ Network error', 'error');
            }
        }
    });

    // ===== HELPER FUNCTION: ANIMATE COUNT =====
    function animateCount(element, newValue) {
        const currentValue = parseInt(element.textContent) || 0;
        
        if (currentValue === newValue) return;

        // Add animation class
        element.style.transition = 'all 0.3s ease';
        
        if (newValue > currentValue) {
            element.style.color = 'var(--color-primary)';
            element.style.transform = 'scale(1.2)';
        } else {
            element.style.transform = 'scale(0.8)';
        }

        // Update value
        element.textContent = newValue;

        // Reset animation
        setTimeout(() => {
            element.style.color = '';
            element.style.transform = 'scale(1)';
        }, 300);
    }

    // ===== INITIALIZE VOTED STATES ON LOAD =====
    function initializeVoteStates() {
        document.querySelectorAll('.group-post-card').forEach(postCard => {
            const postId = postCard.dataset.postId;
            if (!postId) return;

            // Check if user has voted (you can fetch this from server or data attributes)
            // For now, we'll rely on server-side rendering to add 'voted' class
        });
    }

    initializeVoteStates();
});
