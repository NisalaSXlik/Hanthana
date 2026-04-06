<?php
require_once __DIR__ . '/../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acedemic Dashboard | Hanthana</title>
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css?v=20250209_zindex">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/acedemicdashboard.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/forms.css">
</head>
<body>
    <?php include __DIR__ . '/templates/navbar.php'; ?>

    <main>
        <div class="container">
            <?php $activeSidebar = 'acedemicdashboard'; include __DIR__ . '/templates/left-sidebar.php'; ?>

            <div class="middle acedemicdashboard-page">
                <section class="acd-card acd-resources-card">
                    <div class="acd-card-head">
                        <h4>Academic Resources</h4>
                        <div class="acd-tabs" role="tablist" aria-label="Academic resource filters">
                            <button type="button" class="acd-tab active" data-tab="all" aria-selected="true">All</button>
                            <button type="button" class="acd-tab" data-tab="recent_uploads" aria-selected="false">Recent Uploads</button>
                            <button type="button" class="acd-tab" data-tab="top_downloads" aria-selected="false">Top Downloads</button>
                            <button type="button" class="acd-tab" data-tab="my_saves" aria-selected="false">My Saves</button>
                        </div>
                    </div>

                    <div class="acd-breadcrumb" id="acdBreadcrumb"></div>

                    <div class="acd-resource-browser" id="acdResourceBrowser" data-base-path="<?php echo htmlspecialchars(BASE_PATH); ?>">
                        <div class="acd-pane acd-groups-pane" id="acdGroupsPane">
                            <div class="acd-pane-list" id="acdGroupsList"></div>
                        </div>

                        <div class="acd-pane acd-bins-pane" id="acdBinsPane" style="display:none;">
                            <div class="acd-pane-list" id="acdBinsList"></div>
                        </div>

                        <div class="acd-pane acd-files-pane" id="acdFilesPane" style="display:none;">
                            <div class="acd-resource-list" id="acdFilesList"></div>
                        </div>
                    </div>
                </section>

                <section class="acd-card acd-answers-card">
                    <div class="acd-section-title">
                        <h4>Answers for My Questions</h4>
                    </div>

                    <div class="acd-thread-list">
                        <?php if (!empty($myQuestionAnswers)): ?>
                            <?php foreach ($myQuestionAnswers as $qa): ?>
                                <?php
                                    $questionId = (int)($qa['question_id'] ?? 0);
                                    $questionTitle = (string)($qa['title'] ?? 'Untitled Question');
                                    $answerPreview = trim((string)($qa['latest_answer_content'] ?? ''));
                                    if (mb_strlen($answerPreview) > 180) {
                                        $answerPreview = mb_substr($answerPreview, 0, 177) . '...';
                                    }
                                    $answerPreview = $answerPreview !== '' ? $answerPreview : 'A new answer was posted on your question.';
                                    $authorName = trim((string)($qa['first_name'] ?? '') . ' ' . (string)($qa['last_name'] ?? ''));
                                    $authorName = $authorName !== '' ? $authorName : 'A user';
                                    $timeText = !empty($qa['latest_answer_at']) ? date('M j, g:i A', strtotime((string)$qa['latest_answer_at'])) : 'Recently';
                                    $openUrl = BASE_PATH . 'index.php?controller=QnA&action=view&id=' . $questionId;
                                ?>
                                <article class="acd-thread-card">
                                    <div class="acd-thread-top">
                                        <h5><?php echo htmlspecialchars($questionTitle); ?></h5>
                                        <small><?php echo htmlspecialchars($timeText); ?></small>
                                    </div>
                                    <p class="acd-thread-author">Latest answer by <?php echo htmlspecialchars($authorName); ?></p>
                                    <p class="acd-thread-preview"><?php echo htmlspecialchars($answerPreview); ?></p>
                                    <div class="acd-item-footer">
                                        <a class="acd-open-btn" href="<?php echo htmlspecialchars($openUrl); ?>">Open</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <article class="acd-thread-card">
                                <div class="acd-thread-top">
                                    <h5>No recent answers yet</h5>
                                    <small>Q&amp;A</small>
                                </div>
                                <p class="acd-thread-author">Answers to your questions will appear here.</p>
                                <p class="acd-thread-preview">Ask a question in the Q&amp;A page and come back when someone answers.</p>
                            </article>
                        <?php endif; ?>
                    </div>

                    <div class="acd-my-questions-wrap">
                        <a class="acd-my-questions-btn" href="<?php echo BASE_PATH; ?>index.php?controller=QnA&action=index&mine=1">Go to My Questions</a>
                    </div>
                </section>
            </div>

            <aside class="right acd-right-panel">
                <section class="acd-card acd-alert-card">
                    <div class="acd-section-title">
                        <h4>Priority Academic Alerts</h4>
                    </div>
                    <ul class="acd-alert-list">
                        <?php if (!empty($priorityAcademicAlerts)): ?>
                            <?php foreach ($priorityAcademicAlerts as $alert): ?>
                                <?php
                                    $groupName = trim((string)($alert['group_name'] ?? 'Group'));
                                    $title = trim((string)($alert['title'] ?? 'Assignment'));
                                    $contentText = trim((string)($alert['content'] ?? ''));
                                    if ($contentText !== '' && mb_strlen($contentText) > 56) {
                                        $contentText = mb_substr($contentText, 0, 53) . '...';
                                    }
                                    $deadlineTs = isset($alert['deadline_ts']) ? (int)$alert['deadline_ts'] : 0;
                                    $hasDeadline = $deadlineTs > 0;
                                    $alertClass = '';
                                    if ($hasDeadline) {
                                        $secondsLeft = $deadlineTs - time();
                                        if ($secondsLeft <= 86400) {
                                            $alertClass = ' danger';
                                        } elseif ($secondsLeft <= 259200) {
                                            $alertClass = ' warning';
                                        }
                                    }
                                    $metaLine = $hasDeadline
                                        ? $groupName . ' • Due: ' . date('M j, g:i A', $deadlineTs)
                                        : $groupName . ' • ' . ($contentText !== '' ? $contentText : 'Assignment update posted');
                                    $openUrl = BASE_PATH . 'index.php?controller=Group&action=index&group_id=' . (int)($alert['group_id'] ?? 0) . '#post-' . (int)($alert['post_id'] ?? 0);
                                ?>
                                <li class="acd-alert-item<?php echo $alertClass; ?>">
                                    <div>
                                        <p><?php echo htmlspecialchars($title); ?></p>
                                        <small><?php echo htmlspecialchars($metaLine); ?></small>
                                        <div class="acd-item-footer">
                                            <a class="acd-open-btn" href="<?php echo htmlspecialchars($openUrl); ?>">Open</a>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="acd-alert-item">
                                <div>
                                    <p>No assignment alerts yet</p>
                                    <small>Assignment posts from your groups will appear here.</small>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </section>
            </aside>
        </div>
    </main>

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
    <?php include __DIR__ . '/templates/report-modal.php'; ?>

    <script src="./js/calender.js?v=20250209_syntax"></script>
    <script src="./js/feed.js"></script>
    <script src="./js/friends.js"></script>
    <script src="./js/general.js"></script>
    <script src="./js/vote.js"></script>
    <script src="./js/notificationpopup.js"></script>
    <script src="./js/navbar.js"></script>
    <script src="./js/post.js"></script>
    <script src="./js/comment.js"></script>
    <script src="./js/poll.js"></script>
    <script src="./js/report.js"></script>
    <script src="./js/acedemicdashboard.js"></script>
</body>
</html>
