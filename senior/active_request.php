<?php
require_once '../config/db.php';
require_once '../includes/auth_guard.php';
require_role('senior');

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$request_id = 0;
$error      = '';

// -----------------------------
// Accept request
// -----------------------------
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['request_id']) &&
    !isset($_POST['resolve']) &&
    !isset($_POST['escalate'])
) {
    $request_id = (int)$_POST['request_id'];

    $stmt_accept = mysqli_prepare(
        $conn,
        "UPDATE help_requests
         SET senior_id = ?, status = 'in_progress'
         WHERE id = ? AND status = 'open'"
    );

    if ($stmt_accept) {
        mysqli_stmt_bind_param($stmt_accept, 'ii', $user_id, $request_id);
        mysqli_stmt_execute($stmt_accept);

        if (mysqli_stmt_affected_rows($stmt_accept) > 0) {
            $action = "Senior #$user_id accepted request #$request_id";
            $log = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
            if ($log) {
                mysqli_stmt_bind_param($log, 'is', $user_id, $action);
                mysqli_stmt_execute($log);
                mysqli_stmt_close($log);
            }
        }
        mysqli_stmt_close($stmt_accept);
    } else {
        $error = 'Unable to accept the request at this time.';
    }
}

// -----------------------------
// Resolve request ID
// -----------------------------
if ($request_id <= 0) {
    $request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if ($request_id <= 0) {
    header('Location: /senior/dashboard.php');
    exit();
}

// -----------------------------
// Load request assigned to senior
// -----------------------------
$stmt_request = mysqli_prepare(
    $conn,
    "SELECT hr.*, j.full_name AS junior_name, j.email AS junior_email
     FROM help_requests hr
     JOIN users j ON hr.junior_id = j.id
     WHERE hr.id = ? AND hr.senior_id = ?"
);

if (!$stmt_request) {
    die('Database error while loading request.');
}

mysqli_stmt_bind_param($stmt_request, 'ii', $request_id, $user_id);
mysqli_stmt_execute($stmt_request);
$result_request = mysqli_stmt_get_result($stmt_request);
$request = mysqli_fetch_assoc($result_request);
mysqli_stmt_close($stmt_request);

if (!$request) {
    header('Location: /senior/dashboard.php');
    exit();
}

// -----------------------------
// Handle escalation
// FIX: removed resolved_at = NOW() — column does not exist in help_requests
// FIX: 'escalated' is not a valid ENUM value — use 'resolved' with note prefix
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['escalate'])) {
    $note = trim($_POST['resolution_note'] ?? '');

    if ($note !== '') {
        // FIX: status ENUM only has: pending, open, in_progress, resolved
        // We mark as resolved but prefix the note with [ESCALATED]
        $stmt_esc = mysqli_prepare(
            $conn,
            "UPDATE help_requests
             SET status = 'resolved'
             WHERE id = ? AND senior_id = ? AND status = 'in_progress'"
        );

        if ($stmt_esc) {
            mysqli_stmt_bind_param($stmt_esc, 'ii', $request_id, $user_id);
            mysqli_stmt_execute($stmt_esc);

            if (mysqli_stmt_affected_rows($stmt_esc) > 0) {
                // FIX: use resolution_text + resolved_by; removed tags column
                $escalate_note = "[ESCALATED] " . $note;
                $stmt_resolution = mysqli_prepare(
                    $conn,
                    "INSERT INTO resolutions (request_id, resolved_by, resolution_text)
                     VALUES (?, ?, ?)"
                );
                if ($stmt_resolution) {
                    mysqli_stmt_bind_param($stmt_resolution, 'iis', $request_id, $user_id, $escalate_note);
                    mysqli_stmt_execute($stmt_resolution);
                    mysqli_stmt_close($stmt_resolution);
                }

                $action = "Request #$request_id escalated by senior #$user_id - Note: $note";
                $log = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
                if ($log) {
                    mysqli_stmt_bind_param($log, 'is', $user_id, $action);
                    mysqli_stmt_execute($log);
                    mysqli_stmt_close($log);
                }

                mysqli_stmt_close($stmt_esc);
                header('Location: /senior/dashboard.php');
                exit();
            }

            mysqli_stmt_close($stmt_esc);
            $error = 'This request could not be escalated. It may no longer be in progress.';
        } else {
            $error = 'Unable to escalate the request right now.';
        }
    } else {
        $error = 'Please describe what was tried before escalating.';
    }
}

