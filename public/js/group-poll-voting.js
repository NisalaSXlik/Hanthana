// Enhanced Poll Voting Handler for Group Posts
document.addEventListener('click', async function(e) {
    // Check if click is within a poll option (button or any child element)
    const pollOption = e.target.closest('.poll-option');
    if (!pollOption) return;
    
    const pollBtn = pollOption.querySelector('.poll-option-btn');
    if (!pollBtn || pollBtn.disabled) {
        console.log('Poll voting: button not found or disabled', pollBtn);
        return;
    }

    e.preventDefault();
    
    const pollContainer = pollOption.closest('.poll-options');
    
    if (!pollContainer) {
        console.log('Poll voting: container not found');
        return;
    }
    
    const postId = pollContainer.dataset.postId;
    const optionIndex = pollOption.dataset.optionIndex;
    
    console.log('Poll voting started:', { postId, optionIndex });

    // Visual feedback - add loading state
    const allButtons = pollContainer.querySelectorAll('.poll-option-btn');
    const selectedButton = pollBtn;
    
    allButtons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.pointerEvents = 'none';
    });
    
    // Add pulse animation to selected option
    selectedButton.style.animation = 'pulse 0.5s ease-in-out';

    try {
        const formData = new FormData();
        formData.append('sub_action', 'votePollOption');
        formData.append('post_id', postId);
        formData.append('option_index', optionIndex);

        const url = BASE_PATH + 'index.php?controller=Group&action=handleAjax';
        console.log('Sending poll vote to:', url);
        
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        console.log('Poll vote response:', result);

        if (result.success) {
            // Update UI with new vote counts
            const votes = result.votes || [];
            console.log('Vote counts received:', votes);
            const totalVotes = votes.reduce((sum, val) => sum + Number(val || 0), 0);
            console.log('Total votes calculated:', totalVotes);

            // Re-enable buttons so user can change their vote later
            allButtons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
                btn.style.animation = '';
            });

            pollContainer.dataset.userVote = optionIndex;
            const voterPanel = document.getElementById(`poll-voters-${postId}`);
            if (voterPanel && voterPanel.classList.contains('open')) {
                loadPollVoters(postId, voterPanel);
            }

            // Animate vote count updates
            pollContainer.querySelectorAll('.poll-option').forEach((optEl, idx) => {
                const voteCount = Number(votes[idx] || 0);
                const percentage = totalVotes > 0 ? Math.round((voteCount / totalVotes) * 100) : 0;
                
                console.log(`Option ${idx}: votes=${voteCount}, percentage=${percentage}%`);

                // Update stats with smooth transition
                const percentageEl = optEl.querySelector('.option-percentage');
                const votesEl = optEl.querySelector('.option-votes');
                const progressEl = optEl.querySelector('.option-progress');
                
                if (percentageEl) {
                    percentageEl.style.transition = 'all 0.5s ease';
                    percentageEl.textContent = percentage + '%';
                    console.log('Updated percentage element:', percentageEl.textContent);
                }
                
                if (votesEl) {
                    votesEl.style.transition = 'all 0.5s ease';
                    votesEl.textContent = voteCount + ' vote' + (voteCount === 1 ? '' : 's');
                    console.log('Updated votes element:', votesEl.textContent);
                }
                
                if (progressEl) {
                    progressEl.style.transition = 'width 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    progressEl.style.width = percentage + '%';
                }

                // Mark selected option with highlight
                if (idx == optionIndex) {
                    optEl.classList.add('selected');
                    const btn = optEl.querySelector('.poll-option-btn');
                    if (btn) {
                        btn.style.opacity = '1';
                    }
                } else {
                    optEl.classList.remove('selected');
                }
            });

            // Update total votes with animation
            const pollFooter = pollContainer.closest('.poll-content')?.querySelector('.poll-total-votes');
            if (pollFooter) {
                pollFooter.style.transition = 'all 0.3s ease';
                pollFooter.textContent = totalVotes + ' total vote' + (totalVotes === 1 ? '' : 's');
            }

            // Show success message
            if (window.showToast) {
                window.showToast('✓ Vote recorded successfully!', 'success');
            } else {
                console.log('Vote recorded successfully!');
            }
            
            // Keep buttons disabled after successful vote
            allButtons.forEach(btn => {
                btn.style.animation = '';
            });
            
        } else {
            // Re-enable buttons on error
            allButtons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
                btn.style.animation = '';
            });
            
            if (window.showToast) {
                window.showToast(result.message || '✗ Failed to record vote', 'error');
            } else {
                alert(result.message || 'Failed to record vote');
            }
        }
    } catch (error) {
        console.error('Poll vote error:', error);
        
        // Re-enable buttons on error
        allButtons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
            btn.style.animation = '';
        });
        
        if (window.showToast) {
            window.showToast('✗ Network error. Please try again.', 'error');
        } else {
            alert('Failed to record vote. Please try again.');
        }
    }
});

