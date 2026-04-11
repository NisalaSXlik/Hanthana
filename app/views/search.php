<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
$userModel = new UserModel();
$currentUser = $currentUser ?? $userModel->findById($currentUserId);
$currentUserAvatar = MediaHelper::resolveMediaPath($currentUser['profile_picture'] ?? '', 'uploads/user_dp/default.png');

$searchQuery = trim((string)($query ?? ($_GET['query'] ?? '')));
$activeType = strtolower((string)($_GET['type'] ?? 'all'));
$allowedTypes = ['all', 'people', 'posts', 'groups', 'events', 'qna', 'resources'];
if (!in_array($activeType, $allowedTypes, true)) {
    $activeType = 'all';
}

$peopleResults = is_array($peopleResults ?? null) ? $peopleResults : [];
$postResults = is_array($postResults ?? null) ? $postResults : [];
$groupResults = is_array($groupResults ?? null) ? $groupResults : [];
$eventResults = is_array($eventResults ?? null) ? $eventResults : [];
$questionResults = is_array($questionResults ?? null) ? $questionResults : [];
$resourceResults = is_array($resourceResults ?? null) ? $resourceResults : [];
$streamPosts = is_array($streamPosts ?? null) ? $streamPosts : [];
$peopleSidebarResults = array_slice($peopleResults, 0, 5);
$groupSidebarResults = array_slice($groupResults, 0, 5);

$totalCount = count($peopleResults)
    + count($postResults)
    + count($groupResults)
    + count($eventResults)
    + count($questionResults)
    + count($resourceResults);

$typeCountMap = [
    'people' => count($peopleResults),
    'posts' => count($postResults),
    'groups' => count($groupResults),
    'events' => count($eventResults),
    'qna' => count($questionResults),
    'resources' => count($resourceResults),
];

$buildTypeUrl = static function (string $type) use ($searchQuery): string {
    return BASE_PATH . 'index.php?controller=Search&action=index&query=' . urlencode($searchQuery) . '&type=' . urlencode($type);
};

$middleType = in_array($activeType, ['posts', 'events', 'qna', 'resources'], true) ? $activeType : 'all';

$getPrivacyLabel = static function (string $privacy): string {
    $normalized = strtolower(trim($privacy));
    return $normalized === 'private' ? 'Private' : 'Public';
};

$streamFilteredPosts = $streamPosts;
if ($middleType !== 'all') {
    $streamFilteredPosts = array_values(array_filter($streamPosts, static function (array $post) use ($middleType): bool {
        return (($post['stream_category'] ?? 'posts') === $middleType);
    }));
}