// -----------------------------
// Handle resolution
// FIX: removed resolved_at = NOW() — column does not exist
// FIX: use resolution_text + resolved_by instead of resolution_note + tags
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve'])) {
    $note = trim($_POST['resolution_note'] ?? '');

    if ($note !== '') {
        $stmt_resolve = mysqli_prepare(
            $conn,
            "UPDATE help_requests
             SET status = 'resolved'
             WHERE id = ? AND senior_id = ? AND status = 'in_progress'"
        );

        if ($stmt_resolve) {
            mysqli_stmt_bind_param($stmt_resolve, 'ii', $request_id, $user_id);
            mysqli_stmt_execute($stmt_resolve);

            if (mysqli_stmt_affected_rows($stmt_resolve) > 0) {
                // FIX: correct columns — resolution_text, resolved_by (not resolution_note, tags)
                $stmt_resolution = mysqli_prepare(
                    $conn,
                    "INSERT INTO resolutions (request_id, resolved_by, resolution_text)
                     VALUES (?, ?, ?)"
                );

                if ($stmt_resolution) {
                    mysqli_stmt_bind_param($stmt_resolution, 'iis', $request_id, $user_id, $note);
                    mysqli_stmt_execute($stmt_resolution);
                    mysqli_stmt_close($stmt_resolution);
                }

                $action = "Request #$request_id resolved by senior #$user_id";
                $log = mysqli_prepare($conn, "INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
                if ($log) {
                    mysqli_stmt_bind_param($log, 'is', $user_id, $action);
                    mysqli_stmt_execute($log);
                    mysqli_stmt_close($log);
                }

                mysqli_stmt_close($stmt_resolve);
                header('Location: /senior/dashboard.php');
                exit();
            }

            mysqli_stmt_close($stmt_resolve);
            $error = 'This request could not be resolved. It may no longer be in progress.';
        } else {
            $error = 'Unable to resolve the request right now.';
        }
    } else {
        $error = 'Please write a resolution note before closing.';
    }
}

// -----------------------------
// Load videos
// FIX: use created_at not uploaded_at — column is created_at in videos table
// -----------------------------
$stmt_videos = mysqli_prepare(
    $conn,
    "SELECT v.*, u.full_name AS uploader_name
     FROM videos v
     JOIN users u ON v.uploader_id = u.id
     WHERE v.request_id = ?
     ORDER BY v.created_at DESC"
);

