// filepath: /mnt/c/Users/G-San/Desktop/Hanthane/public/js/vote.js
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', async function(e) {
        const arrow = e.target.closest('.uil-arrow-up, .uil-arrow-down');
        if (!arrow) return;

        const feed = arrow.closest('.feed');
        if (!feed) return;

        const postId = feed.dataset.postId;
        const voteType = arrow.dataset.voteType;

        if (!postId || !voteType) return;

        const upArrow = feed.querySelector('.uil-arrow-up');
        const downArrow = feed.querySelector('.uil-arrow-down');
        const upCount = upArrow.nextElementSibling;
        const downCount = downArrow.nextElementSibling;

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
                if (data.action === 'added' || data.action === 'changed') {
                    if (voteType === 'upvote') {
                        upArrow.classList.add('liked');
                        downArrow.classList.remove('liked');
                    } else {
                        downArrow.classList.add('liked');
                        upArrow.classList.remove('liked');
                    }
                } else if (data.action === 'removed') {
                    if (voteType === 'upvote') {
                        upArrow.classList.remove('liked');
                    } else {
                        downArrow.classList.remove('liked');
                    }
                }

                // Update counts
                if (upCount && data.upvote_count !== undefined) {
                    upCount.textContent = data.upvote_count;
                }
                if (downCount && data.downvote_count !== undefined) {
                    downCount.textContent = data.downvote_count;
                }
            } else {
                window.showToast(data.message || 'Vote failed', 'error');
            }
        } catch (error) {
            console.error('Vote error:', error);
            window.showToast('Vote failed', 'error');
        }
    });
});