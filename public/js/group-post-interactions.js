// Group Post Interactions - Upvote, Downvote, Bookmark
document.addEventListener('DOMContentLoaded', function() {
    
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
        const upvoteCount = upvoteBtn?.querySelector('small');
        const downvoteCount = downvoteBtn?.querySelector('small');

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

        // Optimistic UI update
        if (icon) {
            icon.classList.toggle('bookmarked');
            icon.classList.toggle('uil-bookmark');
            icon.classList.toggle('uil-bookmark-full');
        }

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
                if (window.showToast) {
                    const message = wasBookmarked ? 'Bookmark removed' : '✓ Post bookmarked';
                    window.showToast(message, 'success');
                }
            } else {
                // Revert on failure
                if (icon) {
                    icon.classList.toggle('bookmarked');
                    icon.classList.toggle('uil-bookmark');
                    icon.classList.toggle('uil-bookmark-full');
                }
                if (window.showToast) {
                    window.showToast(data.message || '✗ Bookmark failed', 'error');
                }
            }
        } catch (error) {
            console.error('Bookmark error:', error);
            // Revert on error
            if (icon) {
                icon.classList.toggle('bookmarked');
                icon.classList.toggle('uil-bookmark');
                icon.classList.toggle('uil-bookmark-full');
            }
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