// Add pulse animation keyframes dynamically
if (!document.getElementById('poll-vote-animations')) {
    const style = document.createElement('style');
    style.id = 'poll-vote-animations';
    style.textContent = `
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
    `;
    document.head.appendChild(style);
}

document.addEventListener('click', async function(e) {
    const totalBtn = e.target.closest('.poll-total-votes');
    if (!totalBtn) return;

    e.preventDefault();
    const postId = totalBtn.dataset.postId;
    if (!postId) return;

    const panel = document.getElementById(`poll-voters-${postId}`);
    if (!panel) return;

    const isOpen = panel.classList.contains('open');
    if (isOpen) {
        panel.classList.remove('open');
        panel.setAttribute('hidden', 'hidden');
        return;
    }

    panel.removeAttribute('hidden');
    panel.classList.add('open');

    await loadPollVoters(postId, panel);
});

async function loadPollVoters(postId, panel) {
    const content = panel.querySelector('.poll-voters-content');
    if (content) {
        content.innerHTML = '<div class="poll-voters-placeholder">Loading voters…</div>';
    }

    const formData = new FormData();
    formData.append('sub_action', 'fetchPollVotes');
    formData.append('post_id', postId);

    try {
        const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        console.log('fetchPollVotes response:', result);
        if (result.success) {
            console.log('Options data:', result.options);
            renderPollVoters(content, result.options || []);
        } else {
            renderPollVoterError(content, result.message || 'Unable to load voters.');
        }
    } catch (error) {
        console.error('fetchPollVotes error:', error);
        renderPollVoterError(content, 'Network error while loading voters.');
    }
}

function renderPollVoters(content, options) {
    if (!content) return;
    console.log('renderPollVoters called with:', options);
    if (!options.length) {
        content.innerHTML = '<div class="poll-voters-placeholder">No votes to display yet.</div>';
        return;
    }

    const html = options.map(option => {
        console.log('Processing option:', option);
        const safeLabel = escapeHtml(option.label || 'Option');
        const voterEntries = normalizeVoters(option.voters);
        console.log('Normalized voter entries:', voterEntries);
        const votersMarkup = voterEntries.length
            ? voterEntries.map(voter => `
                <div class="poll-voter">
                    <img src="${voter.avatar || ''}" alt="${escapeHtml(voter.name || 'Member')}">
                    <div>
                        <strong>${escapeHtml(voter.name || 'Member')}</strong>
                        ${voter.username ? `<span>@${escapeHtml(voter.username)}</span>` : ''}
                    </div>
                </div>
            `).join('')
            : '<div class="poll-voters-placeholder">No votes for this option yet.</div>';

        return `
            <div class="poll-voter-option">
                <div class="poll-voter-option-header">
                    <span>${safeLabel}</span>
                    <small>${voterEntries.length} vote${voterEntries.length === 1 ? '' : 's'}</small>
                </div>
                <div class="poll-voter-list">
                    ${votersMarkup}
                </div>
            </div>
        `;
    }).join('');

    content.innerHTML = html;
}

function renderPollVoterError(content, message) {
    if (!content) return;
    content.innerHTML = `<div class="poll-voters-error">${escapeHtml(message)}</div>`;
}

function escapeHtml(str) {
    if (typeof str !== 'string') {
        return str;
    }
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function normalizeVoters(raw) {
    if (!raw) {
        return [];
    }
    if (Array.isArray(raw)) {
        return raw;
    }
    if (typeof raw === 'object') {
        return Object.values(raw);
    }
    return [];
}
