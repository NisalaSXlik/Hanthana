<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/MediaHelper.php';

// Ensure session for ownership/UI logic
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_PATH . 'index.php?controller=Login&action=index');
    exit();
}

$userModel = new UserModel;
$currentUser = $userModel->findById((int)$_SESSION['user_id']);
$friendRequests = $friendRequests ?? [];

// Posts should arrive from the controller; redirect if accessed directly
if (!isset($posts)) {
	header('Location: ../controllers/FeedController.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Hanthana</title>
	<link rel="stylesheet" href="./css/myfeed.css">
	<link rel="stylesheet" href="./css/groupprofileview.css">
	<link rel="stylesheet" href="./css/general.css">
	<link rel="stylesheet" href="./css/navbar.css">
	<link rel="stylesheet" href="./css/mediaquery.css">
	<link rel="stylesheet" href="./css/calender.css">
	<link rel="stylesheet" href="./css/post.css">
	<link rel="stylesheet" href="./css/notificationpopup.css">
	<link rel="stylesheet" href="./css/report.css">
	<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
</head>
<body>
	<?php include __DIR__ . '/templates/navbar.php'; ?>

	<main>
		<div class="container">
			<?php $activeSidebar = 'feed'; include __DIR__ . '/templates/left-sidebar.php'; ?>

			<div class="middle">
				<div class="feeds">
					<?php if (!empty($posts)): ?>
						<?php foreach ($posts as $post): ?>
							<?php
							// Profile picture is already resolved by PostModel
							$avatarUrl = MediaHelper::resolveMediaPath($post['profile_picture'], 'uploads/user_dp/default.png');
							$fullName = trim(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? ''));
							$displayName = $post['username'] ?? '';

							if ($displayName === '' || $displayName === null) {
								$displayName = $fullName !== '' ? $fullName : 'Unknown';
							}

							$isOwner = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)($post['author_id'] ?? $post['user_id'] ?? 0);
							$postContentForAttr = htmlspecialchars($post['content'] ?? '', ENT_QUOTES);
							$reportLabel = !empty($post['group_id']) && !empty($post['group_name'])
								? 'post in ' . ($post['group_name'] ?? 'group')
								: 'post by ' . $displayName;

							// Calculate Post URL for card click - navigate to profile with post hash
							$authorUserId = (int)($post['author_id'] ?? $post['user_id'] ?? 0);
							$postId = (int)$post['post_id'];
							$isGroupPost = !empty($post['group_id']);

							if ($isGroupPost) {
								// For group posts, go to author's profile with group-post hash
								$postUrl = BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#group-post-' . $postId;
							} else {
								// For personal posts, go to author's profile with personal-post hash
								$postUrl = BASE_PATH . 'index.php?controller=Profile&action=view&user_id=' . $authorUserId . '#personal-post-' . $postId;
							}
							?>
							<div class="feed" data-post-id="<?php echo (int)$post['post_id']; ?>" data-post-content="<?php echo $postContentForAttr; ?>" data-navigate-url="<?php echo htmlspecialchars($postUrl, ENT_QUOTES); ?>" style="cursor: pointer;">
								<div class="head">
									<div class="user">
										<div class="profile-picture">
											<img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Profile">
										</div>
										<div class="info">
											<h3>
												<?php echo htmlspecialchars($displayName); ?>
												<?php if (!empty($post['group_id']) && !empty($post['group_name'])): ?>
													<span class="group-indicator" style="font-weight: normal; color: var(--color-gray); font-size: 0.9em;">
														<i class="uil uil-angle-right"></i>
														<a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$post['group_id']; ?>" class="group-link" style="color: inherit; text-decoration: none; font-weight: 600;" onclick="event.stopPropagation();">
															<?php echo htmlspecialchars($post['group_name']); ?>
														</a>
													</span>
												<?php endif; ?>
											</h3>
											<small>
												<?php if (!empty($post['group_id'])): ?>
													<a href="<?php echo BASE_PATH; ?>index.php?controller=Group&action=index&group_id=<?php echo (int)$post['group_id']; ?>#post-<?php echo (int)$post['post_id']; ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation();">
														<?php echo htmlspecialchars($post['created_at'] ?? ''); ?>
													</a>
												<?php else: ?>
													<a href="<?php echo BASE_PATH; ?>index.php?controller=Profile&action=view&user_id=<?php echo (int)($post['author_id'] ?? $post['user_id']); ?>#post-<?php echo (int)$post['post_id']; ?>" style="color: inherit; text-decoration: none;" onclick="event.stopPropagation();">
														<?php echo htmlspecialchars($post['created_at'] ?? ''); ?>
													</a>
												<?php endif; ?>
											</small>
										</div>
									</div>
									<?php if ($isOwner): ?>
										<div class="post-menu">
											<button class="menu-trigger" aria-label="Post menu"><i class="uil uil-ellipsis-h"></i></button>
											<div class="menu">
												<button class="menu-item edit-post" data-post-id="<?php echo (int)$post['post_id']; ?>">
													<i class="uil uil-edit"></i> Edit
												</button>
												<button class="menu-item delete-post" data-post-id="<?php echo (int)$post['post_id']; ?>">
													<i class="uil uil-trash-alt"></i> Delete
												</button>
											</div>
										</div>
									<?php else: ?>
										<i class="uil uil-ellipsis-h"></i>
									<?php endif; ?>
								</div>

								<?php
								$isGroupPost = !empty($post['group_id']);
								$postType = $isGroupPost ? ($post['group_post_type'] ?? 'discussion') : ($post['post_type'] ?? 'text');
								$postMetadata = $post['metadata'] ?? [];
								?>

								<?php if ($isGroupPost): ?>
									<!-- Group Post Rendering Logic -->
									<div class="group-post-content" style="margin-bottom: 1rem;">
										<?php if ($postType === 'discussion'): ?>
											<?php if (!empty($post['content'])): ?>
												<div class="caption" style="margin-bottom: 1rem;">
													<p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
												</div>
											<?php endif; ?>
											<?php if (!empty($post['image_url'])): ?>
												<?php $postImage = MediaHelper::resolveMediaPath($post['image_url'], ''); ?>
												<div class="photo post-image">
													<img src="<?php echo htmlspecialchars($postImage); ?>" alt="Post image">
												</div>
											<?php endif; ?>

										<?php elseif ($postType === 'question'): ?>
											<div class="question-content">
												<?php if (!empty($postMetadata['category'])): ?>
													<span class="question-category" style="background: var(--color-light); padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.8rem; color: var(--color-primary); margin-bottom: 0.5rem; display: inline-block;"><?php echo htmlspecialchars($postMetadata['category']); ?></span>
												<?php endif; ?>
												<?php if (!empty($post['content'])): ?>
													<p class="post-text" style="font-weight: 500; font-size: 1.1rem; margin-bottom: 0.5rem;"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
												<?php endif; ?>
											</div>
											<?php if (!empty($post['image_url'])): ?>
												<?php $postImage = MediaHelper::resolveMediaPath($post['image_url'], ''); ?>
												<div class="photo post-image">
													<img src="<?php echo htmlspecialchars($postImage); ?>" alt="Post image">
												</div>
											<?php endif; ?>

										<?php elseif ($postType === 'resource'): ?>
											<?php
											$resourceTypeLabel = $postMetadata['resource_type'] ?? ($postMetadata['type'] ?? '');
											$resourceLink = $postMetadata['resource_link'] ?? ($postMetadata['link'] ?? '');
											$resourceDownloadUrl = !empty($postMetadata['file_path']) ? BASE_PATH . ltrim($postMetadata['file_path'], '/') : '';
											?>
											<div class="resource-content" style="background: var(--color-light); padding: 1rem; border-radius: var(--card-border-radius); margin-bottom: 1rem;">
												<h3 class="resource-title" style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($postMetadata['title'] ?? 'Untitled Resource'); ?></h3>
												<?php if (!empty($resourceTypeLabel)): ?>
													<span class="resource-type-label" style="background: var(--color-primary); color: white; padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.8rem; margin-bottom: 0.5rem; display: inline-block;"><?php echo htmlspecialchars($resourceTypeLabel); ?></span>
												<?php endif; ?>
												<?php if (!empty($post['content'])): ?>
													<p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
												<?php endif; ?>
												<div class="resource-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem;">
													<?php if (!empty($resourceDownloadUrl)): ?>
														<a href="<?php echo htmlspecialchars($resourceDownloadUrl); ?>" class="btn btn-primary" download target="_blank" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="event.stopPropagation();">
															<i class="uil uil-download-alt"></i> Download
														</a>
													<?php endif; ?>
													<?php if (!empty($resourceLink)): ?>
														<a href="<?php echo htmlspecialchars($resourceLink); ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="event.stopPropagation();">
															<i class="uil uil-external-link-alt"></i> Open Link
														</a>
													<?php endif; ?>
												</div>
											</div>

										<?php elseif ($postType === 'poll'): ?>
											<!-- Poll Post -->
											<div class="poll-content">
												<?php if (!empty($post['content'])): ?>
													<p class="post-text poll-question"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
												<?php endif; ?>
												<div class="poll-options" data-post-id="<?php echo (int)$post['post_id']; ?>">
													<?php
													$options = $postMetadata['options'] ?? [];
													$votes = $postMetadata['votes'] ?? array_fill(0, count($options), 0);
													$totalVotes = array_sum($votes);
													$userPollVote = isset($post['user_poll_vote']) ? (int)$post['user_poll_vote'] : -1;
													$hasVoted = $userPollVote >= 0;
													foreach ($options as $index => $optionText):
														$voteCount = (int)($votes[$index] ?? 0);
														$percentage = $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100) : 0;
														$isSelected = $hasVoted && $index === $userPollVote;
													?>
														<div class="poll-option <?php echo $isSelected ? 'selected' : ''; ?>" data-option-index="<?php echo $index; ?>">
															<button class="poll-option-btn" type="button" aria-label="Vote for <?php echo htmlspecialchars($optionText); ?>">
																<span class="option-text"><?php echo htmlspecialchars($optionText); ?></span>
																<div class="option-stats">
																	<span class="option-percentage"><?php echo $percentage; ?>%</span>
																	<span class="option-votes"><?php echo $voteCount; ?> vote<?php echo $voteCount === 1 ? '' : 's'; ?></span>
																</div>
																<div class="option-progress" style="width: <?php echo $percentage; ?>%"></div>
															</button>
														</div>
													<?php endforeach; ?>
												</div>
												<div class="poll-footer" data-post-id="<?php echo (int)$post['post_id']; ?>">
													<button type="button" class="poll-total-votes" data-post-id="<?php echo (int)$post['post_id']; ?>">
														<i class="uil uil-users-alt"></i>
														<span><?php echo $totalVotes; ?> total vote<?php echo $totalVotes === 1 ? '' : 's'; ?></span>
													</button>
													<?php if (!empty($postMetadata['duration'])): ?>
														<span class="poll-duration">Ends in <?php echo (int)$postMetadata['duration']; ?> days</span>
													<?php endif; ?>
												</div>
												<div class="poll-voters-panel" id="poll-voters-<?php echo (int)$post['post_id']; ?>" data-post-id="<?php echo (int)$post['post_id']; ?>" hidden>
													<div class="poll-voters-content">
														<div class="poll-voters-placeholder">
															Click total votes to view voter details
														</div>
													</div>
												</div>
											</div>

										<?php elseif ($postType === 'event'): ?>
											<!-- Event Post -->
											<div class="event-content">
												<h3 class="event-title"><?php echo htmlspecialchars($postMetadata['title'] ?? ($post['event_title'] ?? 'Untitled Event')); ?></h3>
												<?php if (!empty($post['content'])): ?>
													<p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
												<?php endif; ?>
												<div class="event-details">
													<?php if (!empty($postMetadata['date']) || !empty($post['event_date'])): ?>
														<div class="event-detail">
															<i class="uil uil-calendar-alt"></i>
															<span><?php echo date('l, F j, Y', strtotime($postMetadata['date'] ?? $post['event_date'])); ?></span>
														</div>
													<?php endif; ?>
													<?php if (!empty($postMetadata['time']) || !empty($post['event_time'])): ?>
														<div class="event-detail">
															<i class="uil uil-clock"></i>
															<span><?php echo htmlspecialchars($postMetadata['time'] ?? $post['event_time']); ?></span>
														</div>
													<?php endif; ?>
													<?php if (!empty($postMetadata['location']) || !empty($post['event_location'])): ?>
														<div class="event-detail">
															<i class="uil uil-map-marker"></i>
															<span><?php echo htmlspecialchars($postMetadata['location'] ?? $post['event_location']); ?></span>
														</div>
													<?php endif; ?>
												</div>
											</div>

										<?php elseif ($postType === 'assignment'): ?>
											<!-- Assignment Post -->
											<div class="assignment-content">
												<h3 class="assignment-title"><?php echo htmlspecialchars($postMetadata['title'] ?? 'Untitled Assignment'); ?></h3>
												<?php if (!empty($post['content'])): ?>
													<p class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
												<?php endif; ?>
												<div class="assignment-details">
													<?php if (!empty($postMetadata['deadline'])): ?>
														<div class="assignment-detail deadline">
															<i class="uil uil-clock"></i>
															<span>Due: <?php echo date('M j, Y g:i A', strtotime($postMetadata['deadline'])); ?></span>
														</div>
													<?php endif; ?>
													<?php if (!empty($postMetadata['points'])): ?>
														<div class="assignment-detail points">
															<i class="uil uil-award"></i>
															<span><?php echo (int)$postMetadata['points']; ?> points</span>
														</div>
													<?php endif; ?>
												</div>
											</div>
										<?php endif; ?>
									</div>
								<?php else: ?>
									<?php if (!empty($post['image_url'])): ?>
										<?php $postImage = MediaHelper::resolveMediaPath($post['image_url'], ''); ?>
										<div class="photo post-image">
											<img src="<?php echo htmlspecialchars($postImage); ?>" alt="Post image" onerror="this.style.display='none'; console.log('Failed to load image: <?php echo htmlspecialchars($post['image_url']); ?>');">
										</div>
									<?php endif; ?>
								<?php endif; ?>

								<div class="action-buttons">
									<div class="interaction-buttons">
										<?php
										$upClass = ($post['user_vote'] ?? '') === 'upvote' ? 'liked' : '';
										$downClass = ($post['user_vote'] ?? '') === 'downvote' ? 'liked' : '';
										?>
										<div class="interaction-item upvote-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
											<i class="uil uil-arrow-up <?php echo $upClass; ?>" data-vote-type="upvote"></i>
											<span class="interaction-count"><?php echo (int)($post['upvote_count'] ?? 0); ?></span>
										</div>
										<div class="interaction-item downvote-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
											<i class="uil uil-arrow-down <?php echo $downClass; ?>" data-vote-type="downvote"></i>
											<span class="interaction-count"><?php echo (int)($post['downvote_count'] ?? 0); ?></span>
										</div>
										<div class="interaction-item load-comments-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
											<i class="uil uil-comment"></i>
											<span class="interaction-count"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
										</div>
									</div>
									<div class="interaction-item bookmark-item">
										<i class="uil uil-bookmark"></i>
									</div>
									<?php if (!$isOwner): ?>
										<div class="interaction-item report-item">
											<button type="button"
												class="report-trigger"
												data-report-type="post"
												data-target-id="<?php echo (int)$post['post_id']; ?>"
												data-target-label="<?php echo htmlspecialchars($reportLabel, ENT_QUOTES); ?>">
												<i class="uil uil-exclamation-circle"></i>
												<span>Report</span>
											</button>
										</div>
									<?php endif; ?>
								</div> <?php if (!$isGroupPost && !empty($post['content'])): ?>
									<div class="caption">
										<p><b><?php echo htmlspecialchars($post['username'] ?? ''); ?></b> <?php echo htmlspecialchars($post['content']); ?></p>
									</div>
								<?php endif; ?>

								<?php if (!empty($post['comment_count'])): ?>
									<div class="comments load-comments-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">
										View all <?php echo (int)$post['comment_count']; ?> comments
									</div>
								<?php else: ?>
									<div class="comments load-comments-btn" data-post-id="<?php echo (int)$post['post_id']; ?>" style="display:none;">
										View all 0 comments
									</div>
								<?php endif; ?>

								<div id="comments-post-<?php echo (int)$post['post_id']; ?>" class="comment-section" data-post-id="<?php echo (int)$post['post_id']; ?>">
									<div class="comment-header">
										<h3>Comments</h3>
										<button class="close-comments" type="button">
											<i class="fas fa-times"></i>
										</button>
									</div>

									<div class="comments-container" id="comments-container-<?php echo (int)$post['post_id']; ?>">
										<div class="comments-loading">Click to load comments</div>
									</div>

									<div class="add-comment-form">
										<div class="comment-input-container">
											<?php
											$currentUserAvatar = MediaHelper::resolveMediaPath($currentUser['profile_picture'] ?? '', 'uploads/user_dp/default.png');
											?>
											<img src="<?php echo htmlspecialchars($currentUserAvatar); ?>" alt="Your Avatar" class="current-user-avatar">
											<div class="comment-input-wrapper">
												<textarea class="comment-input" placeholder="Write a comment..." data-post-id="<?php echo (int)$post['post_id']; ?>"></textarea>
												<button class="comment-submit-btn" data-post-id="<?php echo (int)$post['post_id']; ?>">Post Comment</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<div class="feed">
							<div class="caption">
								<p>No posts yet. Create one to get started.</p>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="right">
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
					$friendRequests = $friendRequests ?? [];
					include __DIR__ . '/templates/friend-requests.php';
				?>

				<div class="toast-container" id="toastContainer"></div>
			</div>
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

	<div id="editPostModal" class="post-modal" role="dialog" aria-modal="true" aria-labelledby="editPostTitle">
		<div class="modal-content">
			<div class="modal-header">
				<h3 id="editPostTitle">Edit Post</h3>
				<button class="close-modal edit-close" aria-label="Close">&times;</button>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="editPostContent">Content</label>
					<textarea id="editPostContent" rows="5" placeholder="Update your post..."></textarea>
				</div>
			</div>
			<div class="modal-footer">
				<button class="btn btn-secondary cancel-edit">Cancel</button>
				<button class="btn btn-primary save-edit" disabled>Save</button>
			</div>
		</div>
	</div>

	<script src="./js/calender.js"></script>
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
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('.feed[data-navigate-url]').forEach(feedCard => {
				const navigateUrl = feedCard.dataset.navigateUrl;
				if (!navigateUrl) return;

				feedCard.addEventListener('click', function(event) {
					if (event.defaultPrevented) return;
					if (
						event.target.closest('.action-buttons') ||
						event.target.closest('.interaction-buttons') ||
						event.target.closest('.comment-section') ||
						event.target.closest('.add-comment-form') ||
						event.target.closest('.load-comments-btn') ||
						event.target.closest('.poll-content') ||
						event.target.closest('.poll-option') ||
						event.target.closest('.poll-total-votes') ||
						event.target.closest('.poll-voters-panel') ||
						event.target.closest('.post-menu') ||
						event.target.closest('.menu') ||
						event.target.closest('.resource-actions') ||
						event.target.closest('button') ||
						event.target.closest('textarea') ||
						event.target.closest('input') ||
						event.target.closest('select') ||
						event.target.closest('.comment-input-wrapper')
					) {
						return;
					}

					if (event.target.closest('a')) {
						return;
					}

					window.location.href = navigateUrl;
				});
			});
		});
	</script>
	
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