$streamCounts = [
    'posts' => 0,
    'events' => 0,
    'qna' => 0,
    'resources' => 0,
];
foreach ($streamPosts as $post) {
    $cat = (string)($post['stream_category'] ?? 'posts');
    if (isset($streamCounts[$cat])) {
        $streamCounts[$cat]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search | Hanthana</title>
    <link rel="stylesheet" href="./css/myfeed.css">
    <link rel="stylesheet" href="./css/general.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/mediaquery.css">
    <link rel="stylesheet" href="./css/calender.css">
    <link rel="stylesheet" href="./css/post.css">
    <link rel="stylesheet" href="./css/events-page.css">
    <link rel="stylesheet" href="./css/questions.css">
    <link rel="stylesheet" href="./css/notificationpopup.css">
    <link rel="stylesheet" href="./css/notification-center.css">
    <link rel="stylesheet" href="./css/report.css">
    <link rel="stylesheet" href="./css/forms.css">
    <link rel="stylesheet" href="./css/search.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/solid.css">
</head>
<body class="page-search">

<?php include __DIR__ . '/templates/navbar.php'; ?>

<main>
    <div class="container">
        <?php $activeSidebar = 'discover'; include __DIR__ . '/templates/left-sidebar.php'; ?>

        <div class="middle">
            <div class="search-page-wrap">
                <div class="search-header-card">
                    <div>
                        <h2>Search Results</h2>
                        <?php if ($searchQuery !== ''): ?>
                            <p>Showing results for <strong><?php echo htmlspecialchars($searchQuery); ?></strong></p>
                        <?php else: ?>
                            <p>Type in the top search bar and press Enter to search across Hanthana.</p>
                        <?php endif; ?>
                    </div>
                    <div class="search-count-chip"><?php echo (int)$totalCount; ?> results</div>
                </div>

                <div class="search-tabs" role="tablist" aria-label="Search result types">
                    <a class="search-tab <?php echo $middleType === 'all' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($buildTypeUrl('all')); ?>">All</a>
                    <a class="search-tab <?php echo $middleType === 'posts' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($buildTypeUrl('posts')); ?>">Posts (<?php echo (int)$streamCounts['posts']; ?>)</a>
                    <a class="search-tab <?php echo $middleType === 'events' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($buildTypeUrl('events')); ?>">Events (<?php echo (int)$streamCounts['events']; ?>)</a>
                    <a class="search-tab <?php echo $middleType === 'qna' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($buildTypeUrl('qna')); ?>">Q&amp;A (<?php echo (int)$streamCounts['qna']; ?>)</a>
                    <a class="search-tab <?php echo $middleType === 'resources' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($buildTypeUrl('resources')); ?>">Files (<?php echo (int)$streamCounts['resources']; ?>)</a>
                </div>

                <?php if ($searchQuery === ''): ?>
                    <div class="search-empty-state">
                        <i class="uil uil-search"></i>
                        <h3>Start searching</h3>
                        <p>Find people, posts, groups, events, questions, and shared resources.</p>
                    </div>
                <?php elseif ($totalCount === 0): ?>
                    <div class="search-empty-state">
                        <i class="uil uil-file-search-alt"></i>
                        <h3>No results found</h3>
                        <p>Try a different keyword, shorter phrase, or check spelling.</p>
                    </div>
                <?php else: ?>
                    <?php if (empty($streamFilteredPosts)): ?>
                        <div class="search-empty-state">
                            <i class="uil uil-filter"></i>
                            <h3>No matching items in this tab</h3>
                            <p>Try another tab or adjust your search keyword.</p>
                        </div>
                    <?php else: ?>
                        <div class="feeds">
                            <?php foreach ($streamFilteredPosts as $post): ?>
                                <?php
                                    $streamCategory = (string)($post['stream_category'] ?? 'posts');
                                    $postId = (int)($post['post_id'] ?? 0);
                                    $postOwnerId = (int)($post['author_id'] ?? $post['user_id'] ?? 0);
                                    $isOwner = $postOwnerId > 0 && $postOwnerId === $currentUserId;
                                    $reportLabel = $streamCategory === 'events'
                                        ? 'Event post by ' . ($post['stream_author_name'] ?? ($post['username'] ?? 'user'))
                                        : ($streamCategory === 'qna'
                                            ? 'Q&A post by ' . ($post['stream_author_name'] ?? ($post['username'] ?? 'user'))
                                            : 'Post by ' . ($post['stream_author_name'] ?? ($post['username'] ?? 'user')));
                                    $authorName = (string)($post['stream_author_name'] ?? ($post['username'] ?? 'Unknown'));
                                    $authorAvatar = (string)($post['stream_author_avatar'] ?? '');
                                    $createdAt = (string)($post['created_at'] ?? '');
                                    $groupName = (string)($post['group_name'] ?? '');
                                    $content = (string)($post['content'] ?? '');
                                    $postUrl = (string)($post['stream_url'] ?? '#');
                                    $fileNames = (string)($post['stream_file_names'] ?? '');
                                    $imageUrl = !empty($post['stream_image_url']) ? htmlspecialchars((string)$post['stream_image_url']) : '';
                                ?>
                                <article class="feed" data-post-id="<?php echo $postId; ?>">
                                    <?php if ($streamCategory === 'events'): ?>
                                        <?php
                                            $eventDateRaw = (string)($post['event_date'] ?? '');
                                            $eventTimeRaw = trim((string)($post['event_time'] ?? ''));
                                            $eventDate = $eventDateRaw !== '' ? strtotime($eventDateRaw) : false;
                                            $dayLabel = $eventDate ? date('d', $eventDate) : '--';
                                            $monthLabel = $eventDate ? strtoupper(date('M', $eventDate)) : '---';
                                            $timeLabel = $eventTimeRaw !== '' ? date('g:i A', strtotime($eventTimeRaw)) : 'Time TBD';
                                            $locationLabel = (string)($post['event_location'] ?? 'Location TBD');
                                        ?>
                                        <div class="event-card" data-event-id="<?php echo (int)($post['post_id'] ?? 0); ?>">
                                            <div class="post-menu search-post-menu" style="position:absolute; top:0.85rem; right:0.85rem; z-index:3;">
                                                <button type="button" class="menu-trigger" aria-label="Post menu" onclick="window.toggleSearchPostMenu && window.toggleSearchPostMenu(event, this)"><i class="uil uil-ellipsis-h"></i></button>
                                                <div class="menu">
                                                    <button
                                                        type="button"
                                                        class="menu-item report-trigger"
                                                        data-report-type="post"
                                                        data-target-id="<?php echo $postId; ?>"
                                                        data-target-label="<?php echo htmlspecialchars($reportLabel, ENT_QUOTES); ?>"
                                                    >
                                                        <i class="uil uil-exclamation-circle"></i> Report
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="event-card-header">
                                                <div class="event-header-content">
                                                    <div class="event-card-author">
                                                        <img src="<?php echo htmlspecialchars($authorAvatar); ?>" alt="<?php echo htmlspecialchars($authorName); ?>" class="event-author-avatar">
                                                        <div class="event-author-info">
                                                            <h4 class="event-author-name">
                                                                <a href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo $postOwnerId; ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation();">
                                                                    <?php echo htmlspecialchars($authorName); ?>
                                                                </a>
                                                            </h4>
                                                            <p class="event-author-time"><?php echo htmlspecialchars($createdAt); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="event-date-badge">
                                                        <span class="day"><?php echo htmlspecialchars($dayLabel); ?></span>
                                                        <span class="month"><?php echo htmlspecialchars($monthLabel); ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="event-card-body">
                                                <div class="event-card-content">
                                                    <div class="event-card-main">
                                                        <h3 class="event-card-title"><?php echo htmlspecialchars((string)($post['event_title'] ?? 'Event')); ?></h3>

                                                        <?php if ($content !== ''): ?>
                                                            <div class="event-description"><?php echo nl2br(htmlspecialchars($content)); ?></div>
                                                        <?php endif; ?>

                                                        <div class="event-meta-compact">
                                                            <div class="event-detail">
                                                                <i class="uil uil-clock"></i>
                                                                <span><strong>Time:</strong><span class="event-detail-value"><?php echo htmlspecialchars($timeLabel); ?></span></span>
                                                            </div>
                                                            <div class="event-detail">
                                                                <i class="uil uil-location-point"></i>
                                                                <span><strong>Location:</strong><span class="event-detail-value"><?php echo htmlspecialchars($locationLabel); ?></span></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php if ($imageUrl !== ''): ?>
                                                        <div class="event-card-image">
                                                            <img src="<?php echo $imageUrl; ?>" alt="Event image">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="event-card-footer">
                                                    <div class="event-stats">
                                                        <span><i class="uil uil-users-alt"></i> <?php echo (int)($post['going_count'] ?? 0); ?> going</span>
                                                    </div>
                                                    <button
                                                        class="btn-add-calendar <?php echo !empty($post['is_going']) ? 'added' : ''; ?>"
                                                        type="button"
                                                        onclick="window.toggleSearchCalendar && window.toggleSearchCalendar(event, this)"
                                                        data-event-id="<?php echo $postId; ?>"
                                                        data-group-id="<?php echo (int)($post['group_id'] ?? 0); ?>"
                                                        data-event-title="<?php echo htmlspecialchars((string)($post['event_title'] ?? 'Event'), ENT_QUOTES); ?>"
                                                        data-event-date="<?php echo htmlspecialchars((string)($post['event_date'] ?? ''), ENT_QUOTES); ?>"
                                                        data-event-time="<?php echo htmlspecialchars((string)($post['event_time'] ?? ''), ENT_QUOTES); ?>"
                                                        data-event-location="<?php echo htmlspecialchars((string)($post['event_location'] ?? ''), ENT_QUOTES); ?>"
                                                        data-event-description="<?php echo htmlspecialchars((string)($post['content'] ?? ''), ENT_QUOTES); ?>"
                                                    >
                                                        <i class="uil uil-calendar-alt"></i><span>Add Calendar</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php elseif ($streamCategory === 'qna'): ?>
                                            <?php
                                                $metadata = is_array($post['metadata'] ?? null) ? $post['metadata'] : [];
                                                $questionTitle = trim((string)($metadata['title'] ?? ''));
                                                if ($questionTitle === '') {
                                                    $questionTitle = trim($content);
                                                }
                                                if ($questionTitle === '') {
                                                    $questionTitle = 'Question';
                                                }
                                                $questionExcerpt = trim((string)($metadata['problem_statement'] ?? ''));
                                                if ($questionExcerpt === '') {
                                                    $questionExcerpt = trim((string)($metadata['context'] ?? ''));
                                                }
                                                if ($questionExcerpt === '') {
                                                    $questionExcerpt = $content;
                                                }
                                                $answerCount = (int)($post['comment_count'] ?? 0);
                                                $upvoteCount = (int)($post['upvote_count'] ?? 0);
                                                $downvoteCount = (int)($post['downvote_count'] ?? 0);
                                            ?>
                                            <div class="question-card search-qna-card" data-question-id="<?php echo (int)($post['post_id'] ?? 0); ?>">
                                                <div class="question-card-head">
                                                    <div class="question-author">
                                                        <img src="<?php echo htmlspecialchars($authorAvatar); ?>" alt="<?php echo htmlspecialchars($authorName); ?>">
                                                        <div>
                                                            <div style="display:flex; align-items:center; gap:0.35rem; flex-wrap:wrap;">
                                                                <a class="author-name" href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo $postOwnerId; ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation();"><?php echo htmlspecialchars($authorName); ?></a>
                                                                <?php if ($groupName !== ''): ?>
                                                                    <i class="uil uil-angle-right" style="color: var(--color-gray);"></i>
                                                                    <a class="group-link" href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)($post['group_id'] ?? 0); ?>" style="color: var(--color-gray); font-weight: 600; text-decoration: none;" onclick="event.stopPropagation();"><?php echo htmlspecialchars($groupName); ?></a>
                                                                <?php endif; ?>
                                                            </div>
                                                            <small class="question-time"><?php echo htmlspecialchars($createdAt); ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="post-menu" style="margin-left:auto; position:relative;">
                                                        <button type="button" class="menu-trigger" aria-label="Post menu" onclick="window.toggleSearchPostMenu && window.toggleSearchPostMenu(event, this)"><i class="uil uil-ellipsis-h"></i></button>
                                                        <div class="menu">
                                                            <button
                                                                type="button"
                                                                class="menu-item report-trigger"
                                                                data-report-type="post"
                                                                data-target-id="<?php echo $postId; ?>"
                                                                data-target-label="<?php echo htmlspecialchars($reportLabel, ENT_QUOTES); ?>"
                                                            >
                                                                <i class="uil uil-exclamation-circle"></i> Report
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <h2 class="question-title">
                                                    <span><?php echo htmlspecialchars($questionTitle); ?></span>
                                                </h2>

                                                <?php if ($questionExcerpt !== ''): ?>
                                                    <p class="question-excerpt"><?php echo htmlspecialchars(mb_strimwidth($questionExcerpt, 0, 220, '...')); ?></p>
                                                <?php endif; ?>

                                                <div class="question-card-footer">
                                                    <div class="question-card-actions">
                                                        <div class="interaction-item">
                                                            <span class="vote-btn inline upvote" role="img" aria-label="Upvotes">
                                                                <i class="uil uil-arrow-up <?php echo ((string)($post['user_vote'] ?? '') === 'upvote') ? 'liked' : ''; ?>" data-vote-type="upvote"></i>
                                                            </span>
                                                            <span class="interaction-count"><?php echo $upvoteCount; ?></span>
                                                        </div>
                                                        <div class="interaction-item">
                                                            <span class="vote-btn inline downvote" role="img" aria-label="Downvotes">
                                                                <i class="uil uil-arrow-down <?php echo ((string)($post['user_vote'] ?? '') === 'downvote') ? 'liked' : ''; ?>" data-vote-type="downvote"></i>
                                                            </span>
                                                            <span class="interaction-count"><?php echo $downvoteCount; ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="question-card-stats">
                                                        <button type="button" class="question-answer-link question-answer-link-btn load-comments-btn" data-post-id="<?php echo (int)($post['post_id'] ?? 0); ?>">
                                                            <i class="uil uil-comment"></i> <?php echo $answerCount; ?> answers
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="comments-post-<?php echo (int)($post['post_id'] ?? 0); ?>" class="comment-section" data-post-id="<?php echo (int)($post['post_id'] ?? 0); ?>" data-thread-label="answers">
                                                <div class="comment-header">
                                                    <h3>Answers</h3>
                                                    <button class="close-comments" type="button">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>

                                                <div class="comments-container" id="comments-container-<?php echo (int)($post['post_id'] ?? 0); ?>">
                                                    <div class="comments-loading">Click to load answers</div>
                                                </div>

                                                <form class="add-comment-form hf-form hf-inline" onsubmit="return false;">
                                                    <div class="comment-input-container">
                                                        <img src="<?php echo htmlspecialchars($currentUserAvatar); ?>" alt="Your Avatar" class="current-user-avatar">
                                                        <div class="comment-input-wrapper">
                                                            <textarea class="comment-input" placeholder="Write an answer..." data-post-id="<?php echo (int)($post['post_id'] ?? 0); ?>"></textarea>
                                                            <button type="button" class="comment-submit-btn" data-post-id="<?php echo (int)($post['post_id'] ?? 0); ?>">Post Answer</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <?php
                                                $isResourceStream = ($streamCategory === 'resources');
                                                $isGroupPostUi = !empty($post['group_id']) || $isResourceStream;
                                                $postMetadataUiRaw = $post['metadata'] ?? [];
                                                if (is_string($postMetadataUiRaw)) {
                                                    $decodedMetadata = json_decode($postMetadataUiRaw, true);
                                                    $postMetadataUi = is_array($decodedMetadata) ? $decodedMetadata : [];
                                                } elseif (is_array($postMetadataUiRaw)) {
                                                    $postMetadataUi = $postMetadataUiRaw;
                                                } else {
                                                    $postMetadataUi = [];
                                                }
                                                if ($isResourceStream) {
                                                    $postTypeUi = 'resource';
                                                } else {
                                                    $postTypeUi = $isGroupPostUi ? ((string)($post['group_post_type'] ?? 'discussion')) : ((string)($post['post_type'] ?? 'text'));
                                                }
                                                $upClass = ((string)($post['user_vote'] ?? '') === 'upvote') ? 'liked' : '';
                                                $downClass = ((string)($post['user_vote'] ?? '') === 'downvote') ? 'liked' : '';
                                            ?>
                                            <div class="head">
                                                <div class="user">
                                                    <div class="profile-picture">
                                                        <img src="<?php echo htmlspecialchars($authorAvatar); ?>" alt="<?php echo htmlspecialchars($authorName); ?>">
                                                    </div>
                                                    <div class="info">
                                                        <h3>
                                                            <a href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo $postOwnerId; ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation();">
                                                                <?php echo htmlspecialchars($authorName); ?>
                                                            </a>
                                                            <?php if ($groupName !== ''): ?>
                                                                <span class="group-indicator" style="font-weight: normal; color: var(--color-gray); font-size: 0.9em;">
                                                                    <i class="uil uil-angle-right"></i>
                                                                    <a class="group-link" href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)($post['group_id'] ?? 0); ?>" style="color: inherit; font-weight: 600; text-decoration: none;" onclick="event.stopPropagation();"><?php echo htmlspecialchars($groupName); ?></a>
                                                                </span>
                                                            <?php endif; ?>
                                                        </h3>
                                                        <small><?php echo htmlspecialchars($createdAt); ?></small>
                                                    </div>
                                                </div>
                                                <div class="post-menu" style="position:relative;">
                                                    <button type="button" class="menu-trigger" aria-label="Post menu" onclick="window.toggleSearchPostMenu && window.toggleSearchPostMenu(event, this)"><i class="uil uil-ellipsis-h"></i></button>
                                                    <div class="menu">
                                                        <button
                                                            type="button"
                                                            class="menu-item report-trigger"
                                                            data-report-type="post"
                                                            data-target-id="<?php echo $postId; ?>"
                                                            data-target-label="<?php echo htmlspecialchars($reportLabel, ENT_QUOTES); ?>"
                                                        >
                                                            <i class="uil uil-exclamation-circle"></i> Report
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if ($isGroupPostUi): ?>
                                                <div class="group-post-content" style="margin-bottom: 0.35rem;">
                                                    <?php if ($postTypeUi === 'discussion'): ?>
                                                        <?php if ($content !== ''): ?>
                                                            <div class="caption" style="margin-bottom: 1rem;">
                                                                <p class="post-text"><?php echo nl2br(htmlspecialchars($content)); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($imageUrl !== ''): ?>
                                                            <div class="photo post-image">
                                                                <img src="<?php echo $imageUrl; ?>" alt="Post image">
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php elseif ($postTypeUi === 'resource'): ?>
                                                        <?php
                                                            $resourceTitle = (string)($postMetadataUi['title'] ?? 'Untitled Resource');
                                                            $resourceTypeLabel = (string)($postMetadataUi['resource_type'] ?? ($postMetadataUi['type'] ?? ''));
                                                            $resourceLink = (string)($postMetadataUi['resource_link'] ?? ($postMetadataUi['link'] ?? ''));
                                                            $resourceDownloadUrl = !empty($postMetadataUi['file_path'])
                                                                ? BASE_PATH . ltrim((string)$postMetadataUi['file_path'], '/')
                                                                : '';
                                                        ?>
                                                        <div class="resource-content" style="background: var(--color-light); padding: 1rem; border-radius: var(--card-border-radius); margin-bottom: 1rem;">
                                                            <h3 class="resource-title" style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($resourceTitle); ?></h3>
                                                            <?php if ($resourceTypeLabel !== ''): ?>
                                                                <span class="resource-type-label" style="background: var(--color-primary); color: white; padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.8rem; margin-bottom: 0.5rem; display: inline-block;"><?php echo htmlspecialchars($resourceTypeLabel); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($content !== ''): ?>
                                                                <p class="post-text"><?php echo nl2br(htmlspecialchars($content)); ?></p>
                                                            <?php endif; ?>
                                                            <?php if ($fileNames !== ''): ?>
                                                                <p class="search-stream-files"><i class="uil uil-paperclip"></i> <?php echo htmlspecialchars($fileNames); ?></p>
                                                            <?php endif; ?>
                                                            <div class="resource-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                                <?php if ($resourceDownloadUrl !== ''): ?>
                                                                    <a href="<?php echo htmlspecialchars($resourceDownloadUrl); ?>" class="btn btn-primary" download target="_blank" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                                                        <i class="uil uil-download-alt"></i> Download
                                                                    </a>
                                                                <?php endif; ?>
                                                                <?php if ($resourceLink !== ''): ?>
                                                                    <a href="<?php echo htmlspecialchars($resourceLink); ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                                                        <i class="uil uil-external-link-alt"></i> Open Link
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <?php if ($content !== ''): ?>
                                                            <div class="caption" style="margin-bottom: 1rem;">
                                                                <p class="post-text"><?php echo nl2br(htmlspecialchars($content)); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($fileNames !== ''): ?>
                                                            <p class="search-stream-files"><i class="uil uil-paperclip"></i> <?php echo htmlspecialchars($fileNames); ?></p>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <?php if ($content !== ''): ?>
                                                    <div class="caption compact-caption">
                                                        <p class="post-text"><?php echo nl2br(htmlspecialchars($content)); ?></p>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($imageUrl !== ''): ?>
                                                    <div class="photo post-image">
                                                        <img src="<?php echo $imageUrl; ?>" alt="Post image">
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($fileNames !== ''): ?>
                                                    <p class="search-stream-files"><i class="uil uil-paperclip"></i> <?php echo htmlspecialchars($fileNames); ?></p>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <div class="action-buttons">
                                                <div class="interaction-buttons">
                                                    <div class="interaction-item upvote-btn">
                                                        <i class="uil uil-arrow-up <?php echo $upClass; ?>" data-vote-type="upvote"></i>
                                                        <span class="interaction-count"><?php echo (int)($post['upvote_count'] ?? 0); ?></span>
                                                    </div>
                                                    <div class="interaction-item downvote-btn">
                                                        <i class="uil uil-arrow-down <?php echo $downClass; ?>" data-vote-type="downvote"></i>
                                                        <span class="interaction-count"><?php echo (int)($post['downvote_count'] ?? 0); ?></span>
                                                    </div>
                                                </div>
                                                <div class="comments-side">
                                                    <button type="button" class="question-answer-link-btn load-comments-btn" data-post-id="<?php echo (int)($post['post_id'] ?? 0); ?>">
                                                        <i class="uil uil-comment"></i>
                                                        <?php echo (int)($post['comment_count'] ?? 0); ?> comments
                                                    </button>
                                                </div>
                                                <div class="interaction-item bookmark-item" aria-hidden="true">
                                                    <button type="button" class="bookmark-btn <?php echo !empty($post['is_bookmarked']) ? 'bookmarked' : ''; ?>" data-post-id="<?php echo $postId; ?>" aria-label="Save post" aria-pressed="<?php echo !empty($post['is_bookmarked']) ? 'true' : 'false'; ?>">
                                                        <i class="<?php echo !empty($post['is_bookmarked']) ? 'uis uis-bookmark bookmarked' : 'uil uil-bookmark'; ?>" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div id="comments-post-<?php echo (int)($post['post_id'] ?? 0); ?>" class="comment-section" data-post-id="<?php echo (int)($post['post_id'] ?? 0); ?>">
                                                <div class="comment-header">
                                                    <h3>Comments</h3>
                                                    <button class="close-comments" type="button">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>

                                                <div class="comments-container" id="comments-container-<?php echo (int)($post['post_id'] ?? 0); ?>">
                                                    <div class="comments-loading">Click to load comments</div>
                                                </div>

                                                <form class="add-comment-form hf-form hf-inline" onsubmit="return false;">
                                                    <div class="comment-input-container">
                                                        <img src="<?php echo htmlspecialchars($currentUserAvatar); ?>" alt="Your Avatar" class="current-user-avatar">
                                                        <div class="comment-input-wrapper">
                                                            <textarea class="comment-input" placeholder="Write a comment..." data-post-id="<?php echo (int)($post['post_id'] ?? 0); ?>"></textarea>
                                                            <button type="button" class="comment-submit-btn" data-post-id="<?php echo (int)($post['post_id'] ?? 0); ?>">Post Comment</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($imageUrl !== '' && $streamCategory !== 'events' && $streamCategory !== 'posts'): ?>
                                            <div class="photo post-image">
                                                <img src="<?php echo $imageUrl; ?>" alt="Post media">
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($streamCategory !== 'events' && $streamCategory !== 'qna' && $streamCategory !== 'posts' && $streamCategory !== 'resources'): ?>
                                            <div class="post-meta-row search-stream-meta">
                                                <span><i class="uil uil-heart"></i> <?php echo (int)($post['upvote_count'] ?? 0); ?></span>
                                                <span><i class="uil uil-comment"></i> <?php echo (int)($post['comment_count'] ?? 0); ?></span>
                                            </div>
                                        <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>

        <div class="right search-right-column">
            <?php if ($searchQuery !== ''): ?>
                <div class="search-side-card search-side-results">
                    <div class="search-side-header">
                        <h4>People</h4>
                        <span><?php echo (int)count($peopleResults); ?></span>
                    </div>
                    <?php if (!empty($peopleSidebarResults)): ?>
                        <div class="search-side-list">
                            <?php foreach ($peopleSidebarResults as $person): ?>
                                <article class="search-side-item">
                                    <a class="search-side-main" href="<?php echo htmlspecialchars($person['profile_url']); ?>">
                                        <img src="<?php echo htmlspecialchars($person['avatar']); ?>" alt="<?php echo htmlspecialchars($person['name']); ?>">
                                        <div>
                                            <h5><?php echo htmlspecialchars($person['name']); ?></h5>
                                            <p>@<?php echo htmlspecialchars($person['username']); ?></p>
                                            <small><?php echo (int)$person['mutual_friends']; ?> mutual</small>
                                        </div>
                                    </a>
                                    <?php if (($person['friend_state'] ?? 'none') === 'self'): ?>
                                        <button class="btn btn-secondary" type="button" disabled>You</button>
                                    <?php else: ?>
                                        <button
                                            class="btn add-friend-btn"
                                            data-user-id="<?php echo (int)$person['user_id']; ?>"
                                            data-state="<?php echo htmlspecialchars((string)($person['friend_state'] ?? 'none')); ?>"
                                            data-label-friends="Friends"
                                            type="button"
                                        >
                                            <i class="uil uil-user-plus"></i>
                                            <span>Add</span>
                                        </button>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="search-side-empty">No matching people.</p>
                    <?php endif; ?>
                </div>

                <div class="search-side-card search-side-results">
                    <div class="search-side-header">
                        <h4>Groups</h4>
                        <span><?php echo (int)count($groupResults); ?></span>
                    </div>
                    <?php if (!empty($groupSidebarResults)): ?>
                        <div class="search-side-list">
                            <?php foreach ($groupSidebarResults as $group): ?>
                                <article class="search-side-item">
                                    <a class="search-side-main" href="<?php echo htmlspecialchars($group['group_url']); ?>">
                                        <img src="<?php echo htmlspecialchars($group['avatar']); ?>" alt="<?php echo htmlspecialchars($group['name']); ?>">
                                        <div>
                                            <h5><?php echo htmlspecialchars($group['name']); ?></h5>
                                            <p><?php echo (int)$group['member_count']; ?> members</p>
                                            <small><?php echo htmlspecialchars($getPrivacyLabel((string)$group['privacy'])); ?></small>
                                        </div>
                                    </a>
                                    <?php if (!empty($group['is_member'])): ?>
                                        <button class="btn btn-secondary" type="button" disabled>Joined</button>
                                    <?php else: ?>
                                        <?php $groupPrivacy = strtolower((string)($group['privacy'] ?? 'public')); ?>
                                        <button
                                            class="btn btn-primary search-join-btn"
                                            type="button"
                                            data-group-join="true"
                                            data-group-id="<?php echo (int)$group['group_id']; ?>"
                                            data-group-privacy="<?php echo htmlspecialchars($groupPrivacy); ?>"
                                        >
                                            <?php echo $groupPrivacy === 'public' ? 'Join' : 'Request'; ?>
                                        </button>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="search-side-empty">No matching groups.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/templates/report-modal.php'; ?>

<script src="./js/navbar.js"></script>
<script src="./js/calender.js"></script>
<script src="./js/notificationpopup.js"></script>
<script src="./js/general.js"></script>
<script src="./js/friends.js"></script>
<script src="./js/vote.js"></script>
<script src="./js/bookmark.js"></script>
<script src="./js/report.js"></script>
<script src="./js/search-page.js"></script>
<script src="./js/comment.js"></script>
</body>
</html>
