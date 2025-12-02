<?php
require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

require_once __DIR__ . '/../models/FriendModel.php';
$friendModel = new FriendModel();
$incomingFriendRequests = $friendModel->getIncomingRequests($userId);

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Q&A - Hanthane</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/questions.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body class="page-questions">
    <?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <main>
        <div class="container questions-layout">
            <?php 
            (function() {
                $activeSidebar = 'popular';
                include __DIR__ . '/templates/left-sidebar.php';
            })();
            ?>

            <div class="middle">
                <div class="questions-container-full">
                    <!-- Main Content -->
                    <div class="questions-main">
                        <div class="questions-header">
                            <h1>Community Q&A</h1>
                            <div class="search-box">
                                <i class="uil uil-search"></i>
                                <input type="text" placeholder="Search questions..." id="searchInput" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" aria-label="Search questions">
                            </div>
                        </div>
                        
                        <div class="questions-list">
                            <?php if (empty($questions)): ?>
                                <div class="empty-state">
                                    <i class="uil uil-comment-question"></i>
                                    <h3>No questions found</h3>
                                    <p>Be the first to ask a question!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($questions as $q): ?>
                                    <?php
                                        $searchBlob = trim(
                                            ($q['title'] ?? '') . ' ' .
                                            strip_tags($q['content'] ?? '') . ' ' .
                                            ($q['topics'] ?? '') . ' ' .
                                            ($q['category'] ?? '')
                                        );
                                        $normalizedSearchBlob = function_exists('mb_strtolower')
                                            ? mb_strtolower($searchBlob)
                                            : strtolower($searchBlob);
                                    ?>
                                    <article class="question-card" data-search-text="<?php echo htmlspecialchars($normalizedSearchBlob, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="question-card-head">
                                            <div class="question-author">
                                                <img src="<?php echo BASE_PATH . ($q['profile_picture'] ?: 'public/images/default-avatar.png'); ?>" 
                                                     alt="<?php echo htmlspecialchars($q['first_name']); ?>">
                                                <div>
                                                    <span class="author-name"><?php echo htmlspecialchars($q['first_name'] . ' ' . $q['last_name']); ?></span>
                                                    <small class="question-time"><?php echo timeAgo($q['created_at']); ?></small>
                                                </div>
                                            </div>
                                            <div class="question-card-meta">
                                                <span><i class="uil uil-eye"></i> <?php echo $q['views']; ?> views</span>
                                            </div>
                                        </div>

                                        <h2 class="question-title">
                                            <a href="<?php echo BASE_PATH; ?>index.php?controller=Popular&action=view&id=<?php echo $q['question_id']; ?>">
                                                <?php echo htmlspecialchars($q['title']); ?>
                                            </a>
                                        </h2>

                                        <?php if (!empty($q['content'])): ?>
                                            <p class="question-excerpt">
                                                <?php 
                                                $content = $q['content'];
                                                // Check for structured content (Problem, Context, etc.)
                                                if (preg_match('/Problem:\s*(.*?)\s*(?:Context:|Attempts:|Expected Outcome:|$)/is', $content, $matches)) {
                                                    $displayContent = trim($matches[1]);
                                                    // Add ellipsis if truncated
                                                    if (strlen($content) > strlen($displayContent) + 20) {
                                                        $displayContent .= '...';
                                                    }
                                                    echo htmlspecialchars($displayContent);
                                                } else {
                                                    echo htmlspecialchars(mb_substr($content, 0, 200));
                                                    echo mb_strlen($content) > 200 ? '...' : '';
                                                }
                                                ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if (!empty($q['topics'])): ?>
                                            <div class="question-topics">
                                                <?php foreach (explode(',', $q['topics']) as $topic): ?>
                                                    <span class="topic-tag"><?php echo htmlspecialchars($topic); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="question-card-footer">
                                            <div class="question-card-actions">
                                                <div class="interaction-item">
                                                    <button class="vote-btn inline upvote <?php echo $q['user_vote'] === 'upvote' ? 'active' : ''; ?>" 
                                                            data-question-id="<?php echo $q['question_id']; ?>">
                                                        <i class="uil uil-arrow-up"></i>
                                                    </button>
                                                    <span class="interaction-count" aria-label="Upvotes"><?php echo (int) $q['upvote_count']; ?></span>
                                                </div>
                                                <div class="interaction-item">
                                                    <button class="vote-btn inline downvote <?php echo $q['user_vote'] === 'downvote' ? 'active' : ''; ?>"
                                                            data-question-id="<?php echo $q['question_id']; ?>">
                                                        <i class="uil uil-arrow-down"></i>
                                                    </button>
                                                    <span class="interaction-count" aria-label="Downvotes"><?php echo (int) $q['downvote_count']; ?></span>
                                                </div>
                                            </div>
                                            <div class="question-card-stats">
                                                <span><i class="uil uil-comment"></i> <?php echo $q['answer_count']; ?> answers</span>
                                                <span><i class="uil uil-eye"></i> <?php echo $q['views']; ?> views</span>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                                <div class="empty-state" id="questionSearchEmpty" style="display:none;">
                                    <i class="uil uil-search"></i>
                                    <h3>No matching questions</h3>
                                    <p>Try a different keyword to find relevant questions.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right">
                <button class="btn-ask-question" id="askQuestionBtn">
                    <i class="uil uil-plus"></i> Ask Question
                </button>
                
                <div class="sidebar-section">
                    <h3>Filter by</h3>
                    <div class="filter-options">
                        <a href="?controller=Popular&action=index&sort=recent" class="filter-option <?php echo ($_GET['sort'] ?? 'recent') === 'recent' ? 'active' : ''; ?>">
                            <i class="uil uil-clock"></i> Recent
                        </a>
                        <a href="?controller=Popular&action=index&sort=popular" class="filter-option <?php echo ($_GET['sort'] ?? '') === 'popular' ? 'active' : ''; ?>">
                            <i class="uil uil-fire"></i> Popular
                        </a>
                        <a href="?controller=Popular&action=index&sort=unanswered" class="filter-option <?php echo ($_GET['sort'] ?? '') === 'unanswered' ? 'active' : ''; ?>">
                            <i class="uil uil-comment-slash"></i> Unanswered
                        </a>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h3>Categories</h3>
                    <div class="filter-options">
                        <?php foreach ($categories as $cat): ?>
                            <a href="?controller=Popular&action=index&category=<?php echo urlencode($cat); ?>" 
                               class="filter-option <?php echo ($_GET['category'] ?? '') === $cat ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="messages">
                    <div class="heading">
                        <h4>Messages</h4>
                        <i class="uil uil-edit" id="openChatWidget" style="cursor: pointer;"></i>
                    </div>
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Search messages" id="sidebarChatSearch">
                    </div>
                    <div class="message-list" id="sidebarMessageList">
                        <div class="loading-messages" style="text-align: center; padding: 1rem; color: #888;">
                            <i class="uil uil-spinner-alt" style="animation: spin 1s linear infinite;"></i>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Loading messages...</p>
                        </div>
                    </div>
                </div>

                <?php
                    $friendRequests = $incomingFriendRequests ?? [];
                    include __DIR__ . '/templates/friend-requests.php';
                ?>

                <div class="toast-container" id="toastContainer"></div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/templates/question-ask-modal.php'; ?>

    <div class="calendar-popup" id="calendarPopup">
        <div class="calendar-popup-header">
            <h4>Events</h4>
            <span id="popup-date">--</span>
        </div>
        <div class="calendar-popup-body" id="calendarEvents">
            <div class="no-events">
                <i class="uil uil-calendar-slash"></i>
                <p>No events scheduled</p>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_PATH, '/'); ?>';
        const USER_ID = <?php echo $userId; ?>;
    </script>
    <script src="./js/calender.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/questions.js"></script>
    <script src="./js/popular.js"></script>
    <script>
		// Load top 3 conversations for sidebar
		(async function loadSidebarMessages() {
			const listContainer = document.getElementById('sidebarMessageList');
			const searchInput = document.getElementById('sidebarChatSearch');
			const editIcon = document.getElementById('openChatWidget');
			
			if (!listContainer) return;
			
			try {
				const response = await fetch('<?php echo BASE_PATH; ?>index.php?controller=Chat&action=listConversations');
				const data = await response.json();
				const conversations = Array.isArray(data) ? data : (data.data || []);
				
				listContainer.innerHTML = '';
				
				if (!conversations.length) {
					listContainer.innerHTML = '<div style="text-align: center; padding: 1rem; color: #888;"><p>No messages yet</p></div>';
					return;
				}
				
				// Show only top 3
				const top3 = conversations.slice(0, 3);
				
				top3.forEach(conv => {
					const messageDiv = document.createElement('div');
					messageDiv.className = 'message';
					messageDiv.style.cursor = 'pointer';
					
					const avatarPath = conv.avatar || 'uploads/user_dp/default_user_dp.jpg';
					const fullAvatar = avatarPath.startsWith('http') ? avatarPath : '<?php echo BASE_PATH; ?>' + avatarPath;
					
					messageDiv.innerHTML = `
						<div class="profile-picture">
							<img src="${fullAvatar}" alt="${conv.display_name || 'User'}">
							${conv.is_online ? '<div class="active"></div>' : ''}
						</div>
						<div class="message-body">
							<h5>${conv.display_name || 'Unknown'}</h5>
							<p>${conv.last_message_preview || 'No messages yet'}</p>
						</div>
					`;
					
					messageDiv.addEventListener('click', () => {
						// Open chat widget
						const chatIcon = document.getElementById('chatIcon');
						if (chatIcon) chatIcon.click();
					});
					
					listContainer.appendChild(messageDiv);
				});
				
			} catch (error) {
				console.error('Failed to load sidebar messages:', error);
				listContainer.innerHTML = '<div style="text-align: center; padding: 1rem; color: #888;"><p>Failed to load messages</p></div>';
			}
			
			// Edit icon opens chat widget
			if (editIcon) {
				editIcon.addEventListener('click', () => {
					const chatIcon = document.getElementById('chatIcon');
					if (chatIcon) chatIcon.click();
				});
			}
		})();
    </script>
</body>
</html>
