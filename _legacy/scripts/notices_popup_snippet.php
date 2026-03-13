<?php
$noticesPopupApiPath = isset($noticesPopupApiPath) ? (string)$noticesPopupApiPath : '../scripts/notices_api.php';
?>
<style>
.notice-popup-box {
    position: fixed;
    right: 14px;
    bottom: 14px;
    width: 340px;
    max-width: calc(100vw - 24px);
    z-index: 2050;
    background: #fff;
    border: 1px solid #dbe2ef;
    border-radius: 10px;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.15);
    display: none;
}
.notice-popup-head {
    padding: 10px 12px;
    border-bottom: 1px solid #eef2f7;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 700;
}
.notice-popup-body {
    max-height: 320px;
    overflow: auto;
    padding: 8px 10px;
}
.notice-popup-item {
    border: 1px solid #edf1f7;
    border-radius: 8px;
    padding: 8px;
    margin-bottom: 8px;
    background: #fafbfd;
}
.notice-popup-item h6 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 700;
}
.notice-popup-item p {
    margin: 0 0 6px 0;
    font-size: 12px;
}
.notice-popup-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>
<div id="noticePopupBox" class="notice-popup-box" aria-live="polite">
    <div class="notice-popup-head">
        <span>New Notices</span>
        <button type="button" id="noticePopupClose" class="btn btn-sm btn-outline-secondary">Close</button>
    </div>
    <div class="notice-popup-body" id="noticePopupBody"></div>
    <div class="p-2 border-top d-flex justify-content-between">
        <button type="button" id="noticeMarkAllBtn" class="btn btn-sm btn-primary">Mark All Read</button>
        <button type="button" id="noticePopupClose2" class="btn btn-sm btn-outline-secondary">Later</button>
    </div>
</div>
<script>
(function () {
    var api = <?php echo json_encode($noticesPopupApiPath); ?>;
    var box = document.getElementById('noticePopupBox');
    var body = document.getElementById('noticePopupBody');
    var closeBtn = document.getElementById('noticePopupClose');
    var closeBtn2 = document.getElementById('noticePopupClose2');
    var markAllBtn = document.getElementById('noticeMarkAllBtn');
    if (!box || !body) return;

    function esc(v) {
        return String(v || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function hidePopup() {
        box.style.display = 'none';
    }

    function showPopup() {
        box.style.display = 'block';
    }

    function markRead(noticeId) {
        var fd = new FormData();
        fd.append('action', 'mark_read');
        fd.append('notice_id', String(noticeId));
        return fetch(api, { method: 'POST', credentials: 'same-origin', body: fd }).then(function () {});
    }

    function reloadNotices() {
        fetch(api + '?action=list&unread=1&limit=6', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok || !Array.isArray(data.notices) || data.notices.length === 0) {
                    hidePopup();
                    return;
                }
                var html = '';
                data.notices.forEach(function (n) {
                    html += '<div class="notice-popup-item">';
                    html += '<h6>' + esc(n.title) + '</h6>';
                    html += '<p>' + esc(n.body) + '</p>';
                    html += '<div class="notice-popup-actions">';
                    html += '<small class="text-muted">' + esc(n.created_at) + '</small>';
                    html += '<button type="button" class="btn btn-sm btn-outline-success notice-mark-btn" data-id="' + Number(n.id) + '">Mark Read</button>';
                    html += '</div></div>';
                });
                body.innerHTML = html;
                showPopup();

                var btns = body.querySelectorAll('.notice-mark-btn');
                btns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var id = Number(btn.getAttribute('data-id') || '0');
                        if (id > 0) {
                            markRead(id).then(reloadNotices);
                        }
                    });
                });
            })
            .catch(function () {
                hidePopup();
            });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', hidePopup);
    }
    if (closeBtn2) {
        closeBtn2.addEventListener('click', hidePopup);
    }
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function () {
            var fd = new FormData();
            fd.append('action', 'mark_all');
            fetch(api, { method: 'POST', credentials: 'same-origin', body: fd })
                .then(function () { reloadNotices(); });
        });
    }

    reloadNotices();
})();
</script>