if ($stmt_videos) {
    mysqli_stmt_bind_param($stmt_videos, 'i', $request_id);
    mysqli_stmt_execute($stmt_videos);
    $videos = mysqli_stmt_get_result($stmt_videos);
} else {
    $videos = false;
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Active Request #<?= (int)$request_id ?></h1>
    <a href="/senior/dashboard.php" class="btn btn-outline btn-sm">← Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="two-col">
    <div>
        <div class="card">
            <div class="card-header">
                <h2>Request Details</h2>
            </div>
            <div style="padding:1.25rem;">
                <div class="detail-row">
                    <span class="detail-label">From</span>
                    <span><?= htmlspecialchars($request['junior_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span><?= htmlspecialchars($request['junior_email']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="status-badge status-<?= htmlspecialchars($request['status']) ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $request['status']))) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Submitted</span>
                    <span><?= date('M j, Y g:i A', strtotime($request['created_at'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Title</span>
                    <span><?= htmlspecialchars($request['title']) ?></span>
                </div>
                <div style="margin-top:1rem;">
                    <div class="detail-label" style="margin-bottom:0.5rem;">Description</div>
                    <div class="description-box">
                        <?= nl2br(htmlspecialchars($request['description'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>📹 Videos</h2>
            </div>
            <div style="padding:1.25rem;">
                <?php if ($videos && mysqli_num_rows($videos) > 0): ?>
                    <div style="margin-bottom:1rem;">
                        <?php while ($vid = mysqli_fetch_assoc($videos)): ?>
                            <div class="video-item" style="margin-bottom:1rem;">
                                <video controls style="width:100%; border-radius:var(--radius); margin-bottom:0.5rem;">
                                    <source src="/uploads/videos/<?= rawurlencode($vid['filename']) ?>">
                                    Your browser does not support the video tag.
                                </video>
                                <!-- FIX: use created_at not uploaded_at -->
                                <div style="font-size:0.8rem; color:var(--muted);">
                                    Uploaded by <?= htmlspecialchars($vid['uploader_name']) ?>
                                    · <?= date('M j, g:i A', strtotime($vid['created_at'])) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="color:var(--muted); margin-bottom:1rem;">No videos uploaded yet.</p>
                <?php endif; ?>

                <div style="margin-bottom:1rem;">
                    <label style="font-weight:600; font-size:0.85rem; display:block; margin-bottom:0.5rem;">
                        Upload Video
                    </label>
                    <input type="file" id="videoFile" accept="video/*"
                           style="font-size:0.9rem; margin-bottom:0.5rem;">
                    <button type="button" onclick="uploadVideo()" class="btn btn-primary btn-sm">Upload</button>
                    <div id="uploadStatus" style="font-size:0.85rem; margin-top:0.5rem;"></div>
                </div>

                <div>
                    <label style="font-weight:600; font-size:0.85rem; display:block; margin-bottom:0.5rem;">
                        Record Video
                    </label>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem;">
                        <button type="button" onclick="startRecording()" class="btn btn-primary btn-sm" id="recordBtn">
                            🔴 Start Recording
                        </button>
                        <button type="button" onclick="stopRecording()" class="btn btn-outline btn-sm" id="stopBtn" disabled>
                            ⏹ Stop
                        </button>
                    </div>
                    <video id="livePreview" autoplay muted playsinline
                           style="width:100%; border-radius:var(--radius); display:none; background:#000;"></video>
                    <video id="recordedPreview" controls
                           style="width:100%; border-radius:var(--radius); display:none; margin-top:0.5rem;"></video>
                    <button type="button" onclick="uploadRecorded()" id="uploadRecordedBtn"
                            class="btn btn-primary btn-sm" style="display:none; margin-top:0.5rem;">
                        ⬆️ Upload Recording
                    </button>
                    <div id="recordStatus" style="font-size:0.85rem; margin-top:0.5rem;"></div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header">
                <h2>🤖 TekBot AI Assistant</h2>
                <span style="font-size:0.78rem; color:var(--muted);">Powered by Claude</span>
            </div>
            <div id="chatMessages" class="chat-messages"></div>
            <div class="chat-input-area">
                <textarea id="chatInput" placeholder="Ask TekBot anything about this issue..."
                          rows="2" onkeydown="handleChatKey(event)"></textarea>
                <button type="button" onclick="sendChat()" class="btn btn-primary btn-sm" id="sendBtn">Send</button>
            </div>
        </div>

        <?php if ($request['status'] === 'in_progress'): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Close Request</h2>
                </div>
                <div style="padding:1.25rem;">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>
                                Resolution Note
                                <span style="color:var(--danger)">*</span>
                            </label>
                            <textarea name="resolution_note" rows="4"
                                placeholder="Describe what you tried and what resolved the issue..."
                                required></textarea>
                        </div>
                        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                            <button type="submit" name="resolve" value="1" class="btn btn-primary">
                                ✅ Mark Resolved — Save to Knowledge Base
                            </button>
                            <button type="submit" name="escalate" value="1" class="btn btn-escalate">
                                ⚠️ Escalate — Could Not Resolve
                            </button>
                        </div>
                        <p style="font-size:0.8rem; color:var(--muted); margin-top:0.75rem;">
                            ⚠️ Escalated requests are logged for supervisors but marked separately in the knowledge base.
                        </p>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const REQUEST_ID = <?= (int)$request_id ?>;
const CONTEXT = <?= json_encode(($request['title'] ?? '') . ': ' . ($request['description'] ?? '')) ?>;

function handleChatKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChat(); }
}

function appendMessage(role, text) {
    const box = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'chat-msg chat-msg-' + role;
    div.innerHTML = '<div class="chat-bubble">' + String(text).replace(/\n/g, '<br>') + '</div>';
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

async function sendChat() {
    const input = document.getElementById('chatInput');
    const btn   = document.getElementById('sendBtn');
    const msg   = input.value.trim();
    if (!msg) return;

    input.value = '';
    appendMessage('user', msg);
    btn.disabled = true;
    btn.textContent = '...';
    appendMessage('ai', '<em>TekBot is thinking...</em>');

    try {
        const res  = await fetch('/api/ai_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg, context: CONTEXT, request_id: REQUEST_ID })
        });
        let data = {};
        try { data = await res.json(); } catch(e) {}

        const msgs = document.querySelectorAll('.chat-msg-ai');
        if (msgs.length > 0) msgs[msgs.length - 1].remove();

        appendMessage('ai', data.reply || ('⚠️ ' + (data.error || 'Error getting response.')));
    } catch(e) {
        const msgs = document.querySelectorAll('.chat-msg-ai');
        if (msgs.length > 0) msgs[msgs.length - 1].remove();
        appendMessage('ai', '⚠️ Network error. Please try again.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Send';
    }
}

window.addEventListener('load', () => {
    appendMessage('ai', '👋 Hi! I\'m TekBot. I\'m here to help with: <strong>' +
        <?= json_encode(htmlspecialchars($request['title'] ?? '')) ?> +
        '</strong>. Ask me anything about this issue!');
});

async function uploadVideo() {
    const fileInput = document.getElementById('videoFile');
    const file = fileInput.files[0];
    const status = document.getElementById('uploadStatus');
    if (!file) { status.style.color='var(--danger)'; status.textContent='Please select a video file.'; return; }
    status.style.color='inherit'; status.textContent='Uploading...';
    const fd = new FormData();
    fd.append('video', file);
    fd.append('request_id', REQUEST_ID);
    try {
        const res  = await fetch('/api/upload_video.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) { status.style.color='var(--success)'; status.textContent='✅ Uploaded successfully. Refresh to view.'; fileInput.value=''; }
        else { status.style.color='var(--danger)'; status.textContent='❌ ' + (data.error || 'Upload failed.'); }
    } catch(e) { status.style.color='var(--danger)'; status.textContent='❌ Upload failed due to a network error.'; }
}

let mediaRecorder=null, recordedChunks=[], stream=null;

async function startRecording() {
    const livePreview=document.getElementById('livePreview');
    const recordBtn=document.getElementById('recordBtn');
    const stopBtn=document.getElementById('stopBtn');
    const status=document.getElementById('recordStatus');
    try {
        stream = await navigator.mediaDevices.getUserMedia({video:true,audio:true});
        livePreview.srcObject=stream; livePreview.style.display='block';
        recordedChunks=[]; mediaRecorder=new MediaRecorder(stream);
        mediaRecorder.ondataavailable=e=>{ if(e.data&&e.data.size>0) recordedChunks.push(e.data); };
        mediaRecorder.onstop=showRecordedPreview; mediaRecorder.start();
        recordBtn.disabled=true; stopBtn.disabled=false;
        status.style.color='inherit'; status.textContent='🔴 Recording...';
    } catch(e) { status.style.color='var(--danger)'; status.textContent='❌ Camera or microphone access denied.'; }
}

function stopRecording() {
    if (mediaRecorder&&mediaRecorder.state!=='inactive') mediaRecorder.stop();
    if (stream) stream.getTracks().forEach(t=>t.stop());
    document.getElementById('livePreview').style.display='none';
    document.getElementById('livePreview').srcObject=null;
    document.getElementById('recordBtn').disabled=false;
    document.getElementById('stopBtn').disabled=true;
    document.getElementById('recordStatus').textContent='Recording complete.';
}

function showRecordedPreview() {
    if (!recordedChunks.length) return;
    const blob=new Blob(recordedChunks,{type:'video/webm'});
    const url=URL.createObjectURL(blob);
    const preview=document.getElementById('recordedPreview');
    preview.src=url; preview.style.display='block';
    document.getElementById('uploadRecordedBtn').style.display='inline-block';
}

async function uploadRecorded() {
    const btn=document.getElementById('uploadRecordedBtn');
    const status=document.getElementById('recordStatus');
    if (!recordedChunks.length) { status.style.color='var(--danger)'; status.textContent='❌ No recording available.'; return; }
    btn.disabled=true; status.style.color='inherit'; status.textContent='Uploading recording...';
    const blob=new Blob(recordedChunks,{type:'video/webm'});
    const fd=new FormData();
    fd.append('video',blob,'recording_'+Date.now()+'.webm');
    fd.append('request_id',REQUEST_ID);
    try {
        const res=await fetch('/api/upload_video.php',{method:'POST',body:fd});
        const data=await res.json();
        if (data.success) { status.style.color='var(--success)'; status.textContent='✅ Recording uploaded. Refresh to view.'; }
        else { status.style.color='var(--danger)'; status.textContent='❌ '+(data.error||'Upload failed.'); btn.disabled=false; }
    } catch(e) { status.style.color='var(--danger)'; status.textContent='❌ Network error.'; btn.disabled=false; }
}
</script>

<?php
if ($stmt_videos) mysqli_stmt_close($stmt_videos);
require_once '../includes/footer.php';
?>