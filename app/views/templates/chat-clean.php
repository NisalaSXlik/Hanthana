<?php
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../../config/config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    return;
}

require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../helpers/MediaHelper.php';

$userModel = new UserModel();
$currentUser = $userModel->findById((int)$_SESSION['user_id']);

if (!$currentUser) {
    return;
}

$fullName = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
if ($fullName === '') {
    $fullName = $currentUser['username'];
}
$avatarPath = $currentUser['profile_picture'] ?? 'uploads/user_dp/default.png';
?>

<?php if (!defined('CHAT_ASSETS_REGISTERED')): ?>
    <?php define('CHAT_ASSETS_REGISTERED', true); ?>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/chat-new.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
<?php endif; ?>

<div class="chat-clean-template" id="chatPortal"
     data-base-url="<?php echo htmlspecialchars(BASE_PATH, ENT_QUOTES, 'UTF-8'); ?>"
     data-user-id="<?php echo (int)$currentUser['user_id']; ?>"
     data-user-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
     data-user-avatar="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="chat-icon" id="chatIcon">
        <svg viewBox="0 0 24 24">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z" />
        </svg>
        <span class="chat-unread-badge" id="chatUnreadBadge"></span>
    </div>

    <div class="chat-overlay" id="chatOverlay"></div>

    <div class="chat-container card" id="chatContainer">
        <div class="chat-list-view" id="chat-list-view">
            <div class="chat-header flex align-center justify-between">
                <div class="header-info flex align-center gap-1">
                    <div class="profile-photo has-status">
                        <img src="<?php echo htmlspecialchars(MediaHelper::resolveMediaPath($avatarPath, 'uploads/user_dp/default.png'));?>" alt="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="status-dot status-dot--online"></span>
                    </div>
                    <div class="header-details">
                        <h3 class="text-bold">Messages</h3>
                        <p class="text-muted">Active now</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="action-btn" id="chatEditBtn" title="Edit conversations">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
                        </svg>
                    </button>
                    <button class="action-btn" id="chatMaximizeBtn" title="Maximize">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z" />
                        </svg>
                    </button>
                    <button class="close-btn btn" id="chatCloseBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="chat-search-section">
                <div class="chat-search-bar">
                    <i class="uil uil-search"></i>
                    <input type="text" placeholder="Search conversations or friends..." id="chatSearchInput">
                </div>
                <div class="chat-search-results" id="chat-search-results" aria-live="polite"></div>
            </div>

            <div class="users-list" id="chat-user-list">
                <div class="empty-state">
                    <p>No conversations yet. Start chatting from a profile or group.</p>
                </div>
            </div>
        </div>

        <div class="chat-conversation-view" id="chat-conversation-view">
            <div class="conversation-header flex align-center justify-between">
                <div class="header-info flex align-center gap-1">
                    <button class="back-btn" id="chatBackBtn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                        </svg>
                    </button>
                    <div class="profile-photo" id="conversation-avatar">
                        <img src="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Conversation">
                    </div>
                    <div class="header-details">
                        <h3 class="text-bold" id="conversation-name">Select a chat</h3>
                        <p class="text-muted" id="conversation-status">Choose a conversation to begin</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="action-btn" id="chatMaximizeBtnConversation" title="Maximize">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z" />
                        </svg>
                    </button>
                    <button class="close-btn btn" id="chatCloseBtnConversation">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="messages-area" id="messages-area">
                <div class="empty-state">
                    <p>Pick a conversation to see messages.</p>
                </div>
            </div>

            <div class="input-section">
                <div class="input-wrapper">
                    <button class="attach-btn" id="attachFileButton" type="button" aria-label="Attach file">
                        <i class="uil uil-paperclip"></i>
                    </button>
                    <textarea class="message-input" id="messageInput" placeholder="Type a message..." rows="1"></textarea>
                    <button class="send-btn" id="sendMessageButton">
                        <i class="uil uil-telegram-alt"></i>
                    </button>
                </div>
                <div class="attachment-preview" id="chat-attachment-preview" hidden></div>
            </div>
        </div>

        <div class="maximized-view" id="maximized-view">
            <div class="media-sidebar">
                <div class="media-header flex align-center justify-between">
                    <div class="header-info flex align-center gap-1">
                        <div class="header-details">
                            <h3 class="text-bold">Media Storage</h3>
                            <p class="text-muted" id="media-subtitle">Select a chat to view shared files</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="action-btn" id="chatMinimizeBtn" title="Minimize">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z" />
                            </svg>
                        </button>
                        <button class="close-btn btn" id="chatCloseBtnMax">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                            </svg>
                        </button>
                    </div>
                </div>

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

                <div class="media-content-wrapper" id="media-content-wrapper" style="display: none;">
                    <div class="media-tabs">
                        <div class="media-tab active" data-tab="media">Media</div>
                        <div class="media-tab" data-tab="files">Files</div>
                    </div>
                    <div class="media-content">
                        <div class="media-toolbar">
                            <div class="toolbar-left"></div>
                            <div class="toolbar-right">
                                <div class="search-bar">
                                    <i class="uil uil-search"></i>
                                    <input type="text" placeholder="Search photos, videos, files..." id="mediaSearch">
                                    <button class="search-clear" id="mediaSearchClear" title="Clear search">
                                        <i class="uil uil-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="media-grid" id="media-grid">
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
                            <div class="storage-info">
                                <div class="storage-usage">
                                    <div class="usage-bar">
                                        <div class="usage-fill" style="width: 0"></div>
                                    </div>
                                    <div class="usage-text">
                                        <span>0 MB used of 500 MB</span>
                                        <span class="usage-percentage">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="files-content" class="files-content" style="display: none;">
                            <div class="files-toolbar">
                                <div class="toolbar-actions">
                                    <button class="btn btn-primary btn-sm" id="createFolderBtn">
                                        <i class="uil uil-folder-plus"></i> New Folder
                                    </button>
                                    <button class="btn btn-secondary btn-sm" id="uploadFileBtn">
                                        <i class="uil uil-upload"></i> Upload File
                                    </button>
                                    <input type="file" id="fileUploadInput" style="display: none;" multiple>
                                </div>
                                <div class="breadcrumb-path">
                                    <button class="path-nav-btn" id="navBackBtn" title="Go back">
                                        <i class="uil uil-arrow-left"></i>
                                    </button>
                                    <button class="path-nav-btn" id="navForwardBtn" title="Go forward">
                                        <i class="uil uil-arrow-right"></i>
                                    </button>
                                    <button class="path-nav-btn" id="navHomeBtn" title="Go to root">
                                        <i class="uil uil-home"></i>
                                    </button>
                                    <span class="path-separator">/</span>
                                    <span class="path-item active" id="currentFolderPath">All Files</span>
                                </div>
                            </div>
                            <div class="files-grid" id="filesGrid">
                                <!-- Files will be loaded dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-main-maximized">
                <div class="chat-list-maximized" id="chat-list-maximized">
                    <div class="chat-list-header flex align-center justify-between">
                        <div class="header-info">
                            <h3 class="text-bold">All Conversations</h3>
                            <p class="text-muted">Select a chat to view messages and media</p>
                        </div>
                        <div class="search-section-max">
                            <div class="search-bar">
                                <i class="uil uil-search"></i>
                                <input type="text" placeholder="Search conversations..." id="chatSearchInputMax">
                            </div>
                        </div>
                    </div>
                    <div class="users-list-maximized" id="chat-user-list-max">
                        <div class="empty-state">
                            <p>No conversations found.</p>
                        </div>
                    </div>
                </div>

                <div class="chat-conversation-maximized" id="chat-conversation-maximized">
                    <div class="conversation-header-max flex align-center justify-between">
                        <div class="header-info flex align-center gap-1">
                            <button class="back-btn-max" id="chatBackBtnMax">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" />
                                </svg>
                            </button>
                            <div class="profile-photo" id="conversation-avatar-max">
                                <img src="<?php echo htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Conversation">
                            </div>
                            <div class="header-details">
                                <h3 class="text-bold" id="conversation-name-max">Select a chat</h3>
                                <p class="text-muted" id="conversation-status-max">Pick a conversation to start chatting</p>
                            </div>
                        </div>
                        <div class="header-actions">
                            <button class="action-btn" id="chatMinimizeBtnSecondary" title="Minimize">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z" />
                                </svg>
                            </button>
                            <button class="close-btn btn" id="chatCloseBtnMaxSecondary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="messages-area" id="maximized-messages-area">
                        <div class="empty-state">
                            <p>Choose a chat to load messages.</p>
                        </div>
                    </div>
                    <div class="message-input-area-max">
                        <div class="input-wrapper">
                            <textarea class="message-input" id="maximized-message-input" placeholder="Type a message..." rows="1"></textarea>
                            <div class="input-actions">
                                <button class="attach-btn" id="attachFileButtonMax" type="button" aria-label="Attach file">
                                    <i class="uil uil-paperclip"></i>
                                </button>
                                <button class="send-btn" id="sendMessageButtonMax">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="attachment-preview" id="chat-attachment-preview-max" hidden></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="file"
       id="chatAttachmentInput"
       accept="image/*,video/*,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip"
       style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" />

<?php if (!defined('CHAT_SCRIPT_REGISTERED')): ?>
    <?php define('CHAT_SCRIPT_REGISTERED', true); ?>
    <script src="./js/chat-new.js"></script>
<?php endif; ?>
