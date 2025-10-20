<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Popup with Media</title>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <style>

    </style>
</head>
<body>

    <!-- Chat Icon -->
    <div class="chat-icon" id="chatIcon" onclick="openChat()">
        <svg viewBox="0 0 24 24">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
        </svg>
    </div>

    <!-- Chat Overlay -->
    <div class="chat-overlay" id="chatOverlay" onclick="closeChat()"></div>

    <!-- Chat Container -->
    <div class="chat-container card" id="chatContainer">
        
        <!-- Chat List View -->
        <div class="chat-list-view" id="chat-list-view">
            <div class="chat-header flex align-center justify-between">
                <div class="header-info flex align-center gap-1">
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=10" alt="Your Profile">
                    </div>
                    <div class="header-details">
                        <h3 class="text-bold">Messages</h3>
                        <p class="text-muted">Active now</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="action-btn" onclick="toggleMaximize()" title="Maximize">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
                        </svg>
                    </button>
                    <button class="close-btn btn" onclick="closeChat()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="search-section">
                <div class="search-bar">
                    <i class="uil uil-search"></i>
                    <input type="text" placeholder="Search users...">
                </div>
            </div>
            
            <div class="users-list">
                <div class="user-item flex align-center" onclick="openConversation('Andrew Neil')" data-user="andrew">
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=1" alt="Andrew Neil">
                        <div class="new-message-indicator"></div>
                        <div class="active-indicator"></div>
                    </div>
                    <div class="user-info">
                        <h4>Andrew Neil</h4>
                        <p>Hey! How's your day going?</p>
                    </div>
                    <div class="user-status">
                        <span class="message-time">2m</span>
                        <div class="message-count">3</div>
                    </div>
                </div>
                
                <div class="user-item flex align-center" onclick="openConversation('Sarah Anderson')" data-user="sarah">
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=2" alt="Sarah Anderson">
                        <div class="new-message-indicator"></div>
                        <div class="active-indicator"></div>
                    </div>
                    <div class="user-info">
                        <h4>Sarah Anderson</h4>
                        <p>Did you see the new design?</p>
                    </div>
                    <div class="user-status">
                        <span class="message-time">5m</span>
                        <div class="message-count">1</div>
                    </div>
                </div>
                
                <div class="user-item flex align-center" onclick="openConversation('Mike Johnson')" data-user="mike">
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=3" alt="Mike Johnson">
                    </div>
                    <div class="user-info">
                        <h4>Mike Johnson</h4>
                        <p>Meeting at 3 PM?</p>
                    </div>
                    <div class="user-status">
                        <span class="message-time">10m</span>
                    </div>
                </div>
                
                <div class="user-item flex align-center" onclick="openConversation('Emma Wilson')" data-user="emma">
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=4" alt="Emma Wilson">
                        <div class="active-indicator"></div>
                    </div>
                    <div class="user-info">
                        <h4>Emma Wilson</h4>
                        <p>Thanks for the help yesterday!</p>
                    </div>
                    <div class="user-status">
                        <span class="message-time">1h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Conversation View -->
        <div class="chat-conversation-view" id="chat-conversation-view">
            <div class="conversation-header flex align-center justify-between">
                <div class="header-info flex align-center gap-1">
                    <button class="back-btn" onclick="backToList()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                        </svg>
                    </button>
                    <div class="profile-photo" id="conversation-avatar">
                        <img src="https://i.pravatar.cc/150?img=1" alt="User">
                    </div>
                    <div class="header-details">
                        <h3 class="text-bold" id="conversation-name">Andrew Neil</h3>
                        <p class="text-muted">Active now</p>
                    </div>
                </div>
                <div class="header-actions">
                    <!-- These three icons are removed for private chats - they should only be in group chats -->
                    <button class="action-btn" onclick="toggleMaximize()" title="Maximize">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
                        </svg>
                    </button>
                    <button class="close-btn btn" onclick="closeChat()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="messages-area" id="messages-area">
                <div class="message">
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=1" alt="Andrew Neil">
                    </div>
                    <div class="message-content">
                        Hey! How's your day going?
                    </div>
                </div>
                
                <div class="message own">
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=10" alt="You">
                    </div>
                    <div class="message-content">
                        It's going great! Just working on some projects.
                    </div>
                </div>
                
                <div class="message">
                    <div class="profile-photo">
                        <img src="https://i.pravatar.cc/150?img=1" alt="Andrew Neil">
                    </div>
                    <div class="message-content">
                        That sounds awesome! What kind of projects?
                    </div>
                </div>
            </div>
            
            <div class="input-section">
                <div class="input-wrapper flex align-center gap-1">
                    <button class="attach-btn" onclick="attachFile()" title="Attach file">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21.586 10.461l-10.05 10.075c-1.35 1.35-3.54 1.35-4.89 0s-1.35-3.54 0-4.89l10.05-10.075c.787-.787 2.062-.787 2.85 0s.787 2.062 0 2.85L9.51 18.511c-.283.283-.746.283-1.029 0s-.283-.746 0-1.029L17.4 8.564c.39-.39.39-1.02 0-1.41s-1.02-.39-1.41 0L7.071 16.072c-1.166 1.166-1.166 3.062 0 4.228s3.062 1.166 4.228 0L21.364 10.225c1.734-1.734 1.734-4.541 0-6.275s-4.541-1.734-6.275 0L5.04 13.989c-.39.39-.39 1.02 0 1.41s1.02.39 1.41 0L16.489 5.364c.39-.39 1.02-.39 1.41 0s.39 1.02 0 1.41L8.454 16.29c-.283.283-.283.746 0 1.029s.746.283 1.029 0L19.532 7.243c1.166-1.166 1.166-3.062 0-4.228s-3.062-1.166-4.228 0L5.255 12.964c-1.734 1.734-1.734 4.541 0 6.275s4.541 1.734 6.275 0l10.05-10.075c.39-.39.39-1.02 0-1.41s-1.02-.39-1.41 0z"/>
                        </svg>
                    </button>
                    <textarea 
                        class="message-input" 
                        id="messageInput" 
                        placeholder="Type a message..." 
                        rows="1"
                        onkeypress="if(event.key==='Enter' && !event.shiftKey) { event.preventDefault(); sendMessage(); }"
                    ></textarea>
                    <button class="send-btn" onclick="sendMessage()">
                        <svg viewBox="0 0 24 24">
                            <path d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Maximized View with Media Sidebar -->
        <div class="maximized-view" id="maximized-view">
            <!-- Left Side: Media Storage -->
            <div class="media-sidebar">
                <div class="media-header flex align-center justify-between">
                    <div class="header-info flex align-center gap-1">
                        <div class="header-details">
                            <h3 class="text-bold">Media Storage</h3>
                            <p class="text-muted" id="media-subtitle">Select a chat to view shared files</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="action-btn" onclick="toggleMaximize()" title="Minimize">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/>
                            </svg>
                        </button>
                        <button class="close-btn btn" onclick="closeChat()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Default Empty State -->
                <div class="media-empty-state" id="media-empty-state">
                    <div class="empty-state-content">
                        <div class="empty-state-icon">
                            <i class="uil uil-folder-open"></i>
                        </div>
                        <h4>No Chat Selected</h4>
                        <p>Choose a conversation from the right to view shared media, files, and links</p>
                        <div class="empty-state-features">
                            <div class="feature-item">
                                <i class="uil uil-image"></i>
                                <span>Photos & Videos</span>
                            </div>
                            <div class="feature-item">
                                <i class="uil uil-file-alt"></i>
                                <span>Documents</span>
                            </div>
                            <div class="feature-item">
                                <i class="uil uil-link-alt"></i>
                                <span>Shared Links</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Content (Hidden by default) -->
                <div class="media-content-wrapper" id="media-content-wrapper" style="display: none;">
                    <div class="media-tabs">
                        <div class="media-tab active" data-tab="media">Media</div>
                        <div class="media-tab" data-tab="files">Files</div>
                    </div>
                    
                    <div class="media-content">
                        <!-- Media Storage Toolbar -->
                        <div class="media-toolbar">
                            <div class="toolbar-left">
                                <!-- Three toolbar buttons removed -->
                            </div>
                            <div class="toolbar-right">
                                <div class="search-bar">
                                    <i class="uil uil-search"></i>
                                    <input type="text" placeholder="Search photos, videos, files..." id="mediaSearch">
                                    <button class="search-clear" onclick="clearMediaSearch()" title="Clear search" style="display: none;">
                                        <i class="uil uil-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Breadcrumb Navigation -->
                        <div class="breadcrumb-nav">
                            <span class="breadcrumb-item active" onclick="navigateToFolder('')">
                                <i class="uil uil-home"></i> Home
                            </span>
                            <span class="breadcrumb-separator">/</span>
                            <span class="breadcrumb-item" id="currentPath">All Media</span>
                        </div>

                        <!-- Folder Structure -->
                        <div class="media-grid" id="media-grid">
                            <!-- Media sections for file organization -->
                            <div class="media-section">
                                <h4>Photos</h4>
                                <div class="media-photos" id="media-photos"></div>
                            </div>
                            <div class="media-section">
                                <h4>Videos</h4>
                                <div class="media-videos" id="media-videos"></div>
                            </div>
                            <div class="media-section">
                                <h4>Documents</h4>
                                <div class="media-documents" id="media-documents"></div>
                            </div>
                        </div>

                        <!-- Storage Info -->
                        <div class="storage-info">
                            <div class="storage-usage">
                                <div class="usage-bar">
                                    <div class="usage-fill" style="width: 68%"></div>
                                </div>
                                <div class="usage-text">
                                    <span>340 MB used of 500 MB</span>
                                    <span class="usage-percentage">68%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div id="files-content" style="display: none;">
                            <p>No files shared yet.</p>
                        </div>
                        
                        <div id="links-content" style="display: none;">
                            <p>No links shared yet.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side: Chat Area -->
            <div class="chat-main-maximized">
                <!-- Chat List View (Default) -->
                <div class="chat-list-maximized" id="chat-list-maximized">
                    <div class="chat-list-header flex align-center justify-between">
                        <div class="header-info">
                            <h3 class="text-bold">All Conversations</h3>
                            <p class="text-muted">Select a chat to view messages and media</p>
                        </div>
                        <div class="search-section-max">
                            <div class="search-bar">
                                <i class="uil uil-search"></i>
                                <input type="text" placeholder="Search conversations...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="users-list-maximized">
                        <div class="user-item-max flex align-center" onclick="selectChatInMaximized('Andrew Neil', 'andrew')" data-user="andrew">
                            <div class="profile-photo">
                                <img src="https://i.pravatar.cc/150?img=1" alt="Andrew Neil">
                                <div class="new-message-indicator"></div>
                                <div class="active-indicator"></div>
                            </div>
                            <div class="user-info-max">
                                <div class="user-details">
                                    <h4>Andrew Neil</h4>
                                    <p>Hey! How's your day going?</p>
                                </div>
                                <div class="chat-meta">
                                    <span class="message-time">2m</span>
                                    <div class="message-count">3</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="user-item-max flex align-center" onclick="selectChatInMaximized('Sarah Anderson', 'sarah')" data-user="sarah">
                            <div class="profile-photo">
                                <img src="https://i.pravatar.cc/150?img=2" alt="Sarah Anderson">
                                <div class="new-message-indicator"></div>
                                <div class="active-indicator"></div>
                            </div>
                            <div class="user-info-max">
                                <div class="user-details">
                                    <h4>Sarah Anderson</h4>
                                    <p>Did you see the new design?</p>
                                </div>
                                <div class="chat-meta">
                                    <span class="message-time">5m</span>
                                    <div class="message-count">1</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="user-item-max flex align-center" onclick="selectChatInMaximized('Mike Johnson', 'mike')" data-user="mike">
                            <div class="profile-photo">
                                <img src="https://i.pravatar.cc/150?img=3" alt="Mike Johnson">
                            </div>
                            <div class="user-info-max">
                                <div class="user-details">
                                    <h4>Mike Johnson</h4>
                                    <p>Meeting at 3 PM?</p>
                                </div>
                                <div class="chat-meta">
                                    <span class="message-time">10m</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="user-item-max flex align-center" onclick="selectChatInMaximized('Emma Wilson', 'emma')" data-user="emma">
                            <div class="profile-photo">
                                <img src="https://i.pravatar.cc/150?img=4" alt="Emma Wilson">
                                <div class="active-indicator"></div>
                            </div>
                            <div class="user-info-max">
                                <div class="user-details">
                                    <h4>Emma Wilson</h4>
                                    <p>Thanks for the help yesterday!</p>
                                </div>
                                <div class="chat-meta">
                                    <span class="message-time">1h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Conversation View (When chat is selected) -->
                <div class="chat-conversation-maximized" id="chat-conversation-maximized" style="display: none;">
                    <div class="conversation-header-max flex align-center justify-between">
                        <div class="header-info flex align-center gap-1">
                            <button class="back-btn-max" onclick="backToChatList()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                                </svg>
                            </button>
                            <div class="profile-photo" id="conversation-avatar-max">
                                <img src="https://i.pravatar.cc/150?img=1" alt="User">
                            </div>
                            <div class="header-details">
                                <h3 class="text-bold" id="conversation-name-max">Chat</h3>
                                <p class="text-muted">Active now</p>
                            </div>
                        </div>
                        <div class="header-actions">
                            <!-- These three icons are removed for private chats - they should only be in group chats -->
                            <button class="action-btn" onclick="toggleMaximize()" title="Minimize">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/>
                                </svg>
                            </button>
                            <button class="close-btn btn" onclick="closeChat()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="messages-area-max" id="messages-area-max">
                        <!-- Messages will be loaded here dynamically -->
                    </div>
                    
                    <div class="message-input-area-max">
                        <div class="input-wrapper">
                            <input type="text" placeholder="Type a message..." id="messageInput-max">
                            <div class="input-actions">
                                <button class="attach-btn" onclick="attachFile()" title="Attach file">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M21.586 10.461l-10.05 10.075c-1.35 1.35-3.54 1.35-4.89 0s-1.35-3.54 0-4.89l10.05-10.075c.787-.787 2.062-.787 2.85 0s.787 2.062 0 2.85L9.51 18.511c-.283.283-.746.283-1.029 0s-.283-.746 0-1.029L17.4 8.564c.39-.39.39-1.02 0-1.41s-1.02-.39-1.41 0L7.071 16.072c-1.166 1.166-1.166 3.062 0 4.228s3.062 1.166 4.228 0L21.364 10.225c1.734-1.734 1.734-4.541 0-6.275s-4.541-1.734-6.275 0L5.04 13.989c-.39.39-.39 1.02 0 1.41s1.02.39 1.41 0L16.489 5.364c.39-.39 1.02-.39 1.41 0s.39 1.02 0 1.41L8.454 16.29c-.283.283-.283.746 0 1.029s.746.283 1.029 0L19.532 7.243c1.166-1.166 1.166-3.062 0-4.228s-3.062-1.166-4.228 0L5.255 12.964c-1.734 1.734-1.734 4.541 0 6.275s4.541 1.734 6.275 0l10.05-10.075c.39-.39.39-1.02 0-1.41s-1.02-.39-1.41 0z"/>
                                    </svg>
                                </button>
                                <button class="send-btn" onclick="sendMessageMax()">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Custom Folder Creation Popup -->
    <div class="popup-overlay" id="folderPopupOverlay" style="display: none;" onclick="handlePopupOverlayClick(event)">
        <div class="folder-popup" onclick="event.stopPropagation()">
            <div class="popup-header">
                <h3>Create New Folder</h3>
                <button class="popup-close" onclick="closeFolderPopup()">
                    <i class="uil uil-times"></i>
                </button>
            </div>
            <div class="popup-content">
                <div class="folder-preview">
                    <div class="folder-icon-large">
                        <i class="uil uil-folder"></i>
                    </div>
                    <div class="folder-preview-name" id="folderPreviewName">New Folder</div>
                </div>
                <div class="input-group">
                    <label for="folderNameInput">Folder Name</label>
                    <input type="text" id="folderNameInput" placeholder="Enter folder name..." maxlength="50" autocomplete="off">
                    <div class="input-helper">
                        <span class="char-count" id="charCount">0/50</span>
                        <span class="error-msg" id="errorMsg" style="display: none;"></span>
                    </div>
                </div>
                <div class="folder-options">
                    <div class="option-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="createSubfolders" checked>
                            <span class="checkmark"></span>
                            Create default subfolders (Photos, Videos, Documents)
                        </label>
                    </div>
                    <div class="option-group">
                        <label class="color-label">Folder Color:</label>
                        <div class="color-options">
                            <div class="color-option selected" data-color="default" style="background: #4a90e2;"></div>
                            <div class="color-option" data-color="red" style="background: #e74c3c;"></div>
                            <div class="color-option" data-color="green" style="background: #2ecc71;"></div>
                            <div class="color-option" data-color="orange" style="background: #f39c12;"></div>
                            <div class="color-option" data-color="purple" style="background: #9b59b6;"></div>
                            <div class="color-option" data-color="pink" style="background: #e91e63;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="popup-footer">
                <button class="btn-secondary" onclick="closeFolderPopup()">Cancel</button>
                <button class="btn-primary" onclick="createFolderFromPopup()" id="createBtn" disabled>Create Folder</button>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="public/css/general.css">
    <link rel="stylesheet" href="public/css/chat-new.css">
    <script src="public/js/chat-new.js"></script>
</body>
</html>