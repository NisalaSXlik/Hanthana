<div id="editQuestionModal" class="post-modal" role="dialog" aria-modal="true" aria-labelledby="editQuestionTitle">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="editQuestionTitle">Edit Question</h3>
            <button type="button" class="close-modal edit-question-close" aria-label="Close">&times;</button>
        </div>
        <form class="hf-form" id="editQuestionForm" onsubmit="return false;">
            <div class="modal-body">
                <div class="form-group">
                    <label for="editQuestionTitleInput">Title</label>
                    <input type="text" id="editQuestionTitleInput" maxlength="255" placeholder="Update question title...">
                </div>
                <div class="form-group">
                    <label for="editQuestionContentInput">Content</label>
                    <textarea id="editQuestionContentInput" rows="6" maxlength="1000" placeholder="Update your question..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary cancel-question-edit">Cancel</button>
                <button type="button" class="btn btn-primary save-question-edit" disabled>Save</button>
            </div>
        </form>
    </div>
</div>
