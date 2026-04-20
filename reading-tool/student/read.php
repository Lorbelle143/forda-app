<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();
if (isAdmin()) { header('Location: ../admin/dashboard.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../dashboard.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM reading_materials WHERE id = ?');
$stmt->execute([$id]);
$material = $stmt->fetch();
if (!$material) { header('Location: ../dashboard.php'); exit; }

// Check if student already submitted a recording for this material
$stmt = $pdo->prepare('SELECT id, feedback FROM recordings WHERE student_id = ? AND material_id = ? ORDER BY submitted_at DESC LIMIT 1');
$stmt->execute([currentUserId(), $id]);
$existingRec = $stmt->fetch();

// Level display mapping
$levelMap = [
    'beginner'     => ['week' => 'Week 1', 'label' => 'Easy Level',     'session' => 'Sessions 1–2'],
    'intermediate' => ['week' => 'Week 2', 'label' => 'Medium Level',   'session' => 'Sessions 3–4'],
    'advanced'     => ['week' => 'Week 3', 'label' => 'Hard & Advanced', 'session' => 'Sessions 5–6'],
];
$lvlInfo = $levelMap[$material['level']] ?? ['week' => '', 'label' => ucfirst($material['level']), 'session' => ''];

$sessionNames = [
    1 => 'The Baseline', 2 => 'The Bridge',
    3 => 'The Shift',    4 => 'The Flow',
    5 => 'The Challenge',6 => 'The Final Evaluation',
];
$sn = (int)($material['session_number'] ?? 0);
$rn = (int)($material['reading_number'] ?? 0);
$sessionName = $sessionNames[$sn] ?? '';

// Get all readings in this session for prev/next navigation
$stmt = $pdo->prepare('SELECT id, title, reading_number FROM reading_materials WHERE session_number = ? ORDER BY reading_number ASC');
$stmt->execute([$sn]);
$sessionReadings = $stmt->fetchAll();
$currentIdx = array_search($id, array_column($sessionReadings, 'id'));
$prevReading = ($currentIdx > 0) ? $sessionReadings[$currentIdx - 1] : null;
$nextReading = ($currentIdx !== false && $currentIdx < count($sessionReadings) - 1) ? $sessionReadings[$currentIdx + 1] : null;

$pageTitle = $material['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <div class="breadcrumb">
      <a href="../dashboard.php">Home</a>
      <span class="breadcrumb-sep">&#8250;</span>
      <?php if ($sn): ?>
        <a href="../dashboard.php#session-<?= $sn ?>">Session <?= $sn ?><?= $sessionName ? ' — ' . e($sessionName) : '' ?></a>
        <span class="breadcrumb-sep">&#8250;</span>
      <?php endif; ?>
      <span>Reading <?= $rn ?: '' ?></span>
    </div>
    <div class="page-header-inner">
      <div>
        <h1 class="page-title"><?= e($material['title']) ?></h1>
        <p class="page-subtitle" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
          <?php if ($sn): ?>
            <span class="badge badge-<?= e($material['level']) ?>">Session <?= $sn ?> · Reading <?= $rn ?></span>
          <?php endif; ?>
          <span class="badge badge-<?= e($material['level']) ?>"><?= e($lvlInfo['week']) ?> — <?= e($lvlInfo['label']) ?></span>
          <span class="text-muted text-sm"><?= e($lvlInfo['session']) ?></span>
        </p>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
        <?php if ($prevReading): ?>
          <a href="read.php?id=<?= $prevReading['id'] ?>" class="btn btn-ghost btn-sm">← Reading <?= $prevReading['reading_number'] ?></a>
        <?php endif; ?>
        <?php if ($nextReading): ?>
          <a href="read.php?id=<?= $nextReading['id'] ?>" class="btn btn-primary btn-sm">Reading <?= $nextReading['reading_number'] ?> →</a>
        <?php endif; ?>
        <a href="../dashboard.php" class="btn btn-ghost btn-sm">&#8592; Back</a>
      </div>
    </div>
  </div>

  <!-- AIM Session Steps Progress -->
  <div class="aim-steps" style="margin-bottom:1.5rem;">
    <div class="aim-step active" id="step1">
      <div class="aim-step-num">1</div>
      <div class="aim-step-title">👁️ Listen &amp; Observe</div>
      <div class="aim-step-desc">Listen to your facilitator's model recording and read along.</div>
    </div>
    <div class="aim-step" id="step2">
      <div class="aim-step-num">2</div>
      <div class="aim-step-title">🎙️ Record Your Speech</div>
      <div class="aim-step-desc">Record yourself reading the text aloud.</div>
    </div>
    <div class="aim-step" id="step3">
      <div class="aim-step-num">3</div>
      <div class="aim-step-title">🔄 Compare &amp; Reflect</div>
      <div class="aim-step-desc">Play back your recording and compare with the model.</div>
    </div>
    <div class="aim-step" id="step4">
      <div class="aim-step-num">4</div>
      <div class="aim-step-title">📤 Submit &amp; Get Feedback</div>
      <div class="aim-step-desc">Submit your best recording for facilitator feedback.</div>
    </div>
  </div>

  <div class="reading-container">

    <div id="ttsNotice" class="alert alert-warning" style="display:none;">
      Text-to-Speech is not supported in your browser. Try Chrome or Edge.
    </div>

    <?php if ($existingRec): ?>
    <div class="alert alert-<?= $existingRec['feedback'] ? 'success' : 'warning' ?>">
      <?php if ($existingRec['feedback']): ?>
        &#10003; You already submitted a recording for this session and received feedback.
        <a href="../student/my-recordings.php" style="font-weight:700;">View feedback</a>
      <?php else: ?>
        &#9203; You already submitted a recording for this session. Awaiting facilitator feedback.
        <a href="../student/my-recordings.php" style="font-weight:700;">View my recordings</a>
      <?php endif; ?>
      You can still record again below.
      <button class="alert-close" onclick="this.parentElement.remove()">&#215;</button>
    </div>
    <?php endif; ?>

    <!-- STEP 1: Listen & Observe -->
    <div class="card section" id="sectionStep1">
      <div class="card-header">
        <span class="card-title">&#128214; Step 1 — Listen &amp; Observe</span>
        <span class="text-muted text-sm"><?= str_word_count($material['content']) ?> words</span>
      </div>
      <div class="card-body">
        <p class="text-muted text-sm mb-4" style="margin-bottom:.75rem;">
          Read the text carefully. Listen to your facilitator's model recording to hear the correct pronunciation. Observe how each word sounds.
        </p>

        <!-- Reading Text -->
        <div
          id="readingText"
          class="reading-text-box"
          data-raw-text="<?= htmlspecialchars($material['content'], ENT_QUOTES, 'UTF-8') ?>"
        ><?= e($material['content']) ?></div>

        <!-- Facilitator Model Audio (primary) -->
        <div style="margin-top:1.25rem;">
          <?php if (!empty($material['model_audio_path']) && file_exists(__DIR__ . '/../' . $material['model_audio_path'])): ?>
            <div style="background:linear-gradient(135deg,#FDF4FF,#F5E8F9);border:1.5px solid #E9B8D4;border-radius:12px;padding:1.25rem;">
              <p style="font-size:.82rem;font-weight:700;color:#7B1450;margin-bottom:.6rem;display:flex;align-items:center;gap:.4rem;">
                🎙️ Facilitator Model Recording
                <span style="font-size:.72rem;font-weight:400;color:#A855A0;">— listen carefully and observe the pronunciation</span>
              </p>
              <audio id="modelAudio" controls style="width:100%;border-radius:8px;">
                <source src="<?= e('../' . $material['model_audio_path']) ?>">
                Your browser does not support audio playback.
              </audio>
              <p style="font-size:.75rem;color:#8B5070;margin-top:.5rem;">
                💡 Tip: Listen multiple times. Pay attention to stress, intonation, and how each sound is produced.
              </p>
            </div>
          <?php else: ?>
            <div style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:12px;padding:1rem;">
              <p style="font-size:.85rem;color:#92400E;font-weight:600;margin-bottom:.25rem;">⏳ No model audio uploaded yet</p>
              <p style="font-size:.8rem;color:#B45309;">Your facilitator has not uploaded a pronunciation model for this reading. You may use the TTS helper below as a reference in the meantime.</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- TTS as optional helper -->
        <details style="margin-top:1rem;">
          <summary style="cursor:pointer;font-size:.82rem;font-weight:600;color:#A855A0;padding:.5rem 0;list-style:none;display:flex;align-items:center;gap:.4rem;">
            🔊 Optional: Text-to-Speech helper
            <span style="font-size:.72rem;font-weight:400;color:#8B5070;">(not a substitute for the facilitator model)</span>
          </summary>
          <div id="ttsNotice" class="alert alert-warning" style="display:none;margin-top:.5rem;">
            Text-to-Speech is not supported in your browser. Try Chrome or Edge.
          </div>
          <div class="tts-controls" style="margin-top:.75rem;">
            <div class="control-group">
              <button id="ttsPlay" class="btn btn-ghost btn-sm">▶ Play TTS</button>
              <button id="ttsPause" class="btn btn-ghost btn-sm" disabled>⏸ Pause</button>
              <button id="ttsStop" class="btn btn-ghost btn-sm" disabled>⏹ Stop</button>
            </div>
            <div class="control-group">
              <span class="speed-label">Speed:</span>
              <select id="ttsSpeed" class="speed-select">
                <option value="0.6">Slow</option>
                <option value="1" selected>Normal</option>
                <option value="1.5">Fast</option>
              </select>
            </div>
          </div>
        </details>
      </div>
    </div>

    <!-- STEP 2: Record Your Speech -->
    <div class="card section" id="sectionStep2">
      <div class="card-header">
        <span class="card-title">&#127908; Step 2 — Record Your Speech</span>
      </div>
      <div class="card-body">
        <p class="text-muted text-sm mb-4" style="margin-bottom:.75rem;">
          Read the text above aloud and record yourself. Try to match the pronunciation you heard in Step 1.
        </p>

        <div class="recording-controls">
          <div class="recording-indicator" id="recIndicator"></div>
          <span class="recording-timer" id="recTimer">00:00</span>
          <button id="recStart" class="btn btn-danger">&#127908; Start Recording</button>
          <button id="recStop" class="btn btn-ghost" disabled>&#9632; Stop</button>
        </div>

        <p id="recStatus" class="text-muted text-sm mt-3"></p>

        <div id="audioPreview" class="audio-preview" style="display:none;">
          <p class="audio-preview-label">&#128266; Your Recording (Step 2 — First Attempt):</p>
          <audio id="myRecordingAudio" controls></audio>
        </div>
      </div>
    </div>

    <!-- ACCURACY TRACKER -->
    <div class="card section" id="sectionTracker" style="display:none;">
      <div class="card-header">
        <span class="card-title">🌸 My Accuracy Tracker</span>
        <span class="text-muted text-sm">Tally your mispronounced words</span>
      </div>
      <div class="card-body">
        <p style="font-size:.875rem;color:#64748B;margin-bottom:1.25rem;">
          Check your tally of <strong style="color:#7B1450;">mispronounced words</strong> below. This baseline shows us where you are today so we can celebrate your growth tomorrow!
        </p>

        <!-- Tally grid -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem .75rem;max-width:320px;margin-bottom:1.5rem;" id="tallyGrid">
          <?php for ($i = 1; $i <= 10; $i++): ?>
          <div style="display:flex;align-items:center;gap:.5rem;">
            <span style="font-size:.85rem;font-weight:700;color:#7B1450;width:20px;"><?= $i ?>.</span>
            <input type="text" class="tally-word form-control"
              style="padding:.35rem .6rem;font-size:.82rem;"
              placeholder="word <?= $i ?>">
          </div>
          <?php endfor; ?>
        </div>

        <!-- Count input -->
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.25rem;">
          <div>
            <label style="font-size:.82rem;font-weight:700;color:#7B1450;display:block;margin-bottom:.3rem;">Total mispronounced words:</label>
            <div style="display:flex;align-items:center;gap:.5rem;">
              <button type="button" onclick="adjustCount(-1)" class="btn btn-ghost btn-sm" style="width:32px;padding:0;font-size:1.2rem;">−</button>
              <span id="misCount" style="font-size:1.75rem;font-weight:900;color:#7B1450;min-width:40px;text-align:center;">0</span>
              <button type="button" onclick="adjustCount(1)" class="btn btn-ghost btn-sm" style="width:32px;padding:0;font-size:1.2rem;">+</button>
            </div>
          </div>
          <button type="button" onclick="checkMilestone()" class="btn btn-primary">Check My Score</button>
        </div>

        <!-- Milestone result -->
        <div id="milestoneResult" style="display:none;border-radius:12px;padding:1.25rem;border:2px solid;margin-top:.5rem;">
          <div id="milestoneBadge" style="font-size:1rem;font-weight:800;margin-bottom:.5rem;"></div>
          <div id="milestoneFeedback" style="font-size:.875rem;line-height:1.6;"></div>
          <div id="milestoneNote" style="font-size:.78rem;margin-top:.75rem;font-style:italic;"></div>
        </div>
      </div>
    </div>

    <!-- STEP 3: Compare & Reflect -->    <div class="card section" id="sectionStep3" style="display:none;">
      <div class="card-header">
        <span class="card-title">&#128260; Step 3 — Compare &amp; Reflect</span>
      </div>
      <div class="card-body">
        <p class="text-muted text-sm" style="margin-bottom:1rem;">
          Listen to both recordings side by side. Identify gaps or errors in your articulation, then record again with those corrections in mind.
        </p>

        <div class="compare-box">
          <div class="compare-col">
            <div class="compare-col-label">🎙️ Facilitator Model</div>
            <?php if (!empty($material['model_audio_path']) && file_exists(__DIR__ . '/../' . $material['model_audio_path'])): ?>
              <audio controls style="width:100%;">
                <source src="<?= e('../' . $material['model_audio_path']) ?>">
              </audio>
            <?php else: ?>
              <p class="text-muted text-sm" style="margin-bottom:.5rem;">No model audio uploaded.</p>
              <button class="btn btn-ghost btn-sm" onclick="document.getElementById('ttsPlay').click()">▶ Use TTS instead</button>
            <?php endif; ?>
          </div>
          <div class="compare-col">
            <div class="compare-col-label">&#127908; Your Recording</div>
            <audio id="compareAudio" controls style="width:100%;"></audio>
          </div>
        </div>

        <div style="margin-top:1.25rem;padding:1rem;background:#FDF4FF;border-radius:8px;border:1px solid #E9B8D4;">
          <p style="font-size:.875rem;font-weight:600;color:#7B1450;margin-bottom:.5rem;">&#128221; Self-Reflection</p>
          <p class="text-muted text-sm">After comparing, record yourself again below — this time being mindful of the errors you noticed. Focus on the parts with minimal or no errors.</p>
        </div>

        <!-- Re-record section -->
        <div style="margin-top:1rem;">
          <p style="font-size:.875rem;font-weight:700;color:#1E293B;margin-bottom:.5rem;">&#127908; Re-Record (Improved Attempt)</p>
          <div class="recording-controls">
            <div class="recording-indicator" id="recIndicator2"></div>
            <span class="recording-timer" id="recTimer2">00:00</span>
            <button id="recStart2" class="btn btn-danger btn-sm">&#127908; Start Re-Recording</button>
            <button id="recStop2" class="btn btn-ghost btn-sm" disabled>&#9632; Stop</button>
          </div>
          <p id="recStatus2" class="text-muted text-sm mt-3"></p>
          <div id="audioPreview2" class="audio-preview" style="display:none;">
            <p class="audio-preview-label">&#128266; Your Improved Recording:</p>
            <audio id="myRecordingAudio2" controls></audio>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 4: Submit & Receive Feedback -->
    <div class="card section" id="sectionStep4">
      <div class="card-header">
        <span class="card-title">&#128228; Step 4 — Submit &amp; Receive Feedback</span>
      </div>
      <div class="card-body">
        <p class="text-muted text-sm mb-4" style="margin-bottom:.75rem;">
          Submit your <strong>best recording</strong> to your facilitator. They will provide personalized feedback to help you improve.
        </p>

        <div style="margin-bottom:1rem;padding:.75rem 1rem;background:#FDF4FF;border-radius:8px;border:1px solid #E9B8D4;font-size:.85rem;color:#7B1450;">
          <strong>Which recording to submit?</strong> Submit whichever sounds better — your first attempt or your improved re-recording.
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
          <button
            id="recSubmit"
            class="btn btn-secondary btn-lg"
            disabled
            data-material-id="<?= (int)$material['id'] ?>"
            data-base="../"
          >&#128228; Submit First Recording</button>

          <button
            id="recSubmit2"
            class="btn btn-primary btn-lg"
            disabled
            data-material-id="<?= (int)$material['id'] ?>"
            data-base="../"
          >&#128228; Submit Improved Recording</button>
        </div>

        <div id="submitSuccess" class="alert alert-success mt-4" style="display:none;">
          &#10003; Your recording has been submitted! Your facilitator will review it and provide feedback.
          <br><br>
          <a href="../student/my-recordings.php" class="btn btn-secondary btn-sm">View My Recordings</a>
          <a href="../dashboard.php" class="btn btn-ghost btn-sm">Back to Dashboard</a>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
// Step progress tracking
function markStepComplete(stepNum) {
  var el = document.getElementById('step' + stepNum);
  if (el) el.classList.add('completed');
  var next = document.getElementById('step' + (stepNum + 1));
  if (next) next.classList.add('active');
}

// Show compare section once first recording is done
document.addEventListener('DOMContentLoaded', function() {
  var recStop = document.getElementById('recStop');
  if (recStop) {
    recStop.addEventListener('click', function() {
      setTimeout(function() {
        // Show accuracy tracker
        var tracker = document.getElementById('sectionTracker');
        if (tracker) tracker.style.display = '';

        var sec3 = document.getElementById('sectionStep3');
        if (sec3) sec3.style.display = '';
        // Copy audio to compare box
        setTimeout(function() {
          var src1 = document.getElementById('myRecordingAudio');
          var cmp  = document.getElementById('compareAudio');
          if (src1 && cmp && src1.src) { cmp.src = src1.src; cmp.load(); }
          markStepComplete(2);
        }, 600);
      }, 800);
    });
  }

  // Second recorder
  initRecorder2();
});

var recState2 = { mediaRecorder: null, chunks: [], stream: null, timerInterval: null, seconds: 0, audioBlob: null };

function initRecorder2() {
  var btnStart  = document.getElementById('recStart2');
  var btnStop   = document.getElementById('recStop2');
  var btnSubmit = document.getElementById('recSubmit2');
  var preview   = document.getElementById('audioPreview2');
  var timerEl   = document.getElementById('recTimer2');
  var indicator = document.getElementById('recIndicator2');
  var statusEl  = document.getElementById('recStatus2');

  if (!btnStart) return;

  btnStart.addEventListener('click', async function () {
    try {
      recState2.stream  = await navigator.mediaDevices.getUserMedia({ audio: true });
      recState2.chunks  = [];
      recState2.seconds = 0;

      var options = {};
      if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) options.mimeType = 'audio/webm;codecs=opus';
      else if (MediaRecorder.isTypeSupported('audio/webm')) options.mimeType = 'audio/webm';

      recState2.mediaRecorder = new MediaRecorder(recState2.stream, options);
      recState2.mediaRecorder.ondataavailable = function(e) { if (e.data && e.data.size > 0) recState2.chunks.push(e.data); };
      recState2.mediaRecorder.onstop = function() {
        var mimeType = recState2.mediaRecorder.mimeType || 'audio/webm';
        recState2.audioBlob = new Blob(recState2.chunks, { type: mimeType });
        var url = URL.createObjectURL(recState2.audioBlob);
        if (preview) { preview.style.display = 'block'; var a = document.getElementById('myRecordingAudio2'); if (a) { a.src = url; a.load(); } }
        if (btnSubmit) btnSubmit.disabled = false;
        if (statusEl) statusEl.textContent = 'Improved recording ready. Submit below.';
        markStepComplete(3);
      };
      recState2.mediaRecorder.start(250);
      recState2.timerInterval = setInterval(function() { recState2.seconds++; if (timerEl) timerEl.textContent = formatTime2(recState2.seconds); }, 1000);
      btnStart.disabled = true;
      if (btnStop) btnStop.disabled = false;
      if (indicator) indicator.classList.add('active');
      if (statusEl) statusEl.textContent = 'Recording...';
    } catch(err) { if (statusEl) statusEl.textContent = 'Mic error: ' + err.message; }
  });

  if (btnStop) {
    btnStop.addEventListener('click', function() {
      if (recState2.mediaRecorder && recState2.mediaRecorder.state !== 'inactive') recState2.mediaRecorder.stop();
      if (recState2.stream) recState2.stream.getTracks().forEach(function(t) { t.stop(); });
      clearInterval(recState2.timerInterval);
      btnStart.disabled = false;
      btnStop.disabled = true;
      if (indicator) indicator.classList.remove('active');
    });
  }

  if (btnSubmit) {
    btnSubmit.addEventListener('click', function() {
      submitRecording(recState2.audioBlob, btnSubmit);
    });
  }
}

function submitRecording(blob, btn) {
  if (!blob) return;
  var materialId = btn.dataset.materialId;
  var formData = new FormData();
  var ext = blob.type.includes('ogg') ? 'ogg' : 'webm';
  formData.append('audio', blob, 'recording.' + ext);
  formData.append('material_id', materialId);

  // Attach accuracy tracker score if available
  var countEl = document.getElementById('misCount');
  if (countEl) {
    formData.append('mispronounced_count', countEl.textContent.trim());
  }
  var milestoneEl = document.getElementById('milestoneBadge');
  if (milestoneEl && milestoneEl.dataset.value) {
    formData.append('milestone', milestoneEl.dataset.value);
  }

  btn.disabled = true;
  btn.textContent = 'Uploading...';
  var base = btn.dataset.base || '';
  fetch(base + 'student/submit_recording.php', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        var successBox = document.getElementById('submitSuccess');
        if (successBox) successBox.style.display = 'block';
        btn.textContent = '✓ Submitted';
        markStepComplete(4);
      } else {
        btn.disabled = false;
        btn.textContent = 'Submit Recording';
        alert('Error: ' + (data.error || 'Upload failed.'));
      }
    })
    .catch(function() { btn.disabled = false; btn.textContent = 'Submit Recording'; });
}

