<?php
$questionCategories = isset($categories) && is_array($categories) ? $categories : [];
?>
<!-- Ask Question Template Modal -->
<div class="modal question-template-modal" id="askQuestionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Craft Your Question</h2>
            <button class="modal-close" id="closeModal"><i class="uil uil-times"></i></button>
        </div>
        <form id="askQuestionForm" class="question-template-form hf-form" enctype="multipart/form-data">
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

            <div class="template-columns">
                <div class="template-field">
                    <label>Question title *</label>
                    <input type="text" name="title" id="questionTitleInput" required placeholder="Summarize your question in one sentence">
                    <small>Example: "How do I debounce API requests in vanilla JS?"</small>
                </div>
                <div class="template-field">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach ($questionCategories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="template-field">
                <label>What problem are you facing? *</label>
                <textarea name="problem_statement" data-maxlength="1000" maxlength="1000" required placeholder="Describe the exact issue, error messages, or blockers."></textarea>
                <div class="char-count" data-for="problem_statement">0 / 1000</div>
            </div>

            <div class="template-columns">
                <div class="template-field">
                    <label>Topics (comma separated)</label>
                    <input type="text" name="topics" placeholder="e.g., php, mysql, async">
                </div>
                <div class="template-field template-tips">
                    <h4>Quick tips</h4>
                    <ul>
                        <li>Share reproducible details.</li>
                        <li>Add tags so the right folks find it.</li>
                    </ul>
                </div>
            </div>

            <div class="template-field">
                <label>Attachment (optional)</label>
                <input type="file" name="attachment_file" accept=".pdf,.doc,.docx,.txt,.jpg,.png,.zip,.xlsx">
                <small>Allowed types: PDF, DOC, DOCX, TXT, JPG, PNG, ZIP, XLSX.</small>
            </div>

            <div class="template-actions">
                <button type="button" class="btn-secondary" id="cancelBtn">Cancel</button>
                <button type="submit" class="btn-primary">Post Question</button>
            </div>
        </form>
    </div>
</div>
