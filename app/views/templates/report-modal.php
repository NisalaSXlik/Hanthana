<div id="reportModal" class="report-modal" aria-hidden="true">
    <div class="report-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
        <button type="button" class="report-modal__close" data-report-close aria-label="Close report form">&times;</button>
        <header class="report-modal__header">
            <h3 id="reportModalTitle">Report content</h3>
            <p>You are reporting <span id="reportTargetLabel">this content</span>. Tell us what happened.</p>
        </header>
        <form id="reportForm" class="report-form">
            <input type="hidden" name="target_type" id="reportTargetType">
            <input type="hidden" name="target_id" id="reportTargetId">

            <div class="form-group">
                <label for="reportReason">Reason</label>
                <select id="reportReason" name="report_type" required>
                    <option value="spam">Spam or misleading</option>
                    <option value="harassment">Harassment or hate</option>
                    <option value="inappropriate">Inappropriate or unsafe</option>
                    <option value="other">Something else</option>
                </select>
            </div>

            <div class="form-group">
                <label for="reportDescription">Details (optional)</label>
                <textarea id="reportDescription" name="description" placeholder="Add any context that helps us understand the issue" maxlength="1000"></textarea>
                <small>Sharing screenshots, links, or context speeds up the review.</small>
            </div>

            <div class="report-feedback" id="reportFeedback" role="alert" hidden></div>

            <div class="report-modal__actions">
                <button type="button" class="btn btn-secondary" data-report-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitReportBtn">Submit report</button>
            </div>
        </form>
    </div>
</div>
