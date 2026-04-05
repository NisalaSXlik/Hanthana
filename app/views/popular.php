<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$userModel = new UserModel;
$currentUser = $userModel->findById((int)$_SESSION['user_id']);

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
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/questions.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body class="page-questions page-popular">
    <?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <main>
        <div class="container questions-layout">
            <?php $activeSidebar = 'qna'; include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle">
                <div class="questions-container-full">
                    <!-- Main Content -->
                    <div class="questions-main">
                        <div class="questions-header">
                            <div class="questions-header-top">
                                <div class="questions-title-block">
                                    <h1><i class="uil uil-question-circle"></i> Q&amp;A</h1>
                                    <p>Ask questions, share answers, and learn with the Hanthane community</p>
                                </div>
                                <form class="hf-form hf-inline" onsubmit="return false;">
                                    <div class="search-bar">
                                        <i class="uil uil-search"></i>
                                        <input type="text" placeholder="Search questions..." id="searchInput" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" aria-label="Search questions">
                                    </div>
                                </form>
                            </div>
                            <div class="questions-header-actions">
                                <div class="filter-tabs questions-filter-tabs">
                                    <a href="?controller=QnA&action=index&sort=recent" class="filter-tab questions-filter-tab <?php echo (($_GET['sort'] ?? 'recent') === 'recent' && (($_GET['mine'] ?? '') !== '1')) ? 'active' : ''; ?>">
                                        <i class="uil uil-clock"></i> Recent
                                    </a>
                                    <a href="?controller=QnA&action=index&sort=popular" class="filter-tab questions-filter-tab <?php echo ($_GET['sort'] ?? '') === 'popular' ? 'active' : ''; ?>">
                                        <i class="uil uil-fire"></i> Popular
                                    </a>
                                    <a href="?controller=QnA&action=index&sort=my_questions" class="filter-tab questions-filter-tab <?php echo (($_GET['sort'] ?? '') === 'my_questions' || (isset($_GET['mine']) && $_GET['mine'] === '1')) ? 'active' : ''; ?>">
                                        <i class="uil uil-user"></i> My Questions
                                    </a>
                                </div>
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
                                    <?php $isOwner = (int)$q['user_id'] === (int)$currentUserId; ?>
                                    <article id="question-card-<?php echo (int)$q['question_id']; ?>" class="question-card" data-question-id="<?php echo (int)$q['question_id']; ?>" data-search-text="<?php echo htmlspecialchars($normalizedSearchBlob, ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="question-card-head">
                                            <div class="question-author">
                                                <a href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)$q['user_id']; ?>" class="question-author-link">
                                                    <img src="<?php echo BASE_PATH . ($q['profile_picture'] ?: 'public/images/default-avatar.png'); ?>"
                                                         alt="<?php echo htmlspecialchars($q['first_name']); ?>">
                                                    <div>
                                                        <span class="author-name"><?php echo htmlspecialchars($q['first_name'] . ' ' . $q['last_name']); ?></span>
                                                        <small class="question-time"><?php echo timeAgo($q['created_at']); ?></small>
                                                    </div>
                                                </a>
                                            </div>

                                            <div class="question-card-meta">
                                                <span><i class="uil uil-eye"></i> <?php echo (int)$q['views']; ?> views</span>

                                                <div class="question-menu-wrap">
                                                    <button type="button" class="question-menu-trigger" aria-label="Question menu">
                                                        <i class="uil uil-ellipsis-h"></i>
                                                    </button>

                                                    <div class="question-menu">
                                                        <?php if ($isOwner): ?>
                                                            <button type="button" class="question-menu-item edit-question" data-question-id="<?php echo (int)$q['question_id']; ?>">
                                                                <i class="uil uil-edit"></i> Edit
                                                            </button>
                                                            <button type="button" class="question-menu-item delete-question" data-question-id="<?php echo (int)$q['question_id']; ?>">
                                                                <i class="uil uil-trash-alt"></i> Delete
                                                            </button>
                                                        <?php endif; ?>

                                                        <?php if (!$isOwner): ?>
                                                            <button type="button"
                                                                    class="question-menu-item report-trigger"
                                                                    data-report-type="question"
                                                                    data-target-id="<?php echo (int)$q['question_id']; ?>"
                                                                    data-target-label="<?php echo htmlspecialchars($q['title'], ENT_QUOTES); ?>">
                                                                <i class="uil uil-exclamation-circle"></i> Report
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <h2 class="question-title">
                                            <a href="<?php echo BASE_PATH; ?>index.php?controller=QnA&action=view&id=<?php echo $q['question_id']; ?>">
                                                <?php echo htmlspecialchars($q['title']); ?>
                                            </a>
                                        </h2>

                                        <?php if (!empty($q['content'])): ?>
                                            <p class="question-excerpt">
                                                <?php
                                                $content = $q['content'];
                                                if (preg_match('/Problem:\s*(.*?)\s*(?:Context:|Attempts:|Expected Outcome:|$)/is', $content, $matches)) {
                                                    $displayContent = trim($matches[1]);
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

                                        <?php if (!empty($q['attachment_path'])): ?>
                                            <a class="question-attachment-link"
                                               href="<?php echo BASE_PATH . ltrim((string)$q['attachment_path'], '/'); ?>"
                                               download="<?php echo htmlspecialchars((string)($q['attachment_name'] ?? 'question-attachment')); ?>">
                                                <i class="uil uil-paperclip"></i>
                                                <span><?php echo htmlspecialchars((string)($q['attachment_name'] ?? 'Download attachment')); ?></span>
                                            </a>
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
                                                <button type="button"
                                                        class="question-answer-link question-answer-link-btn toggle-inline-answers"
                                                        data-question-id="<?php echo (int)$q['question_id']; ?>"
                                                        data-target="inlineAnswers-<?php echo (int)$q['question_id']; ?>"
                                                        aria-expanded="false">
                                                    <i class="uil uil-comment"></i> <?php echo (int)$q['answer_count']; ?> answers
                                                </button>
                                            </div>
                                        </div>

                                        <div id="inlineAnswers-<?php echo (int)$q['question_id']; ?>"
                                             class="inline-answers-panel collapsed"
                                             data-question-id="<?php echo (int)$q['question_id']; ?>"
                                             aria-hidden="true">
                                            <div class="inline-answers-header">
                                                <h4>Answers</h4>
                                                <button type="button" class="close-inline-answers" aria-label="Close answers">
                                                    <i class="uil uil-times"></i>
                                                </button>
                                            </div>

                                            <div class="inline-answers-list comments-container">
                                                <div class="no-comments">Loading answers...</div>
                                            </div>

                                            <div class="add-comment-form inline-answer-form-wrap">
                                                <form class="inline-answer-form">
                                                    <input type="hidden" name="question_id" value="<?php echo (int)$q['question_id']; ?>">
                                                    <input type="hidden" name="parent_answer_id" value="">
                                                    <div class="comment-input-wrapper">
                                                        <textarea name="content" class="comment-input" rows="3" placeholder="Write your answer..." required></textarea>
                                                        <button type="submit" class="comment-submit-btn">Post Answer</button>
                                                    </div>
                                                </form>
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
                    <h3>Categories</h3>
                    <div class="filter-options">
                        <?php foreach ($categories as $cat): ?>
                            <a href="?controller=QnA&action=index&category=<?php echo urlencode($cat); ?>" 
                               class="filter-option <?php echo ($_GET['category'] ?? '') === $cat ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="toast-container" id="toastContainer"></div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/templates/question-ask-modal.php'; ?>
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

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
        const USER_ID = <?php echo $currentUserId; ?>;

        (function focusQuestionCardFromQuery() {
            const params = new URLSearchParams(window.location.search);
            const focusQuestionId = params.get('focus_question');
            if (!focusQuestionId) return;

            const targetCard = document.getElementById('question-card-' + focusQuestionId);
            if (!targetCard) return;

            targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            targetCard.style.transition = 'box-shadow 0.25s ease, border-color 0.25s ease';
            targetCard.style.borderColor = 'rgba(14, 165, 233, 0.4)';
            targetCard.style.boxShadow = '0 0 0 2px rgba(14, 165, 233, 0.15)';

            setTimeout(() => {
                targetCard.style.borderColor = '';
                targetCard.style.boxShadow = '';
            }, 2200);
        })();
    </script>
    <script src="./js/calender.js?v=20250209_syntax"></script>
    <script src="./js/general.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/questions.js"></script>
    <script src="./js/popular.js"></script>
    <script src="./js/report.js"></script>
</body>
</html>
