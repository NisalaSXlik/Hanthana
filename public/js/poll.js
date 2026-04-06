document.addEventListener('DOMContentLoaded', function() {
    // Handle poll voting
    document.addEventListener('click', async function(e) {
        // Find the clicked button or its parent if icon clicked
        const btn = e.target.closest('.poll-option-btn');
        if (!btn) return;

        // Prevent default action
        e.preventDefault();
        e.stopPropagation();

        // Find the poll option container
        const optionContainer = btn.closest('.poll-option');
        if (!optionContainer) return;

        // Find the poll options container (parent)
        const pollOptionsContainer = btn.closest('.poll-options');
        if (!pollOptionsContainer) return;

        // Get IDs
        const postId = parseInt(pollOptionsContainer.dataset.postId || '', 10);
        const optionIndex = parseInt(optionContainer.dataset.optionIndex || '', 10);

        if (!Number.isInteger(postId) || !Number.isInteger(optionIndex)) return;

        // Disable all buttons in this poll to prevent multiple votes
        const allButtons = pollOptionsContainer.querySelectorAll('.poll-option-btn');
        allButtons.forEach(b => b.disabled = true);

        try {
            const formData = new FormData();
            formData.append('sub_action', 'votePollOption');
            formData.append('post_id', postId);
            formData.append('option_index', optionIndex);

            const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const votes = Array.isArray(data.votes) ? data.votes : [];
                const selectedIndex = Number.isInteger(parseInt(data.selected, 10))
                    ? parseInt(data.selected, 10)
                    : optionIndex;

                updatePollUI(postId, votes, selectedIndex);
                window.dispatchEvent(new CustomEvent('hanthana:poll-vote-changed', {
                    detail: {
                        postId,
                        votes,
                        selected: selectedIndex
                    }
                }));
            } else {
                // Re-enable buttons if failed
                allButtons.forEach(b => b.disabled = false);
                if (window.showToast) {
                    window.showToast(data.message || 'Vote failed', 'error');
                } else {
                    alert(data.message || 'Vote failed');
                }
            }
        } catch (error) {
            console.error('Poll vote error:', error);
            allButtons.forEach(b => b.disabled = false);
            if (window.showToast) {
                window.showToast('Vote failed', 'error');
            }
        }
    });

    window.addEventListener('hanthana:poll-vote-changed', function(event) {
        const detail = event.detail || {};
        const postId = parseInt(detail.postId, 10);
        if (!Number.isInteger(postId)) return;

        const votes = Array.isArray(detail.votes) ? detail.votes : [];
        const selectedIndex = Number.isInteger(parseInt(detail.selected, 10))
            ? parseInt(detail.selected, 10)
            : -1;

        updatePollUI(postId, votes, selectedIndex);
    });

    function updatePollUI(postId, votes, selectedIndex) {
        const containers = document.querySelectorAll(`.poll-options[data-post-id="${postId}"]`);
        containers.forEach(container => updatePollContainer(container, votes, selectedIndex));
    }

    function updatePollContainer(container, votes, selectedIndex) {
        const totalVotes = votes.reduce((a, b) => a + parseInt(b || 0, 10), 0);
        const options = container.querySelectorAll('.poll-option');

        options.forEach((option, index) => {
            const voteCount = parseInt(votes[index] || 0, 10);
            const percentage = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
            
            // Update selection state
            if (index === selectedIndex) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }

            // Update stats
            const percentageEl = option.querySelector('.option-percentage');
            const votesEl = option.querySelector('.option-votes');
            const progressEl = option.querySelector('.option-progress');

            if (percentageEl) percentageEl.textContent = percentage + '%';
            if (votesEl) votesEl.textContent = voteCount + ' vote' + (voteCount !== 1 ? 's' : '');
            if (progressEl) progressEl.style.width = percentage + '%';
        });

        const pollContent = container.closest('.poll-content');
        const totalVotesLabel = totalVotes + ' total vote' + (totalVotes !== 1 ? 's' : '');
        const totalVotesButton = pollContent ? pollContent.querySelector('.poll-total-votes') : null;
        const totalSpan = totalVotesButton ? totalVotesButton.querySelector('span') : null;
        if (totalSpan) {
            totalSpan.textContent = totalVotesLabel;
        } else if (totalVotesButton) {
            totalVotesButton.textContent = totalVotesLabel;
        }

        container.dataset.userVote = String(selectedIndex);
        
        // Re-enable buttons to allow changing vote
        const allButtons = container.querySelectorAll('.poll-option-btn');
        allButtons.forEach(b => b.disabled = false);
    }
});
