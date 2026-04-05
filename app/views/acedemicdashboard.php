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
                            <button type="button" class="acd-tab active" aria-selected="true">All</button>
                            <button type="button" class="acd-tab" aria-selected="false">Recent Uploads</button>
                            <button type="button" class="acd-tab" aria-selected="false">Top Downloads</button>
                            <button type="button" class="acd-tab" aria-selected="false">My Saves</button>
                        </div>
                    </div>

                    <div class="acd-resource-list">
                        <article class="acd-resource-item">
                            <div class="acd-file-icon"><i class="uil uil-file-alt"></i></div>
                            <div class="acd-resource-main">
                                <h5>Data Structures - Week 07 Notes.pdf</h5>
                                <p>CS2202 • Uploaded by Dr. Perera</p>
                                <div class="acd-meta-row">
                                    <span><i class="uil uil-star"></i> 4.8</span>
                                    <span><i class="uil uil-download-alt"></i> 1.3k downloads</span>
                                </div>
                            </div>
                            <div class="acd-actions">
                                <button type="button" class="btn btn-primary acd-btn">Download</button>
                                <button type="button" class="acd-btn-outline">Save</button>
                            </div>
                        </article>

                        <article class="acd-resource-item">
                            <div class="acd-file-icon"><i class="uil uil-file-download-alt"></i></div>
                            <div class="acd-resource-main">
                                <h5>Thermodynamics Past Paper Set.zip</h5>
                                <p>EN1104 • Uploaded by E-Library</p>
                                <div class="acd-meta-row">
                                    <span><i class="uil uil-star"></i> 4.6</span>
                                    <span><i class="uil uil-download-alt"></i> 980 downloads</span>
                                </div>
                            </div>
                            <div class="acd-actions">
                                <button type="button" class="btn btn-primary acd-btn">Download</button>
                                <button type="button" class="acd-btn-outline">Save</button>
                            </div>
                        </article>

                        <article class="acd-resource-item">
                            <div class="acd-file-icon"><i class="uil uil-file-bookmark-alt"></i></div>
                            <div class="acd-resource-main">
                                <h5>Linear Algebra Formula Sheet.pdf</h5>
                                <p>MA1201 • Uploaded by Academic Support</p>
                                <div class="acd-meta-row">
                                    <span><i class="uil uil-star"></i> 4.9</span>
                                    <span><i class="uil uil-download-alt"></i> 2.1k downloads</span>
                                </div>
                            </div>
                            <div class="acd-actions">
                                <button type="button" class="btn btn-primary acd-btn">Download</button>
                                <button type="button" class="acd-btn-outline">Save</button>
                            </div>
                        </article>
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
                        <li class="acd-alert-item danger">
                            <div>
                                <p>Assignment 03 deadline in 6 hours</p>
                                <small>CS2202 • Due today 11:59 PM</small>
                                <div class="acd-item-footer">
                                    <button type="button" class="acd-open-btn">Open</button>
                                </div>
                            </div>
                        </li>
                        <li class="acd-alert-item warning">
                            <div>
                                <p>Lecturer announcement posted</p>
                                <small>MA1201 • Quiz scope updated</small>
                                <div class="acd-item-footer">
                                    <button type="button" class="acd-open-btn">Open</button>
                                </div>
                            </div>
                        </li>
                        <li class="acd-alert-item">
                            <div>
                                <p>Exam timetable released</p>
                                <small>Faculty notice • Semester final</small>
                                <div class="acd-item-footer">
                                    <button type="button" class="acd-open-btn">Open</button>
                                </div>
                            </div>
                        </li>
                        <li class="acd-alert-item danger-soft">
                            <div>
                                <p>Urgent group message from Project Team</p>
                                <small>SE3101 • Need slides before 5PM</small>
                                <div class="acd-item-footer">
                                    <button type="button" class="acd-open-btn">Open</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <section class="acd-card acd-updates-card">
                    <div class="acd-section-title">
                        <h4>Groups Latest Updates</h4>
                    </div>
                    <div class="acd-updates-list">
                        <article class="acd-update-item"><strong>AI Study Circle</strong><p>Shared quick revision sheet for unit 4.</p><small>2m ago</small><div class="acd-item-footer"><span class="acd-chip">Resource</span><button type="button" class="acd-open-btn">Open</button></div></article>
                        <article class="acd-update-item"><strong>Database Team</strong><p>ER diagram v3 uploaded to resources.</p><small>8m ago</small><div class="acd-item-footer"><span class="acd-chip">Thread</span><button type="button" class="acd-open-btn">Open</button></div></article>
                        <article class="acd-update-item"><strong>Math Tutorial Group</strong><p>Tomorrow session moved to 10:00 AM.</p><small>15m ago</small><div class="acd-item-footer"><span class="acd-chip">Notice</span><button type="button" class="acd-open-btn">Open</button></div></article>
                        <article class="acd-update-item"><strong>Project Phoenix</strong><p>UI feedback notes added in docs.</p><small>22m ago</small><div class="acd-item-footer"><span class="acd-chip">Update</span><button type="button" class="acd-open-btn">Open</button></div></article>
                        <article class="acd-update-item"><strong>Cyber Club</strong><p>CTF prep tasks assigned to members.</p><small>30m ago</small><div class="acd-item-footer"><span class="acd-chip">Task</span><button type="button" class="acd-open-btn">Open</button></div></article>
                        <article class="acd-update-item"><strong>English Speaking Club</strong><p>Topic list posted for next meetup.</p><small>43m ago</small><div class="acd-item-footer"><span class="acd-chip">Topic</span><button type="button" class="acd-open-btn">Open</button></div></article>
                    </div>
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
</body>
</html>
