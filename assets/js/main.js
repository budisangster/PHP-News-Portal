// assets/js/main.js

// Wrap everything in an IIFE (Immediately Invoked Function Expression) to avoid global scope pollution
(function() {
    let isSubmitting = false;

    document.addEventListener('DOMContentLoaded', function() {
        const commentList = document.querySelector('.comment-list');
        const mainCommentForm = document.getElementById('main-comment-form');
        
        if (commentList) {
            commentList.addEventListener('click', function(e) {
                if (e.target.classList.contains('reply-btn') || e.target.closest('.reply-btn')) {
                    const replyBtn = e.target.classList.contains('reply-btn') ? e.target : e.target.closest('.reply-btn');
                    const commentId = replyBtn.dataset.commentId;
                    const replyFormContainer = replyBtn.closest('.comment-content').querySelector('.reply-form-container');
                    replyFormContainer.style.display = replyFormContainer.style.display === 'none' ? 'block' : 'none';
                }

                if (e.target.classList.contains('vote-btn') || e.target.closest('.vote-btn')) {
                    const voteBtn = e.target.classList.contains('vote-btn') ? e.target : e.target.closest('.vote-btn');
                    const commentId = voteBtn.dataset.commentId;
                    const voteType = voteBtn.dataset.voteType;
                    
                    voteComment(commentId, voteType, voteBtn);
                }
            });

            commentList.addEventListener('submit', function(e) {
                if (e.target.classList.contains('reply-form')) {
                    e.preventDefault();
                    const form = e.target;
                    const parentId = form.dataset.parentId;
                    const content = form.querySelector('textarea').value;
                    
                    if (typeof articleId !== 'undefined') {
                        submitReply(parentId, content, form);
                    } else {
                        console.error('Article ID is not defined');
                        alert('Unable to submit reply. Article ID is missing.');
                    }
                }
            });
        }

        if (mainCommentForm) {
            mainCommentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const content = this.querySelector('textarea[name="comment"]').value;
                submitComment(content, this);
            });
        }

        // Reply form submission (using event delegation)
        document.body.addEventListener('submit', function(e) {
            if (e.target.classList.contains('reply-form')) {
                e.preventDefault();
                const parentId = e.target.dataset.parentId;
                const content = e.target.querySelector('textarea').value;
                submitReply(parentId, content, e.target);
            }
        });

        const searchToggle = document.querySelector('.search-toggle');
        const searchForm = document.querySelector('.search-form');

        searchToggle.addEventListener('click', function() {
            searchForm.classList.toggle('active');
            if (searchForm.classList.contains('active')) {
                searchForm.querySelector('input').focus();
            }
        });

        // Close search form when clicking outside
        document.addEventListener('click', function(event) {
            if (!searchForm.contains(event.target) && !searchToggle.contains(event.target)) {
                searchForm.classList.remove('active');
            }
        });
    });

    function voteComment(commentId, voteType, button) {
        fetch('vote_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `comment_id=${commentId}&vote_type=${voteType}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const voteCountElement = button.parentElement.querySelector('.vote-count');
                voteCountElement.textContent = data.new_vote_count;
                
                const upvoteBtn = button.parentElement.querySelector('.upvote');
                const downvoteBtn = button.parentElement.querySelector('.downvote');
                
                upvoteBtn.classList.remove('active');
                downvoteBtn.classList.remove('active');
                
                if (data.user_vote === 1) {
                    upvoteBtn.classList.add('active');
                } else if (data.user_vote === -1) {
                    downvoteBtn.classList.add('active');
                }
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function submitComment(content, form) {
        if (isSubmitting) return;
        isSubmitting = true;

        fetch('add_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `content=${encodeURIComponent(content)}&article_id=${articleId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newComment = createCommentElement(data.comment);
                const commentList = document.querySelector('.comment-list');
                commentList.insertAdjacentHTML('afterbegin', newComment);
                form.reset();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        })
        .finally(() => {
            isSubmitting = false;
        });
    }

    function submitReply(parentId, content, form) {
        if (isSubmitting) return;
        isSubmitting = true;

        fetch('add_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `parent_id=${parentId}&content=${encodeURIComponent(content)}&article_id=${articleId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newComment = createCommentElement(data.comment);
                const parentComment = form.closest('.comment-item');
                parentComment.insertAdjacentHTML('afterend', newComment);
                form.reset();
                form.style.display = 'none';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        })
        .finally(() => {
            isSubmitting = false;
        });
    }

    function createCommentElement(comment) {
        return `
            <div class="comment-item" data-comment-id="${comment.id}" style="margin-left: ${comment.parent_id ? '20px' : '0'};">
                <div class="comment-vote">
                    <button class="vote-btn upvote" data-comment-id="${comment.id}" data-vote-type="1">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <span class="vote-count">${comment.upvotes - comment.downvotes}</span>
                    <button class="vote-btn downvote" data-comment-id="${comment.id}" data-vote-type="-1">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                        <img src="${comment.avatar_url || 'assets/images/default-avatar.png'}" 
                             alt="${comment.username}" class="comment-avatar">
                        <span class="comment-author">${comment.username}</span>
                        <span class="comment-date">Just now</span>
                    </div>
                    <div class="comment-body">
                        ${comment.content}
                    </div>
                    <div class="comment-actions">
                        <button class="reply-btn" data-comment-id="${comment.id}">Reply</button>
                    </div>
                    <div class="reply-form-container" style="display: none;">
                        <form class="reply-form" data-parent-id="${comment.id}">
                            <textarea class="form-control" rows="3" required></textarea>
                            <button type="submit" class="btn btn-primary btn-sm mt-2">Submit Reply</button>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }

    // You can add more JavaScript functionality here as needed
})();
