(function () {
    const eventTypeFilter = document.getElementById('eventTypeFilter');
    const eventTimeFilter = document.getElementById('eventTimeFilter');
    const governanceEvents = Array.from(document.querySelectorAll('.governance-event-item'));

    const seeVotesModal = document.getElementById('seeVotesModal');
    const closeSeeVotesModal = document.getElementById('closeSeeVotesModal');
    const votesPopupInFavorList = document.getElementById('votesPopupInFavorList');
    const votesPopupAgainstList = document.getElementById('votesPopupAgainstList');

    const voteEvents = Array.isArray(window.GOVERNANCE_VOTE_EVENTS) ? window.GOVERNANCE_VOTE_EVENTS : [];

    function openModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.add('is-open');
        modalEl.setAttribute('aria-hidden', 'false');
    }

    function closeModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.remove('is-open');
        modalEl.setAttribute('aria-hidden', 'true');
    }

    function showToast(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type || 'info');
            return;
        }
        alert(message);
    }

    function applyGovernanceFilters() {
        const typeValue = eventTypeFilter ? eventTypeFilter.value : 'all';
        const timeValue = eventTimeFilter ? eventTimeFilter.value : 'all';

        governanceEvents.forEach((eventEl) => {
            const matchesType = typeValue === 'all' || eventEl.dataset.type === typeValue;
            const matchesTime = timeValue === 'all' || eventEl.dataset.time === timeValue;
            eventEl.style.display = (matchesType && matchesTime) ? '' : 'none';
        });
    }

    async function postGovernanceAction(fields) {
        const payload = new FormData();
        Object.keys(fields || {}).forEach((key) => {
            payload.append(key, String(fields[key]));
        });

        const response = await fetch(BASE_PATH + 'index.php?controller=Group&action=handleAjax', {
            method: 'POST',
            body: payload
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Governance action failed.');
        }

        return data;
    }

    function buildAvatarUrl(raw) {
        const value = String(raw || '').trim();
        if (!value) {
            return BASE_PATH + 'uploads/user_dp/default_user_dp.jpg';
        }
        if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
            return value;
        }
        return BASE_PATH + value;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderVotesList(listEl, voters, voteType) {
        if (!listEl) return;
        const rows = Array.isArray(voters) ? voters : [];
        if (!rows.length) {
            listEl.innerHTML = '<li class="votes-popup-empty">No votes yet.</li>';
            return;
        }

        listEl.innerHTML = rows.map((voter) => {
            const iconClass = voteType === 'up' ? 'uil-thumbs-up vote-mark-up' : 'uil-thumbs-down vote-mark-down';
            return '<li class="votes-popup-item">'
                + '<img src="' + escapeHtml(buildAvatarUrl(voter.avatar)) + '" alt="' + escapeHtml(voter.name) + '">'
                + '<div class="votes-popup-main">'
                + '<strong>' + escapeHtml(voter.name) + '</strong>'
                + '<small>@' + escapeHtml(voter.username) + '</small>'
                + '</div>'
                + '<i class="uil ' + iconClass + '"></i>'
                + '</li>';
        }).join('');
    }

    if (eventTypeFilter) eventTypeFilter.addEventListener('change', applyGovernanceFilters);
    if (eventTimeFilter) eventTimeFilter.addEventListener('change', applyGovernanceFilters);
    applyGovernanceFilters();

    document.querySelectorAll('.vote-choice-checkbox').forEach((checkbox) => {
        checkbox.addEventListener('change', async (event) => {
            const current = event.currentTarget;
            if (!current.checked) {
                return;
            }

            const eventIndex = current.getAttribute('data-event-index');
            document.querySelectorAll('.vote-choice-checkbox[data-event-index="' + eventIndex + '"]').forEach((peer) => {
                if (peer !== current) {
                    peer.checked = false;
                }
            });

            const eventId = Number(current.getAttribute('data-event-id') || 0);
            const voteChoice = current.getAttribute('data-vote-choice') || '';

            if (!eventId || !voteChoice) {
                return;
            }

            try {
                await postGovernanceAction({
                    sub_action: 'cast_governance_vote',
                    group_id: GROUP_ID,
                    event_id: eventId,
                    vote_choice: voteChoice,
                });
                showToast('Vote recorded.', 'success');
                window.location.reload();
            } catch (error) {
                showToast(error.message, 'error');
            }
        });
    });

    document.querySelectorAll('.see-votes-popup-trigger').forEach((triggerBtn) => {
        triggerBtn.addEventListener('click', () => {
            const eventIndex = Number(triggerBtn.getAttribute('data-event-index'));
            const selected = voteEvents[eventIndex] || {};
            renderVotesList(votesPopupInFavorList, selected.supporters || [], 'up');
            renderVotesList(votesPopupAgainstList, selected.opponents || [], 'down');
            openModal(seeVotesModal);
        });
    });

    if (closeSeeVotesModal) {
        closeSeeVotesModal.addEventListener('click', () => closeModal(seeVotesModal));
    }

    [seeVotesModal].forEach((modalEl) => {
        if (!modalEl) return;
        modalEl.addEventListener('click', (event) => {
            if (event.target === modalEl) {
                closeModal(modalEl);
            }
        });
    });
})();
