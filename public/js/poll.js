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
        const postId = pollOptionsContainer.dataset.postId;
        const optionIndex = optionContainer.dataset.optionIndex;

        if (!postId || optionIndex === undefined) return;

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
                updatePollUI(pollOptionsContainer, data.votes, data.selected);
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

    function updatePollUI(container, votes, selectedIndex) {
        const totalVotes = votes.reduce((a, b) => a + parseInt(b), 0);
        const options = container.querySelectorAll('.poll-option');

        options.forEach((option, index) => {
            const voteCount = parseInt(votes[index] || 0);
            const percentage = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
            
            // Update selection state
            if (index === parseInt(selectedIndex)) {
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

        // Update total votes in footer if exists
        const footer = container.nextElementSibling;
        if (footer && footer.classList.contains('poll-footer')) {
            const totalSpan = footer.querySelector('.poll-total-votes span');
            if (totalSpan) {
                totalSpan.textContent = totalVotes + ' total vote' + (totalVotes !== 1 ? 's' : '');
            }
        }
        
        // Re-enable buttons to allow changing vote
        const allButtons = container.querySelectorAll('.poll-option-btn');
        allButtons.forEach(b => b.disabled = false);
    }
});
