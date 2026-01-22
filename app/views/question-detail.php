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
                <?php $structuredSections = parseStructuredQuestionContent($question['content'] ?? ''); ?>
                <div class="question-detail-container">
                    <!-- Back button -->
                    <a href="<?php echo BASE_PATH; ?>index.php?controller=Popular&action=index" class="back-link">
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
                        $shouldCollapse = true;
                    ?>
                    <div class="answers-section" id="answersSection">
                        <div class="answers-header">
                            <h2><?php echo $answerCount; ?> <?php echo $answerCount === 1 ? 'Answer' : 'Answers'; ?></h2>
                            <button class="toggle-answers-btn" type="button" data-targets="answersList answerFormSection" aria-expanded="false" data-label-show="Show answers" data-label-hide="Hide answers">
                                <i class="uil uil-comments"></i>
                                <span>Show answers</span>
                            </button>
                        </div>

                        <div id="answersList" class="answers-list <?php echo $shouldCollapse ? 'collapsed' : ''; ?>">
                            <?php if (empty($answers)): ?>
                                <div class="no-answers">
                                    <i class="uil uil-comment-slash"></i>
                                    <p>No answers yet. Be the first to answer!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($answers as $answer): ?>
                                    <div class="answer-card">
                                    <div class="answer-header">
                                        <div class="answer-author">
                                            <img src="<?php echo BASE_PATH . ($answer['profile_picture'] ?: 'public/images/default-avatar.png'); ?>" 
                                                 alt="<?php echo htmlspecialchars($answer['first_name']); ?>">
                                            <div>
                                                <strong><?php echo htmlspecialchars($answer['first_name'] . ' ' . $answer['last_name']); ?></strong>
                                                <span><?php echo timeAgo($answer['created_at']); ?></span>
                                            </div>
                                        </div>
                                        <?php if ($answer['is_accepted']): ?>
                                            <span class="answer-badge">Accepted</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="answer-body">
                                        <?php echo nl2br(htmlspecialchars($answer['content'])); ?>
                                    </div>

                                    <div class="answer-actions">
                                        <div class="interaction-item">
                                            <button class="vote-btn inline upvote <?php echo $answer['user_vote'] === 'upvote' ? 'active' : ''; ?>" 
                                                    data-answer-id="<?php echo $answer['answer_id']; ?>">
                                                <i class="uil uil-arrow-up"></i>
                                            </button>
                                            <span class="interaction-count" aria-label="Upvotes"><?php echo (int) $answer['upvote_count']; ?></span>
                                        </div>
                                        <div class="interaction-item">
                                            <button class="vote-btn inline downvote <?php echo $answer['user_vote'] === 'downvote' ? 'active' : ''; ?>"
                                                    data-answer-id="<?php echo $answer['answer_id']; ?>">
                                                <i class="uil uil-arrow-down"></i>
                                            </button>
                                            <span class="interaction-count" aria-label="Downvotes"><?php echo (int) $answer['downvote_count']; ?></span>
                                        </div>
                                    </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Answer Form -->
                    <div class="answer-form-section <?php echo $shouldCollapse ? 'collapsed' : ''; ?>" id="answerFormSection">
                        <h3>Your Answer</h3>
                        <form id="answerForm">
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
                    <div class="search-bar">
                        <i class="uil uil-search"></i>
                        <input type="search" placeholder="Search messages">
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
    <?php include __DIR__ . '/templates/chat-clean.php'; ?>

    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_PATH, '/'); ?>';
        const USER_ID = <?php echo $userId; ?>;
    </script>
    <script src="./js/calender.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/questions.js"></script>
</body>
</html>