function formatTime2(secs) {
  var m = Math.floor(secs/60).toString().padStart(2,'0');
  var s = (secs%60).toString().padStart(2,'0');
  return m+':'+s;
}

// Hook first submit button to recState from app.js
document.addEventListener('DOMContentLoaded', function() {
  var btn1 = document.getElementById('recSubmit');
  if (btn1) {
    btn1.addEventListener('click', function() {
      if (typeof recState !== 'undefined' && recState.audioBlob) {
        submitRecording(recState.audioBlob, btn1);
      }
    });
  }
  // Enable submit btn1 when first recording is ready
  var origStop = document.getElementById('recStop');
  if (origStop) {
    origStop.addEventListener('click', function() {
      setTimeout(function() {
        if (btn1) btn1.disabled = false;
      }, 800);
    });
  }
});

// ── Accuracy Tracker ──────────────────────────────────────
var misCount = 0;

function adjustCount(delta) {
  misCount = Math.max(0, misCount + delta);
  document.getElementById('misCount').textContent = misCount;
  // Reset result when count changes
  var res = document.getElementById('milestoneResult');
  if (res) res.style.display = 'none';
}

function checkMilestone() {
  var milestones = [
    {
      min: 1, max: 3,
      rawValue: 'Excellent',
      label: '🌟 Excellent!',
      color: '#065F46', bg: '#ECFDF5', border: '#A7F3D0',
      feedback: 'Very Good! You are doing amazing. Keep it up! Congratulations, you are officially ready and heading to the next session!',
      note: 'Learners with 1–3 mispronounced words are ready to move on to the next level — great work!'
    },
    {
      min: 4, max: 6,
      rawValue: 'Great Progress',
      label: '👏 Great Progress!',
      color: '#1E40AF', bg: '#EFF6FF', border: '#BFDBFE',
      feedback: 'Wonderful! You are making "The Shift" look easy. Your word stress is becoming very accurate and natural — keep that focus!',
      note: 'Learners with 4–6 mispronounced words are ready to move on to the next level — great work!'
    },
    {
      min: 7, max: 9,
      rawValue: 'Nice Job',
      label: '💪 Nice Job!',
      color: '#92400E', bg: '#FFFBEB', border: '#FDE68A',
      feedback: 'Way to go! You\'ve made a solid start on these longer words. Let\'s try reading one more time to make those multi-syllable words flow even better!',
      note: 'Learners with 7–9 mispronounced words are encouraged to re-read the text using the provided audio to improve accuracy before advancing.'
    },
    {
      min: 10, max: Infinity,
      rawValue: 'Brave Start',
      label: '🌱 A Brave Start!',
      color: '#7B1450', bg: '#FDF4FF', border: '#E9B8D4',
      feedback: 'It\'s not that bad! Word stress can be tricky when words get longer. It is better to hear the audio-visual recording again to listen for the "loud" part of the words. You can do this!',
      note: 'Learners with 10 or more mispronounced words are encouraged to re-read the text using the provided audio to improve accuracy before advancing.'
    },
  ];

  var count = misCount;
  var match = milestones.find(function(m) { return count >= m.min && count <= m.max; });
  if (!match) match = milestones[milestones.length - 1];

  var res = document.getElementById('milestoneResult');
  document.getElementById('milestoneBadge').textContent = match.label;
  document.getElementById('milestoneFeedback').textContent = match.feedback;
  document.getElementById('milestoneNote').textContent = '📌 ' + match.note;

  res.style.display = 'block';
  res.style.background = match.bg;
  res.style.borderColor = match.border;
  res.style.color = match.color;
  document.getElementById('milestoneBadge').style.color = match.color;
  document.getElementById('milestoneBadge').dataset.value = match.rawValue;
  document.getElementById('milestoneNote').style.color = match.color;

  // Scroll to result
  res.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
