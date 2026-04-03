<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/MediaHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($currentUser) || !is_array($currentUser)) {
    $currentUser = null;
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/../../models/UserModel.php';
        $userModel = new UserModel();
        $resolvedUser = $userModel->findById((int)$_SESSION['user_id']);
        if (is_array($resolvedUser)) {
            $currentUser = $resolvedUser;
        }
    }
}

$currentUser = is_array($currentUser) ? $currentUser : [];

$currentUserAvatar = MediaHelper::resolveMediaPath($currentUser['profile_picture'] ?? '', 'uploads/user_dp/default.png');
$showPostModal = !isset($hidePostModal) || !$hidePostModal;
// Load notifications for logged in user
$notifications = [];
$unreadCount = 0;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../../app/models/NotificationsModel.php';
    $notifModel = new NotificationsModel();
    $notifications = $notifModel->getLatestNotifications((int)$_SESSION['user_id'], 8);
    $unreadCount = $notifModel->countUnread((int)$_SESSION['user_id']);
}
?>
<nav>
    <div class="container">
        <div class="nav-left">
            <h2 class="logo">Hanthana</h2>
        </div>
        <div class="nav-center">
            <div class="nav-search">
                <div class="search-bar">
                    <i class="uil uil-search"></i>
                    <input
                        type="search"
                        id="navSearchInput"
                        placeholder="Search Hanthana"
                        data-base-path="<?php echo htmlspecialchars(BASE_PATH); ?>"
                        autocomplete="off"
                    >
                </div>
                <div class="nav-search-results hidden" id="navSearchResults"></div>
            </div>
        </div>
        <div class="nav-right">
            <?php if ($showPostModal): ?>
                <button class="btn btn-primary" id="openPostModal" data-trigger="post-modal">Create</button>
            <?php endif; ?>
            <div class="calendar-icon">
                <i class="uil uil-calendar-alt"></i>
                <div class="calendar-popup" id="calendar-popup">
                    <div class="cal">
                        <div class="calender">
                            <div class="month">
                                <i class="uil uil-angle-left prev"></i>
                                <div class="date"></div>
                                <i class="uil uil-angle-right next"></i>
                            </div>
                            <div class="weekdays">
                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>
                            </div>
                            <div class="days"></div>
                            <div class="goto-today">
                                <div class="goto">
                                    <input type="text" placeholder="mm/yyyy" class="date-input">
                                    <button class="goto-btn">Go</button>
                                </div>
                                <button class="today-btn">Today</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($showPostModal): ?>
                <!-- Post Creation Modal with 3 tabs -->
                <div class="post-modal" id="postModal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Create New Post</h3>
                            <button class="close-modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <!-- Tab buttons -->
                            <div class="post-type-selector">
                                <button class="post-type-btn active" data-type="general">General Post</button>
                                <button class="post-type-btn" data-type="event">Event Post</button>
                                <button class="post-type-btn" data-type="question">Question Post</button>
                            </div>
                            
                            <!-- ===== GENERAL POST FIELDS ===== -->
                            <div id="generalFields" style="display: block;">
                                <div class="image-upload">
                                    <i class="uil uil-image-upload"></i>
                                    <p>Drag photos and videos here or click to browse</p>
                                    <input type="file" id="postImage" accept="image/*" style="display: none;">
                                </div>
                                
                                <div class="post-details">
                                    <div class="form-group">
                                        <label for="postCaption">Caption</label>
                                        <textarea id="postCaption" placeholder="Write a caption..."></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="postTags">Tags (minimum 5, separated by commas)</label>
                                        <input type="text" id="postTags" placeholder="e.g., travel, srilanka, beach, vacation, sunset">
                                        <small class="tag-count">0/5 tags</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ===== EVENT POST FIELDS ===== -->
                            <div id="eventFields" style="display: none;">
                                <div class="form-group">
                                    <label for="createEventTitle">Event Title <span class="required">*</span></label>
                                    <input type="text" id="createEventTitle" placeholder="Enter event title">
                                </div>

                                <div class="form-group">
                                    <label>Event Image</label>
                                    <div class="event-image-upload" id="eventImageUpload">
                                        <i class="uil uil-image-upload"></i>
                                        <p id="eventImageLabel">Click to add event image</p>
                                    </div>
                                    <input type="file" id="eventPostImage" accept="image/*" style="display: none;">
                                </div>

                                <div class="form-group">
                                    <label for="createEventDescription">Description</label>
                                    <textarea id="createEventDescription" rows="4" placeholder="Describe your event..."></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="createEventDate">Date <span class="required">*</span></label>
                                    <input type="date" id="createEventDate">
                                </div>

                                <div class="form-group">
                                    <label for="createEventTime">Time</label>
                                    <input type="time" id="createEventTime">
                                </div>

                                <div class="form-group">
                                    <label for="createEventLocation">Location</label>
                                    <input type="text" id="createEventLocation" placeholder="Enter location">
                                </div>
                            </div>
                            
                            <!-- ===== QUESTION POST FIELDS ===== -->
                            <div id="questionFields" style="display: none;">
                                <section class="template-section">
                                    <div class="template-label-row">
                                        <label>Question style</label>
                                        <small>Select one to pre-fill your title</small>
                                    </div>
                                    <div class="question-type-grid" role="list">
                                        <button type="button" class="template-chip active" data-template-prefix="How do I">How do I...</button>
                                        <button type="button" class="template-chip" data-template-prefix="Why does">Why does...</button>
                                        <button type="button" class="template-chip" data-template-prefix="What is">What is...</button>
                                        <button type="button" class="template-chip" data-template-prefix="Best way to">Best way to...</button>
                                        <button type="button" class="template-chip" data-template-prefix="Troubleshooting">Troubleshooting...</button>
                                    </div>
                                </section>

                                <div class="form-group">
                                    <label>Question title <span class="required">*</span></label>
                                    <input type="text" name="title" id="questionTitleInput" placeholder="Summarize your question in one sentence">
                                    <small>Example: "How do I create a modal popup in PHP?"</small>
                                </div>

                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category" id="questionCategory">
                                        <option value="General">General</option>
                                        <option value="Technology">Technology</option>
                                        <option value="Science">Science</option>
                                        <option value="Education">Education</option>
                                        <option value="Health">Health</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Career">Career</option>
                                        <option value="Lifestyle">Lifestyle</option>
                                        <option value="Travel">Travel</option>
                                        <option value="Entertainment">Entertainment</option>
                                        <option value="Sports">Sports</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>What problem are you facing? <span class="required">*</span></label>
                                    <textarea name="problem_statement" id="problemStatement" maxlength="400" placeholder="Describe the exact issue, error messages, or blockers."></textarea>
                                    <div class="char-count" data-for="problem_statement">0 / 400</div>
                                </div>

                                <div class="form-group">
                                    <label>Topics (comma separated)</label>
                                    <input type="text" name="topics" id="questionTopics" placeholder="e.g., php, mysql, async">
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button class="btn btn-secondary cancel-btn">Cancel</button>
                            <button class="btn btn-primary share-btn">Share</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="notification">
                <i class="uil uil-bell">
                    <small class="notification-count"><?php echo $unreadCount > 9 ? '9+' : ($unreadCount > 0 ? (int)$unreadCount : ''); ?></small>
                </i>
                <div class="notifications-popup">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $n): ?>
                            <?php
                                $actionUrl = $n['action_url'] ?? '';
                                $nid = (int)$n['notification_id'];
                                $priority = htmlspecialchars($n['priority'] ?? 'medium');
                                $typeLabel = ucwords(str_replace('_', ' ', $n['type'] ?? 'Update'));
                                $triggerPic = $n['trigger_profile_picture'] ?? '';
                                $imgUrl = MediaHelper::resolveMediaPath($triggerPic, 'uploads/user_dp/default.png');
                                $isUnread = empty($n['is_read']);
                            ?>
                            <div class="notification-item-wrap<?php echo $isUnread ? ' is-unread' : ''; ?>" data-notif-id="<?php echo $nid; ?>" data-priority="<?php echo $priority; ?>">
                                <a href="#" class="notification-item" data-action-url="<?php echo htmlspecialchars($actionUrl); ?>" data-notif-id="<?php echo $nid; ?>">
                                    <div class="notification-avatar">
                                        <div class="profile-picture">
                                            <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Trigger avatar">
                                        </div>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-meta">
                                            <span class="notification-chip"><?php echo htmlspecialchars($typeLabel); ?></span>
                                            <small class="notification-time"><?php echo htmlspecialchars(date('M j, H:i', strtotime($n['created_at']))); ?></small>
                                        </div>
                                        <p class="notification-title"><?php echo htmlspecialchars($n['title'] ?? ''); ?></p>
                                        <p class="notification-message"><?php echo htmlspecialchars($n['message'] ?? ''); ?></p>
                                    </div>
                                </a>
                                <button class="notif-dismiss" data-notif-id="<?php echo $nid; ?>" title="Dismiss notification" aria-label="Dismiss notification">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div>
                            <div class="notification-body">
                                No notifications
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-picture" id="profileDropdown">
                <img src=".<?php echo htmlspecialchars($currentUserAvatar); ?>" alt="Your profile picture">
                <div class="profile-dropdown">
                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view<?php echo isset($_SESSION['user_id']) ? '&user_id=' . (int)$_SESSION['user_id'] : ''; ?>"><i class="uil uil-user"></i> My Profile</a>
                    <?php if (($_SESSION['role'] ?? 'user') === 'admin'): ?>
                        <a href="<?php echo BASE_PATH; ?>index.php?controller=Admin&action=index"><i class="uil uil-shield-check"></i> Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Settings&action=index"><i class="uil uil-cog"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Logout&action=index" class="logout"><i class="uil uil-signout"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>