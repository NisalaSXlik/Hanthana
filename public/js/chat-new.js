// Chat functionality

        let activeUsers = ['andrew', 'sarah', 'emma']; // Users currently active
        let currentActiveUser = null;
        let isMaximized = false;
        let currentConversation = null;
        let readChats = new Set(); // Track which chats have been read

        function openChat() {
            document.getElementById('chatContainer').classList.add('show');
            document.getElementById('chatOverlay').classList.add('show');
            
            // Reset to proper initial state
            isMaximized = false;
            document.getElementById('chatContainer').classList.remove('maximized');
            document.body.classList.remove('chat-maximized');
            
            // Show initial chat list view
            document.getElementById('chat-list-view').style.display = 'flex';
            document.getElementById('chat-conversation-view').style.display = 'none';
            document.getElementById('maximized-view').style.display = 'none';
            
            document.body.classList.add('chat-open');
            
            // Update active users display
            updateActiveUsers();
        }

        function closeChat() {
            document.getElementById('chatContainer').classList.remove('show');
            document.getElementById('chatOverlay').classList.remove('show');
            document.body.classList.remove('chat-open');
            document.body.classList.remove('chat-maximized');
            
            // Reset all states
            isMaximized = false;
            currentConversation = null;
            document.getElementById('chatContainer').classList.remove('maximized');
            
            // Reset view displays
            document.getElementById('chat-list-view').style.display = 'none';
            document.getElementById('chat-conversation-view').style.display = 'none';
            document.getElementById('maximized-view').style.display = 'none';
            document.getElementById('chat-list-maximized').style.display = 'none';
            document.getElementById('chat-conversation-maximized').style.display = 'none';
        }

        function updateActiveUsers() {
            const userItems = document.querySelectorAll('.user-item');
            
            userItems.forEach(item => {
                const userId = item.getAttribute('data-user');
                const activeIndicator = item.querySelector('.active-indicator');
                
                if (activeUsers.includes(userId)) {
                    item.classList.add('active');
                    if (activeIndicator) {
                        activeIndicator.style.display = 'block';
                    }
                } else {
                    item.classList.remove('active');
                    if (activeIndicator) {
                        activeIndicator.style.display = 'none';
                    }
                }
                
                // Hide message count for read chats
                if (readChats.has(userId)) {
                    const messageCount = item.querySelector('.message-count');
                    if (messageCount) {
                        messageCount.style.display = 'none';
                    }
                }
            });
        }

        function toggleUserActiveStatus(userId) {
            if (activeUsers.includes(userId)) {
                activeUsers = activeUsers.filter(id => id !== userId);
            } else {
                activeUsers.push(userId);
            }
            updateActiveUsers();
        }

        function openConversation(userName) {
            currentConversation = userName;
            
            if (isMaximized) {
                document.getElementById('chat-list-view').style.display = 'none';
                document.getElementById('chat-conversation-view').style.display = 'none';
                document.getElementById('maximized-view').style.display = 'flex';
                
                document.getElementById('maximized-conversation-name').textContent = userName;
                document.getElementById('maximized-conversation-avatar').querySelector('img').src = 
                    document.getElementById('conversation-avatar').querySelector('img').src;
            } else {
                document.getElementById('chat-list-view').style.display = 'none';
                document.getElementById('chat-conversation-view').style.display = 'flex';
                document.getElementById('maximized-view').style.display = 'none';
            }
            
            document.getElementById('conversation-name').textContent = userName;
            
            // Set current active user
            const userItems = document.querySelectorAll('.user-item');
            userItems.forEach(item => {
                const userNameElement = item.querySelector('.user-info h4');
                if (userNameElement && userNameElement.textContent === userName) {
                    currentActiveUser = item.getAttribute('data-user');
                }
            });
            
            // Remove new message indicator and message count for this user
            userItems.forEach(item => {
                const userNameElement = item.querySelector('.user-info h4');
                if (userNameElement && userNameElement.textContent === userName) {
                    const indicator = item.querySelector('.new-message-indicator');
                    if (indicator) {
                        indicator.remove();
                    }
                    
                    // Mark as read and hide message count
                    const userId = item.getAttribute('data-user');
                    if (userId) {
                        readChats.add(userId);
                        hideMessageCount(userId);
                    }
                }
            });
            
            // Update conversation header to show active status
            const conversationStatus = document.querySelector('#chat-conversation-view .text-muted');
            const userId = getUserIdByName(userName);
            if (activeUsers.includes(userId)) {
                conversationStatus.textContent = 'Active now';
                conversationStatus.style.color = 'var(--color-success)';
            } else {
                conversationStatus.textContent = 'Last seen recently';
                conversationStatus.style.color = 'var(--color-gray)';
            }
            
            // Update conversation avatar based on user
            const avatar = document.getElementById('conversation-avatar');
            const img = avatar.querySelector('img');
            
            switch(userName) {
                case 'Andrew Neil':
                    img.src = 'https://i.pravatar.cc/150?img=1';
                    img.alt = 'Andrew Neil';
                    break;
                case 'Sarah Anderson':
                    img.src = 'https://i.pravatar.cc/150?img=2';
                    img.alt = 'Sarah Anderson';
                    break;
                case 'Mike Johnson':
                    img.src = 'https://i.pravatar.cc/150?img=3';
                    img.alt = 'Mike Johnson';
                    break;
                case 'Emma Wilson':
                    img.src = 'https://i.pravatar.cc/150?img=4';
                    img.alt = 'Emma Wilson';
                    break;
            }
        }

        function getUserIdByName(userName) {
            switch(userName) {
                case 'Andrew Neil': return 'andrew';
                case 'Sarah Anderson': return 'sarah';
                case 'Mike Johnson': return 'mike';
                case 'Emma Wilson': return 'emma';
                default: return null;
            }
        }

        function backToList() {
            document.getElementById('chat-conversation-view').style.display = 'none';
            document.getElementById('maximized-view').style.display = 'none';
            document.getElementById('chat-list-view').style.display = 'flex';
        }

        function backToNormalView() {
            toggleMaximize();
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (message) {
                const messagesArea = document.getElementById('messages-area');
                const messageElement = document.createElement('div');
                messageElement.className = 'message own';
                messageElement.innerHTML = `
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=10" alt="You">
                    </div>
                    <div class="message-content">${message}</div>
                `;
                messagesArea.appendChild(messageElement);
                messagesArea.scrollTop = messagesArea.scrollHeight;
                input.value = '';
                
                // Auto-resize textarea
                input.style.height = 'auto';
                
                // Also add to maximized view if open
                if (isMaximized) {
                    const maximizedArea = document.getElementById('maximized-messages-area');
                    const maximizedElement = messageElement.cloneNode(true);
                    maximizedArea.appendChild(maximizedElement);
                    maximizedArea.scrollTop = maximizedArea.scrollHeight;
                }
            }
        }

        function sendMaximizedMessage() {
            const input = document.getElementById('maximized-message-input');
            const message = input.value.trim();
            
            if (message) {
                const messagesArea = document.getElementById('maximized-messages-area');
                const messageElement = document.createElement('div');
                messageElement.className = 'message own';
                messageElement.innerHTML = `
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=10" alt="You">
                    </div>
                    <div class="message-content">${message}</div>
                `;
                messagesArea.appendChild(messageElement);
                messagesArea.scrollTop = messagesArea.scrollHeight;
                input.value = '';
                
                // Also add to normal view
                const normalArea = document.getElementById('messages-area');
                const normalElement = messageElement.cloneNode(true);
                normalArea.appendChild(normalElement);
                normalArea.scrollTop = normalArea.scrollHeight;
                
                // Auto-resize textarea
                input.style.height = 'auto';
            }
        }

        function toggleMaximize() {
            isMaximized = !isMaximized;
            
            if (isMaximized) {
                document.getElementById('chatContainer').classList.add('maximized');
                document.getElementById('chat-list-view').style.display = 'none';
                document.getElementById('chat-conversation-view').style.display = 'none';
                document.getElementById('maximized-view').style.display = 'flex';
                document.body.classList.add('chat-maximized');
                
                // Check if there's an active conversation
                if (currentConversation) {
                    // Show the conversation in maximized view
                    document.getElementById('chat-list-maximized').style.display = 'none';
                    document.getElementById('chat-conversation-maximized').style.display = 'flex';
                    
                    // Update maximized conversation header with current conversation
                    document.getElementById('conversation-name-max').textContent = currentConversation;
                    
                    // Copy avatar from normal view to maximized view
                    const normalAvatar = document.getElementById('conversation-avatar').querySelector('img');
                    const maxAvatar = document.querySelector('#conversation-avatar-max img');
                    if (normalAvatar && maxAvatar) {
                        maxAvatar.src = normalAvatar.src;
                        maxAvatar.alt = normalAvatar.alt;
                    }
                    
                    // Load messages for current conversation
                    const userId = getUserIdByName(currentConversation);
                    if (userId) {
                        loadMessagesForChat(userId, currentConversation);
                        
                        // Select the active chat in maximized view
                        document.querySelectorAll('.user-item-max').forEach(item => {
                            item.classList.remove('selected');
                            if (item.getAttribute('data-user') === userId) {
                                item.classList.add('selected');
                            }
                        });
                    }
                    
                    // Show media content for current chat
                    showMediaContent(currentConversation);
                    
                } else {
                    // Show chat list by default when no conversation is active
                    document.getElementById('chat-list-maximized').style.display = 'flex';
                    document.getElementById('chat-conversation-maximized').style.display = 'none';
                    showEmptyMediaState();
                }
                
                // Update active users in maximized view
                updateActiveUsersMaximized();
                
                // Update read status for all chats
                updateReadStatus();
                
                // Sync messages across views to ensure attachments persist
                syncMessagesAcrossViews();
                
            } else {
                document.getElementById('chatContainer').classList.remove('maximized');
                document.getElementById('maximized-view').style.display = 'none';
                document.body.classList.remove('chat-maximized');
                
                if (currentConversation) {
                    document.getElementById('chat-conversation-view').style.display = 'flex';
                } else {
                    document.getElementById('chat-list-view').style.display = 'flex';
                }
                
                // Sync messages across views to ensure attachments persist
                syncMessagesAcrossViews();
            }
        }

        function showEmptyMediaState() {
            document.getElementById('media-empty-state').style.display = 'flex';
            document.getElementById('media-content-wrapper').style.display = 'none';
            document.getElementById('media-subtitle').textContent = 'Select a chat to view shared files';
        }

        function showMediaContent(chatName) {
            document.getElementById('media-empty-state').style.display = 'none';
            document.getElementById('media-content-wrapper').style.display = 'flex';
            document.getElementById('media-subtitle').textContent = `Shared with ${chatName}`;
            
            // Load media content for this chat
            loadMediaForChat(chatName);
        }

        function hideMessageCount(userId) {
            // Find all message count elements for this user in both regular and maximized views
            const selectors = [
                `.user-item[data-user="${userId}"] .message-count`,
                `.user-item-max[data-user="${userId}"] .message-count`
            ];
            
            selectors.forEach(selector => {
                const messageCount = document.querySelector(selector);
                if (messageCount && messageCount.style.display !== 'none') {
                    console.log('Hiding message count for user:', userId, 'with selector:', selector);
                    messageCount.style.opacity = '0';
                    messageCount.style.transform = 'scale(0)';
                    messageCount.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    
                    setTimeout(() => {
                        messageCount.style.display = 'none';
                    }, 300);
                }
            });
        }

        function selectChatInMaximized(chatName, userId) {
            console.log('selectChatInMaximized called for user:', userId);
            
            // Remove previous selection
            document.querySelectorAll('.user-item-max').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selection to clicked item
            const selectedItem = document.querySelector(`[data-user="${userId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('selected');
            }
            
            // Hide chat list and show conversation
            document.getElementById('chat-list-maximized').style.display = 'none';
            document.getElementById('chat-conversation-maximized').style.display = 'flex';
            
            // Update conversation header
            document.getElementById('conversation-name-max').textContent = chatName;
            const avatarImg = document.querySelector('#conversation-avatar-max img');
            const userImg = selectedItem.querySelector('img');
            if (avatarImg && userImg) {
                avatarImg.src = userImg.src;
                avatarImg.alt = chatName;
            }
            
            // Load messages for this chat
            loadMessagesForChat(userId, chatName);
            
            // Show media content for this chat
            showMediaContent(chatName);
            
            // Mark chat as read and hide message count
            readChats.add(userId);
            hideMessageCount(userId);
        }

        function backToChatList() {
            document.getElementById('chat-conversation-maximized').style.display = 'none';
            document.getElementById('chat-list-maximized').style.display = 'flex';
            
            // Remove all selections
            document.querySelectorAll('.user-item-max').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Show empty media state
            showEmptyMediaState();
        }

        function loadMessagesForChat(userId, chatName) {
            const messagesArea = document.getElementById('messages-area-max');
            
            // Sample messages for different users
            const messageData = {
                'andrew': [
                    { type: 'received', content: 'Hey! How\'s your day going?', time: '2:30 PM' },
                    { type: 'sent', content: 'Pretty good! Just working on some projects.', time: '2:31 PM' },
                    { type: 'received', content: 'That sounds great! What kind of projects?', time: '2:32 PM' }
                ],
                'sarah': [
                    { type: 'received', content: 'Did you see the new design?', time: '1:45 PM' },
                    { type: 'sent', content: 'Yes, it looks amazing!', time: '1:46 PM' }
                ],
                'mike': [
                    { type: 'received', content: 'Meeting at 3 PM?', time: '12:30 PM' },
                    { type: 'sent', content: 'Sure, I\'ll be there.', time: '12:31 PM' }
                ],
                'emma': [
                    { type: 'received', content: 'Thanks for the help yesterday!', time: '11:15 AM' },
                    { type: 'sent', content: 'You\'re welcome! Happy to help.', time: '11:16 AM' }
                ]
            };
            
            const messages = messageData[userId] || [];
            messagesArea.innerHTML = '';
            
            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.type}`;
                messageDiv.innerHTML = `
                    <div class="message-content">${msg.content}</div>
                    <div class="message-time">${msg.time}</div>
                `;
                messagesArea.appendChild(messageDiv);
            });
            
            // Scroll to bottom
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        function sendMessageMax() {
            const input = document.getElementById('messageInput-max');
            const message = input.value.trim();
            
            if (message) {
                const messagesArea = document.getElementById('messages-area-max');
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message sent';
                messageDiv.innerHTML = `
                    <div class="message-content">${message}</div>
                    <div class="message-time">Now</div>
                `;
                messagesArea.appendChild(messageDiv);
                
                input.value = '';
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
        }

        // Add event listener for Enter key in maximized message input
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput-max');
            if (messageInput) {
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendMessageMax();
                    }
                });
            }
        });

        function updateActiveUsersMaximized() {
            const userItems = document.querySelectorAll('.user-item-max');
            
            userItems.forEach(item => {
                const userId = item.getAttribute('data-user');
                const activeIndicator = item.querySelector('.active-indicator');
                
                if (activeUsers.includes(userId)) {
                    item.classList.add('active');
                    if (activeIndicator) {
                        activeIndicator.style.display = 'block';
                    }
                } else {
                    item.classList.remove('active');
                    if (activeIndicator) {
                        activeIndicator.style.display = 'none';
                    }
                }
            });
        }

        function updateReadStatus() {
            const userItems = document.querySelectorAll('.user-item-max');
            
            userItems.forEach(item => {
                const userId = item.getAttribute('data-user');
                
                if (readChats.has(userId)) {
                    // Hide message count for read chats
                    const messageCount = item.querySelector('.message-count');
                    if (messageCount) {
                        messageCount.style.display = 'none';
                    }
                    
                    // Remove new message indicator for read chats
                    const indicator = item.querySelector('.new-message-indicator');
                    if (indicator) {
                        indicator.remove();
                    }
                }
            });
        }

        function loadMediaForChat(chatName) {
            const mediaGrid = document.getElementById('media-grid');
            
            // Ensure media sections exist before loading content
            createMediaSections();
            
            // Clear existing content
            mediaGrid.innerHTML = '';
            
            // Load sample media based on chat
            const sampleMedia = getSampleMediaForChat(chatName);
            
            sampleMedia.forEach(media => {
                const mediaItem = createMediaItem(media);
                mediaGrid.appendChild(mediaItem);
            });
        }

        function getSampleMediaForChat(chatName) {
            const mediaData = {
                'Andrew Neil': [
                    {type: 'image', name: 'beach_photo.jpg', size: '2.4 MB', url: 'https://picsum.photos/200/200?random=1'},
                    {type: 'video', name: 'sunset_video.mp4', size: '15.2 MB', url: 'https://picsum.photos/200/200?random=2'},
                    {type: 'document', name: 'travel_plan.pdf', size: '1.8 MB'}
                ],
                'Sarah Anderson': [
                    {type: 'image', name: 'design_mockup.jpg', size: '3.1 MB', url: 'https://picsum.photos/200/200?random=3'},
                    {type: 'document', name: 'project_brief.docx', size: '890 KB'}
                ],
                'Mike Johnson': [
                    {type: 'document', name: 'meeting_agenda.pdf', size: '524 KB'},
                    {type: 'image', name: 'whiteboard_notes.jpg', size: '1.9 MB', url: 'https://picsum.photos/200/200?random=4'}
                ],
                'Emma Wilson': [
                    {type: 'image', name: 'thank_you_card.jpg', size: '1.2 MB', url: 'https://picsum.photos/200/200?random=5'}
                ]
            };
            
            return mediaData[chatName] || [];
        }

        function createMediaItem(media) {
            const mediaItem = document.createElement('div');
            mediaItem.className = `media-item ${media.type}`;
            mediaItem.setAttribute('data-type', media.type);
            mediaItem.onclick = () => previewMedia(mediaItem);
            
            let thumbnailContent = '';
            if (media.type === 'image') {
                thumbnailContent = `<img src="${media.url}" alt="${media.name}">`;
            } else if (media.type === 'video') {
                thumbnailContent = `
                    <img src="${media.url}" alt="${media.name}">
                    <div class="play-button"><i class="uil uil-play"></i></div>
                `;
            } else {
                thumbnailContent = `<div class="file-icon"><i class="uil uil-file-alt"></i></div>`;
            }
            
            mediaItem.innerHTML = `
                <div class="media-thumbnail">
                    ${thumbnailContent}
                    <div class="media-overlay">
                        <button class="media-action" onclick="downloadMedia(event, this)" title="Download">
                            <i class="uil uil-download-alt"></i>
                        </button>
                        <button class="media-action" onclick="shareMedia(event, this)" title="Share">
                            <i class="uil uil-share-alt"></i>
                        </button>
                        <button class="media-action" onclick="deleteMedia(event, this)" title="Delete">
                            <i class="uil uil-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="media-info">
                    <div class="media-name">${media.name}</div>
                    <div class="media-details">${media.size} • Shared</div>
                </div>
            `;
            
            return mediaItem;
        }

        // Media tabs functionality
        document.querySelectorAll('.media-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.media-tab').forEach(t => {
                    t.classList.remove('active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all content
                document.getElementById('media-grid').style.display = 'none';
                document.getElementById('files-content').style.display = 'none';
                document.getElementById('links-content').style.display = 'none';
                
                // Show relevant content
                const tabName = this.getAttribute('data-tab');
                if (tabName === 'media') {
                    document.getElementById('media-grid').style.display = 'grid';
                } else if (tabName === 'files') {
                    document.getElementById('files-content').style.display = 'block';
                } else if (tabName === 'links') {
                    document.getElementById('links-content').style.display = 'block';
                }
            });
        });

        // Auto-resize textarea
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput');
            const maximizedInput = document.getElementById('maximized-message-input');
            
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });

                // Send message on Enter
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
            
            if (maximizedInput) {
                maximizedInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });

                // Send message on Enter
                maximizedInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMaximizedMessage();
                    }
                });
            }
            
            // Initialize active users display
            updateActiveUsers();
            
            // Simulate dynamic active status changes (optional)
            simulateActiveStatusChanges();
        });

        // Simulate users going online/offline randomly
        function simulateActiveStatusChanges() {
            const allUsers = ['andrew', 'sarah', 'mike', 'emma'];
            
            setInterval(() => {
                // Randomly change active status for demonstration
                const randomUser = allUsers[Math.floor(Math.random() * allUsers.length)];
                toggleUserActiveStatus(randomUser);
                
                // Update conversation status if we're currently chatting with this user
                if (currentActiveUser === randomUser) {
                    const conversationStatus = document.querySelector('#chat-conversation-view .text-muted');
                    if (conversationStatus) {
                        if (activeUsers.includes(randomUser)) {
                            conversationStatus.textContent = 'Active now';
                            conversationStatus.style.color = 'var(--color-success)';
                        } else {
                            conversationStatus.textContent = 'Last seen recently';
                            conversationStatus.style.color = 'var(--color-gray)';
                        }
                    }
                }
            }, 10000); // Change every 10 seconds for demo purposes
        }

        // Close chat when clicking outside
        document.addEventListener('click', function(e) {
            const chatContainer = document.getElementById('chatContainer');
            const chatIcon = document.getElementById('chatIcon');
            
            if (chatContainer && chatIcon && 
                !chatContainer.contains(e.target) && 
                !chatIcon.contains(e.target) && 
                chatContainer.classList.contains('show')) {
                closeChat();
            }
        });

        // Media Storage Interface Functions
        let currentFolder = '';
        let viewMode = 'grid';

        function createFolder() {
            showFolderPopup();
        }

        // Folder Popup Functions
        function showFolderPopup() {
            document.getElementById('folderPopupOverlay').style.display = 'flex';
            document.getElementById('folderNameInput').focus();
            setupFolderPopupEvents();
        }

        function closeFolderPopup() {
            document.getElementById('folderPopupOverlay').style.display = 'none';
            resetFolderPopup();
        }

        function handlePopupOverlayClick(event) {
            // Only close popup if clicking directly on the overlay (background)
            if (event.target === event.currentTarget) {
                event.stopPropagation();
                event.preventDefault();
                closeFolderPopup();
            }
        }

        function resetFolderPopup() {
            document.getElementById('folderNameInput').value = '';
            document.getElementById('folderPreviewName').textContent = 'New Folder';
            document.getElementById('charCount').textContent = '0/50';
            document.getElementById('errorMsg').style.display = 'none';
            document.getElementById('createBtn').disabled = true;
            document.getElementById('folderNameInput').classList.remove('error');
            document.getElementById('createSubfolders').checked = true;
            
            // Reset color selection
            document.querySelectorAll('.color-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelector('.color-option[data-color="default"]').classList.add('selected');
        }

        function setupFolderPopupEvents() {
            const folderNameInput = document.getElementById('folderNameInput');
            const folderPreviewName = document.getElementById('folderPreviewName');
            const charCount = document.getElementById('charCount');
            const errorMsg = document.getElementById('errorMsg');
            const createBtn = document.getElementById('createBtn');

            // Real-time input validation
            folderNameInput.addEventListener('input', function() {
                const value = this.value;
                const length = value.length;
                
                // Update character count
                charCount.textContent = `${length}/50`;
                
                // Update preview name
                folderPreviewName.textContent = value || 'New Folder';
                
                // Validation
                let isValid = true;
                let errorText = '';
                
                if (length === 0) {
                    isValid = false;
                } else if (length > 50) {
                    isValid = false;
                    errorText = 'Folder name is too long';
                } else if (!/^[a-zA-Z0-9\s\-_]+$/.test(value)) {
                    isValid = false;
                    errorText = 'Only letters, numbers, spaces, hyphens and underscores allowed';
                } else if (folderExists(value)) {
                    isValid = false;
                    errorText = 'A folder with this name already exists';
                }
                
                // Update UI
                if (errorText) {
                    errorMsg.textContent = errorText;
                    errorMsg.style.display = 'block';
                    this.classList.add('error');
                } else {
                    errorMsg.style.display = 'none';
                    this.classList.remove('error');
                }
                
                createBtn.disabled = !isValid;
            });

            // Color selection
            document.querySelectorAll('.color-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });

            // Enter key submission
            folderNameInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !createBtn.disabled) {
                    createFolderFromPopup();
                }
            });

            // Escape key to close
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('folderPopupOverlay').style.display === 'flex') {
                    e.stopPropagation();
                    e.preventDefault();
                    closeFolderPopup();
                }
            });
        }

        function folderExists(folderName) {
            const existingFolders = document.querySelectorAll('.media-folder .folder-name');
            return Array.from(existingFolders).some(folder => 
                folder.textContent.toLowerCase() === folderName.toLowerCase()
            );
        }

        function createFolderFromPopup() {
            const folderName = document.getElementById('folderNameInput').value.trim();
            const createSubfolders = document.getElementById('createSubfolders').checked;
            const selectedColor = document.querySelector('.color-option.selected').dataset.color;
            
            if (!folderName) return;
            
            const mediaGrid = document.getElementById('media-grid');
            const newFolder = document.createElement('div');
            newFolder.className = 'media-folder';
            newFolder.setAttribute('data-folder', folderName.toLowerCase().replace(/\s+/g, '_'));
            newFolder.setAttribute('data-color', selectedColor);
            newFolder.onclick = () => openFolder(folderName.toLowerCase().replace(/\s+/g, '_'));
            
            let folderIconColor = '';
            switch(selectedColor) {
                case 'red': folderIconColor = '#e74c3c'; break;
                case 'green': folderIconColor = '#2ecc71'; break;
                case 'orange': folderIconColor = '#f39c12'; break;
                case 'purple': folderIconColor = '#9b59b6'; break;
                case 'pink': folderIconColor = '#e91e63'; break;
                default: folderIconColor = '#4a90e2'; break;
            }
            
            newFolder.innerHTML = `
                <div class="folder-icon" style="color: ${folderIconColor};">
                    <i class="uil uil-folder"></i>
                    <span class="folder-count">0</span>
                </div>
                <div class="folder-name">${folderName}</div>
                <div class="folder-date">Just now</div>
            `;
            
            const sectionDivider = document.querySelector('.section-divider');
            if (sectionDivider) {
                mediaGrid.insertBefore(newFolder, sectionDivider);
            } else {
                mediaGrid.appendChild(newFolder);
            }
            
            // Create subfolders if requested
            if (createSubfolders) {
                // This would be implemented based on your folder structure needs
                console.log('Creating subfolders for:', folderName);
            }
            
            showToast(`Folder "${folderName}" created successfully!`);
            closeFolderPopup();
        }

        function uploadFiles() {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            input.accept = 'image/*,video/*,.pdf,.doc,.docx,.txt';
            
            input.onchange = function(e) {
                const files = Array.from(e.target.files);
                files.forEach(file => addFileToGrid(file));
                showToast(`${files.length} file(s) uploaded successfully!`);
            };
            
            input.click();
        }

        function addFileToGrid(file) {
            const mediaGrid = document.getElementById('media-grid');
            const fileType = getFileType(file);
            const fileSize = formatFileSize(file.size);
            
            const mediaItem = document.createElement('div');
            mediaItem.className = `media-item ${fileType}`;
            mediaItem.setAttribute('data-type', fileType);
            mediaItem.onclick = () => previewMedia(mediaItem);
            
            let thumbnailContent = '';
            if (fileType === 'image') {
                const url = URL.createObjectURL(file);
                thumbnailContent = `<img src="${url}" alt="${file.name}">`;
            } else if (fileType === 'video') {
                const url = URL.createObjectURL(file);
                thumbnailContent = `
                    <img src="${url}" alt="${file.name}">
                    <div class="play-button"><i class="uil uil-play"></i></div>
                `;
            } else {
                thumbnailContent = `<div class="file-icon"><i class="uil uil-file-alt"></i></div>`;
            }
            
            mediaItem.innerHTML = `
                <div class="media-thumbnail">
                    ${thumbnailContent}
                    <div class="media-overlay">
                        <button class="media-action" onclick="downloadMedia(event, this)" title="Download">
                            <i class="uil uil-download-alt"></i>
                        </button>
                        <button class="media-action" onclick="shareMedia(event, this)" title="Share">
                            <i class="uil uil-share-alt"></i>
                        </button>
                        <button class="media-action" onclick="deleteMedia(event, this)" title="Delete">
                            <i class="uil uil-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="media-info">
                    <div class="media-name">${file.name}</div>
                    <div class="media-details">${fileSize} • Just now</div>
                </div>
            `;
            
            mediaGrid.appendChild(mediaItem);
        }

        function getFileType(file) {
            if (file.type.startsWith('image/')) return 'image';
            if (file.type.startsWith('video/')) return 'video';
            return 'document';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function toggleViewMode() {
            viewMode = viewMode === 'grid' ? 'list' : 'grid';
            const mediaGrid = document.getElementById('media-grid');
            const viewToggle = document.getElementById('viewToggle');
            
            if (viewMode === 'list') {
                mediaGrid.classList.add('list-view');
                viewToggle.innerHTML = '<i class="uil uil-list-ul"></i>';
            } else {
                mediaGrid.classList.remove('list-view');
                viewToggle.innerHTML = '<i class="uil uil-apps"></i>';
            }
        }

        function openFolder(folderName) {
            currentFolder = folderName;
            document.getElementById('currentPath').textContent = folderName.charAt(0).toUpperCase() + folderName.slice(1);
            showToast(`Opened ${folderName} folder`);
        }

        function navigateToFolder(path) {
            currentFolder = path;
            if (path === '') {
                document.getElementById('currentPath').textContent = 'All Media';
                const mediaItems = document.querySelectorAll('.media-item, .media-folder');
                mediaItems.forEach(item => item.style.display = 'flex');
            }
        }

        function previewMedia(element) {
            const mediaName = element.querySelector('.media-name').textContent;
            const mediaType = element.getAttribute('data-type');
            showToast(`Preview: ${mediaName} (${mediaType})`);
        }

        function downloadMedia(event, element) {
            event.stopPropagation();
            const mediaName = element.closest('.media-item').querySelector('.media-name').textContent;
            showToast(`Download started: ${mediaName}`);
        }

        function shareMedia(event, element) {
            event.stopPropagation();
            const mediaName = element.closest('.media-item').querySelector('.media-name').textContent;
            showToast(`Shared: ${mediaName}`);
        }

        function deleteMedia(event, element) {
            event.stopPropagation();
            const mediaItem = element.closest('.media-item');
            const mediaName = mediaItem.querySelector('.media-name').textContent;
            
            if (confirm(`Are you sure you want to delete ${mediaName}?`)) {
                mediaItem.remove();
                showToast(`Deleted: ${mediaName}`);
            }
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
                background: var(--color-dark); color: var(--color-white);
                padding: 0.75rem 1.5rem; border-radius: var(--border-radius);
                z-index: 10000; opacity: 0; transition: opacity 0.3s ease;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.style.opacity = '1', 100);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }

        // File Attachment Functionality
        function attachFile() {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = true;
            input.accept = 'image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.zip,.rar';
            
            input.onchange = function(e) {
                const files = Array.from(e.target.files);
                files.forEach(file => {
                    addAttachmentToChat(file);
                });
                
                if (files.length > 0) {
                    showToast(`${files.length} file(s) attached successfully!`);
                }
            };
            
            input.click();
        }

        function addAttachmentToChat(file) {
            console.log('🚀 Adding attachment to chat:', file.name);
            
            const fileType = getAttachmentType(file);
            const fileSize = formatFileSize(file.size);
            const fileName = file.name;
            const mimeType = file.type;
            
            console.log('📋 File details - Type:', fileType, 'Size:', fileSize);
            
            // Create file preview URL
            const fileURL = URL.createObjectURL(file);
            
            // Create attachment message (true for sent message)
            const messageHtml = createAttachmentMessage(fileName, fileSize, fileType, fileURL, mimeType, true);
            
            // Add to both normal and maximized message areas
            addMessageToArea('messages-area', messageHtml);
            addMessageToArea('messages-area-max', messageHtml);
            
            console.log('💬 Attachment message added to chat');
            
            // Add to media storage
            addToMediaStorage(file, fileURL, fileType);
        }

        function ensureMediaSectionVisible() {
            const mediaGrid = document.getElementById('media-grid');
            if (mediaGrid) {
                mediaGrid.style.display = 'block';
                
                // Make sure parent containers are visible
                const chatMain = mediaGrid.closest('.chat-main');
                if (chatMain) {
                    chatMain.style.display = 'block';
                }
            }
        }

        function addToMediaStorage(file, fileURL, fileType) {
            console.log('🔄 Adding to media storage:', file.name, 'Type:', fileType);
            
            // First check if we're in the right context (media storage should be visible)
            const mediaGrid = document.getElementById('media-grid');
            if (!mediaGrid) {
                console.error('❌ Media grid not found! Are we in the right view?');
                return;
            }
            
            console.log('✅ Media grid found:', mediaGrid);
            
            // Get appropriate media section immediately
            let mediaSection;
            
            if (fileType === 'image') {
                mediaSection = document.getElementById('media-photos');
                console.log('📸 Target: Photos section');
            } else if (fileType === 'video') {
                mediaSection = document.getElementById('media-videos');
                console.log('🎥 Target: Videos section');
            } else {
                mediaSection = document.getElementById('media-documents');
                console.log('📄 Target: Documents section');
            }

            if (!mediaSection) {
                console.error('❌ Media section not found for type:', fileType);
                console.log('Available sections:');
                console.log('- media-photos:', !!document.getElementById('media-photos'));
                console.log('- media-videos:', !!document.getElementById('media-videos'));
                console.log('- media-documents:', !!document.getElementById('media-documents'));
                
                // Try to create the section if it doesn't exist
                if (mediaGrid) {
                    console.log('🔧 Attempting to create missing media sections...');
                    createMediaSections();
                    // Try again after creating
                    if (fileType === 'image') {
                        mediaSection = document.getElementById('media-photos');
                    } else if (fileType === 'video') {
                        mediaSection = document.getElementById('media-videos');
                    } else {
                        mediaSection = document.getElementById('media-documents');
                    }
                }
                
                if (!mediaSection) {
                    console.error('❌ Still no media section after creation attempt');
                    return;
                }
            }

            console.log('✅ Found media section:', mediaSection);

            // Create media item instantly
            const mediaItem = document.createElement('div');
            mediaItem.className = 'media-item';
            
            if (fileType === 'image') {
                mediaItem.innerHTML = `<img src="${fileURL}" alt="${file.name}" onclick="previewFile('${fileURL}', '${file.name}')" title="${file.name}">`;
            } else if (fileType === 'video') {
                mediaItem.innerHTML = `<video onclick="previewFile('${fileURL}', '${file.name}')" title="${file.name}"><source src="${fileURL}" type="${file.type}"></video>`;
            } else {
                const fileIcon = getFileIcon(fileType);
                mediaItem.innerHTML = `<div class="file-item" onclick="downloadFile('${fileURL}', '${file.name}')" title="${file.name}"><i class="${fileIcon}"></i><span class="file-name">${file.name}</span></div>`;
            }

            // Add immediately without any delays
            mediaSection.appendChild(mediaItem);
            console.log('✅ Media item added. Total items:', mediaSection.children.length);
            
            // Force immediate display update
            mediaSection.style.display = 'grid';
            const parentSection = mediaSection.parentElement;
            if (parentSection) {
                parentSection.style.display = 'block';
                console.log('✅ Parent section made visible');
            }
            
            // Force browser to recalculate layout
            mediaSection.offsetHeight;
            
            // Also trigger a manual refresh of all media sections
            refreshAllMediaSections();
            
            console.log('🎉 Media update complete!');
        }

        function refreshAllMediaSections() {
            const sections = ['media-photos', 'media-videos', 'media-documents'];
            sections.forEach(sectionId => {
                const section = document.getElementById(sectionId);
                if (section) {
                    section.style.display = 'grid';
                    const parent = section.closest('.media-section');
                    if (parent) {
                        parent.style.display = 'block';
                    }
                }
            });
            console.log('🔄 All media sections refreshed');
        }

        function createMediaSections() {
            const mediaGrid = document.getElementById('media-grid');
            if (!mediaGrid) return;

            console.log('🔧 Creating media sections...');
            
            // Create Photos section
            if (!document.getElementById('media-photos')) {
                const photosSection = document.createElement('div');
                photosSection.className = 'media-section';
                photosSection.innerHTML = `
                    <h4>Photos</h4>
                    <div class="media-photos" id="media-photos"></div>
                `;
                mediaGrid.appendChild(photosSection);
            }
            
            // Create Videos section
            if (!document.getElementById('media-videos')) {
                const videosSection = document.createElement('div');
                videosSection.className = 'media-section';
                videosSection.innerHTML = `
                    <h4>Videos</h4>
                    <div class="media-videos" id="media-videos"></div>
                `;
                mediaGrid.appendChild(videosSection);
            }
            
            // Create Documents section
            if (!document.getElementById('media-documents')) {
                const documentsSection = document.createElement('div');
                documentsSection.className = 'media-section';
                documentsSection.innerHTML = `
                    <h4>Documents</h4>
                    <div class="media-documents" id="media-documents"></div>
                `;
                mediaGrid.appendChild(documentsSection);
            }
            
            console.log('✅ Media sections created');
        }

        function syncMessagesAcrossViews() {
            const normalArea = document.getElementById('messages-area');
            const maxArea = document.getElementById('messages-area-max');
            
            if (normalArea && maxArea) {
                // Sync from normal to maximized
                if (normalArea.children.length > maxArea.children.length) {
                    console.log('🔄 Syncing messages from normal to maximized view');
                    maxArea.innerHTML = normalArea.innerHTML;
                }
                // Sync from maximized to normal  
                else if (maxArea.children.length > normalArea.children.length) {
                    console.log('🔄 Syncing messages from maximized to normal view');
                    normalArea.innerHTML = maxArea.innerHTML;
                }
            }
        }

        function createAttachmentMessage(fileName, fileSize, fileType, fileURL, mimeType, isSent = true) {
            const currentTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            let attachmentContent = '';
            
            if (fileType === 'image') {
                attachmentContent = `
                    <div class="attachment-preview image-preview">
                        <img src="${fileURL}" alt="${fileName}" onclick="previewFile('${fileURL}', '${fileName}')">
                        <div class="attachment-info">
                            <span class="file-name">${fileName}</span>
                            <span class="file-size">${fileSize}</span>
                        </div>
                    </div>
                `;
            } else if (fileType === 'video') {
                attachmentContent = `
                    <div class="attachment-preview video-preview">
                        <video controls onclick="previewFile('${fileURL}', '${fileName}')">
                            <source src="${fileURL}" type="${mimeType}">
                        </video>
                        <div class="attachment-info">
                            <span class="file-name">${fileName}</span>
                            <span class="file-size">${fileSize}</span>
                        </div>
                    </div>
                `;
            } else {
                const fileIcon = getFileIcon(fileType);
                attachmentContent = `
                    <div class="attachment-preview file-preview" onclick="downloadFile('${fileURL}', '${fileName}')">
                        <div class="file-icon">
                            <i class="${fileIcon}"></i>
                        </div>
                        <div class="attachment-info">
                            <span class="file-name">${fileName}</span>
                            <span class="file-size">${fileSize}</span>
                            <span class="file-type">${fileType.toUpperCase()}</span>
                        </div>
                        <div class="download-btn">
                            <i class="uil uil-download-alt"></i>
                        </div>
                    </div>
                `;
            }
            
            // Simple attachment message without profile photo - just the content
            return `
                <div class="attachment-message ${isSent ? 'sent' : 'received'}">
                    <div class="attachment-content">
                        ${attachmentContent}
                        <div class="attachment-time">${currentTime}</div>
                    </div>
                </div>
            `;
        }

        function getAttachmentType(file) {
            if (file.type.startsWith('image/')) return 'image';
            if (file.type.startsWith('video/')) return 'video';
            if (file.type.startsWith('audio/')) return 'audio';
            if (file.type.includes('pdf')) return 'pdf';
            if (file.type.includes('document') || file.type.includes('word')) return 'document';
            if (file.type.includes('text')) return 'text';
            if (file.type.includes('zip') || file.type.includes('rar')) return 'archive';
            return 'file';
        }

        function getFileIcon(fileType) {
            switch(fileType) {
                case 'pdf': return 'uil uil-file-alt';
                case 'document': return 'uil uil-file-edit-alt';
                case 'text': return 'uil uil-file-alt';
                case 'audio': return 'uil uil-music';
                case 'archive': return 'uil uil-file-compress-alt';
                default: return 'uil uil-file';
            }
        }

        function addMessageToArea(areaId, messageHtml) {
            const messagesArea = document.getElementById(areaId);
            console.log(`💬 Adding message to ${areaId}:`, !!messagesArea);
            if (messagesArea) {
                messagesArea.insertAdjacentHTML('beforeend', messageHtml);
                messagesArea.scrollTop = messagesArea.scrollHeight;
                console.log(`✅ Message added to ${areaId}, total messages:`, messagesArea.children.length);
            } else {
                console.error(`❌ Message area ${areaId} not found!`);
            }
        }

        function previewFile(fileURL, fileName) {
            // Open file in a new tab/window for preview
            const newWindow = window.open(fileURL, '_blank');
            if (!newWindow) {
                // Fallback: download the file
                downloadFile(fileURL, fileName);
            }
        }

        function downloadFile(fileURL, fileName) {
            const link = document.createElement('a');
            link.href = fileURL;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Enhanced Media Search Functionality
        function clearMediaSearch() {
            const searchInput = document.getElementById('mediaSearch');
            const clearBtn = document.querySelector('.search-clear');
            
            searchInput.value = '';
            clearBtn.style.display = 'none';
            
            // Show all media items
            const mediaItems = document.querySelectorAll('.media-item, .media-folder');
            mediaItems.forEach(item => {
                item.style.display = 'block';
            });
            
            showToast('Search cleared');
        }

        // Initialize enhanced search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('mediaSearch');
            const clearBtn = document.querySelector('.search-clear');
            
            if (searchInput && clearBtn) {
                // Show/hide clear button based on input
                searchInput.addEventListener('input', function() {
                    const hasValue = this.value.trim().length > 0;
                    clearBtn.style.display = hasValue ? 'block' : 'none';
                    
                    // Perform search
                    performMediaSearch(this.value.trim());
                });
                
                // Clear search on button click
                clearBtn.addEventListener('click', clearMediaSearch);
                
                // Search on Enter key
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        performMediaSearch(this.value.trim());
                    }
                });
            }
        });

        function performMediaSearch(query) {
            const mediaItems = document.querySelectorAll('.media-item, .media-folder');
            
            if (!query) {
                // Show all items if query is empty
                mediaItems.forEach(item => {
                    item.style.display = 'block';
                });
                return;
            }
            
            let foundCount = 0;
            
            mediaItems.forEach(item => {
                const mediaName = item.querySelector('.media-name, .folder-name');
                const mediaType = item.getAttribute('data-type') || 'folder';
                
                if (mediaName) {
                    const nameText = mediaName.textContent.toLowerCase();
                    const queryLower = query.toLowerCase();
                    
                    // Search in name and type
                    if (nameText.includes(queryLower) || mediaType.includes(queryLower)) {
                        item.style.display = 'block';
                        foundCount++;
                    } else {
                        item.style.display = 'none';
                    }
                }
            });
            
            // Show search results count
            if (foundCount === 0) {
                showToast(`No results found for "${query}"`);
            } else {
                showToast(`Found ${foundCount} result(s) for "${query}"`);
            }
        }

        // Helper function to add received attachment message (for future use)
        function addReceivedAttachment(fileName, fileSize, fileType, fileURL, mimeType) {
            // Create received attachment message (false for received message)
            const messageHtml = createAttachmentMessage(fileName, fileSize, fileType, fileURL, mimeType, false);
            
            // Add to both normal and maximized message areas
            addMessageToArea('messages-area', messageHtml);
            addMessageToArea('messages-area-max', messageHtml);
            
            // Optionally add to media storage (you might want to separate sent/received media)
            // addToMediaStorage(file, fileURL, fileType);
        }

        // Media tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mediaTabs = document.querySelectorAll('.media-tab');
            
            mediaTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    mediaTabs.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    
                    // Force refresh media sections when switching to media tab
                    if (targetTab === 'media') {
                        refreshMediaSections();
                    }
                });
            });
        });

        function refreshMediaSections() {
            // Ensure media sections exist before refreshing
            createMediaSections();
            
            const sections = ['media-photos', 'media-videos', 'media-documents'];
            
            sections.forEach(sectionId => {
                const section = document.getElementById(sectionId);
                if (section) {
                    // Force redraw
                    section.style.display = 'none';
                    section.offsetHeight; // Force reflow
                    section.style.display = 'grid';
                }
            });
            
            console.log('Media sections refreshed and ensured to exist');
        }

        // Main initialization function to ensure all components are properly set up
        document.addEventListener('DOMContentLoaded', function() {
            // Optimize: Only initialize if chat elements exist
            if (document.querySelector('.chat-container')) {
                // Run heavy initialization here
                
                // Ensure media sections exist on page load
                createMediaSections();
                
                // Initialize message synchronization
                syncMessagesAcrossViews();
                
                console.log('Chat system initialization complete');
            } else {
                console.log('Chat not present, skipping init');
            }
        });