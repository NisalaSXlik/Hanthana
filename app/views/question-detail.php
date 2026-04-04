<?php
require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
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

function parseStructuredQuestionContent(?string $content): array {
    $cleanContent = trim($content ?? '');
    if ($cleanContent === '') {
        return [];
    }

    $sections = [];
    $allowedLabels = ['Problem', 'Context', 'Attempts', 'Expected Outcome'];
    $chunks = preg_split('/\n\s*\n/', $cleanContent);

    foreach ($chunks as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') {
            continue;
        }

        $parts = preg_split('/:\s*/', $chunk, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $label = trim($parts[0]);
        $value = trim($parts[1]);

        if ($value === '' || !in_array($label, $allowedLabels, true)) {
            continue;
        }

        $sections[] = [
            'label' => $label,
            'value' => $value,
        ];
    }

    return $sections;
}

function renderAnswerNode(array $answer, int $currentUserId, int $questionOwnerId, int $level = 0): string {
    $answerId = (int)($answer['answer_id'] ?? 0);
    $authorName = htmlspecialchars(trim(($answer['first_name'] ?? '') . ' ' . ($answer['last_name'] ?? '')));
    $time = htmlspecialchars(timeAgo($answer['created_at'] ?? 'now'));
    $profilePic = BASE_PATH . ($answer['profile_picture'] ?: 'public/images/default-avatar.png');
    $content = nl2br(htmlspecialchars($answer['content'] ?? ''));
    $canModerate = (int)($answer['user_id'] ?? 0) === $currentUserId
        || $questionOwnerId === $currentUserId;
    $accepted = !empty($answer['is_accepted']);
    $replyStyle = $level > 0 ? ' style="margin-left: 40px;"' : '';

    $repliesHtml = '';
    if (!empty($answer['replies']) && is_array($answer['replies'])) {
        foreach ($answer['replies'] as $reply) {
            $repliesHtml .= renderAnswerNode($reply, $currentUserId, $questionOwnerId, $level + 1);
        }
    }

    return '
        <div class="comment' . ($level > 0 ? ' reply' : '') . '" data-answer-id="' . $answerId . '"' . $replyStyle . '>
            <div class="comment-header-info">
                <img src="' . htmlspecialchars($profilePic) . '" class="comment-avatar" alt="' . $authorName . '">
                <span class="comment-author">' . $authorName . '</span>
                <span class="comment-time">' . $time . '</span>
                ' . ($accepted ? '<span class="answer-badge">Accepted</span>' : '') . '
            </div>
            <div class="comment-text">' . $content . '</div>
            <div class="comment-actions">
                ' . ($level === 0 ? '<button class="comment-action reply-btn" data-answer-id="' . $answerId . '"><i class="fas fa-reply"></i><span>Reply</span></button>' : '') . '
                ' . ($canModerate ? '<button class="comment-action edit-answer-btn" data-answer-id="' . $answerId . '">Edit</button><button class="comment-action delete-answer-btn" data-answer-id="' . $answerId . '">Delete</button>' : '') . '
            </div>
            ' . ($level === 0 ? '<div class="reply-form" id="reply-form-' . $answerId . '"><div class="reply-input-container"><input type="text" class="reply-input" placeholder="Write a reply..." data-answer-id="' . $answerId . '"><button class="reply-submit-btn" data-answer-id="' . $answerId . '"><i class="fas fa-paper-plane"></i></button></div></div>' : '') . '
            ' . ($repliesHtml ? '<div class="comment-replies">' . $repliesHtml . '</div>' : '') . '
        </div>
    ';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($question['title']); ?> - Hanthane</title>
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/questions.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body class="page-questions">
    <?php include __DIR__ . '/templates/navbar.php'; ?>
    
    <main>
        <div class="container questions-layout">
            <?php 
            (function() {
                $activeSidebar = 'qna';
                include __DIR__ . '/templates/left-sidebar.php';
            })();
            ?>

            <div class="middle">
                <?php $structuredSections = parseStructuredQuestionContent($question['content'] ?? ''); ?>
                <div class="question-detail-container">
                    <!-- Back button -->
                    <a href="<?php echo BASE_PATH; ?>index.php?controller=QnA&action=index" class="back-link">
                        <i class="uil uil-arrow-left"></i> Back to Questions
                    </a>
                    
                    <!-- Question -->
                    <div class="question-detail-card">
                        <div class="question-header">
                            <div class="question-owner">
                                <img src="<?php echo BASE_PATH . ($question['profile_picture'] ?: 'public/images/default-avatar.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($question['first_name']); ?>">
                                <div>
                                    <strong><?php echo htmlspecialchars($question['first_name'] . ' ' . $question['last_name']); ?></strong>
                                    <span>Asked <?php echo timeAgo($question['created_at']); ?></span>
                                </div>
                            </div>
                            <div class="question-meta-stats">
                                <span><i class="uil uil-eye"></i> <?php echo $question['views']; ?> views</span>
                            </div>
                        </div>

                        <div class="question-content-block">
                            <h1><?php echo htmlspecialchars($question['title']); ?></h1>

                            <?php if (!empty($question['topics'])): ?>
                                <div class="question-topics">
                                    <?php foreach (explode(',', $question['topics']) as $topic): ?>
                                        <span class="topic-tag"><?php echo htmlspecialchars($topic); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($structuredSections)): ?>
                                <div class="question-structured-grid">
                                    <?php foreach ($structuredSections as $section): ?>
                                        <div class="question-structured-card">
                                            <span class="section-label"><?php echo htmlspecialchars($section['label']); ?></span>
                                            <p><?php echo nl2br(htmlspecialchars($section['value'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif (!empty($question['content'])): ?>
                                <div class="question-body">
                                    <?php echo nl2br(htmlspecialchars($question['content'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="question-actions">
                            <div class="interaction-item">
                                <button class="vote-btn inline upvote <?php echo $question['user_vote'] === 'upvote' ? 'active' : ''; ?>" 
                                        data-question-id="<?php echo $question['question_id']; ?>">
                                    <i class="uil uil-arrow-up"></i>
                                </button>
                                <span class="interaction-count" aria-label="Upvotes"><?php echo (int) $question['upvote_count']; ?></span>
                            </div>
                            <div class="interaction-item">
                                <button class="vote-btn inline downvote <?php echo $question['user_vote'] === 'downvote' ? 'active' : ''; ?>"
                                        data-question-id="<?php echo $question['question_id']; ?>">
                                    <i class="uil uil-arrow-down"></i>
                                </button>
                                <span class="interaction-count" aria-label="Downvotes"><?php echo (int) $question['downvote_count']; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Answers Section -->
                    <?php 
                        $answerCount = count($answers);
                    ?>
                    <div class="answers-section" id="answersSection">
                        <button class="toggle-answers-btn"
                                type="button"
                                data-targets="answersPanel"
                                aria-expanded="false"
                                data-label-show="View all <?php echo $answerCount; ?> answers"
                                data-label-hide="Hide answers">
                            <span>View all <?php echo $answerCount; ?> answers</span>
                        </button>

                        <div id="answersPanel" class="comment-section collapsed">
                            <div class="comment-header">
                                <h3>Answers</h3>
                                <button class="close-comments" type="button" id="closeAnswersBtn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="comments-container">
                                <?php if (empty($answers)): ?>
                                    <div class="no-comments">No answers yet. Be the first to answer!</div>
                                <?php else: ?>
                                    <?php foreach ($answers as $answer): ?>
                                        <?php echo renderAnswerNode($answer, $userId, (int)$question['user_id']); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="add-comment-form">
                                <form id="answerForm">
                                    <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                                    <input type="hidden" name="parent_answer_id" value="">
                                    <div class="comment-input-wrapper">
                                        <textarea name="content" class="comment-input" rows="3" placeholder="Write your answer..." required></textarea>
                                        <button type="submit" class="comment-submit-btn">Post Answer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Answer Form -->
                    <div class="answer-form-section <?php echo $shouldCollapse ? 'collapsed' : ''; ?>" id="answerFormSection">
                        <h3>Your Answer</h3>
                        <form id="answerForm" class="hf-form">
                            <input type="hidden" name="question_id" value="<?php echo $question['question_id']; ?>">
                            <textarea name="content" rows="8" placeholder="Write your answer here..." required></textarea>
                            <button type="submit" class="btn-primary">Post Answer</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="right">
                <div class="messages">
                    <div class="heading">
                        <h4>Messages</h4>
                        <i class="uil uil-edit"></i>
                    </div>
                    <form class="hf-form hf-inline" onsubmit="return false;">
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Search messages">
                    </div>
                    </form>
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
    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_PATH, '/'); ?>';
        const USER_ID = <?php echo $userId; ?>;
    </script>
    <script src="./js/calender.js?v=20250209_syntax"></script>
    <script src="./js/general.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/questions.js"></script>
    <script src="./js/answers.js"></script>
</body>
</html>
