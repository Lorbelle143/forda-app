/**
 * ReadEase - app.js
 * Handles: TTS, Audio Recording, Tabs, Nav, Confirm dialogs
 */

/* ============================================================
   UTILITY — two separate helpers, no naming conflict
============================================================ */
function qs(sel, ctx)  { return (ctx || document).querySelector(sel); }
function qsa(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

/* ============================================================
   BOOT
============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  initNav();
  initSidebarToggle();
  initTabs();
  initTTS();
  initRecorder();
  initConfirmButtons();
  initAutoDismissAlerts();
});

/* ============================================================
   NAV TOGGLE (mobile) — closes on link click too
============================================================ */
function initNav() {
  var toggle = document.getElementById('navToggle');
  var nav    = document.getElementById('mainNav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', function () {
    nav.classList.toggle('open');
  });

  qsa('a', nav).forEach(function (link) {
    link.addEventListener('click', function () {
      nav.classList.remove('open');
    });
  });
}

/* ============================================================
   TABS
============================================================ */
function initTabs() {
  qsa('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target    = btn.dataset.tab;
      var container = btn.closest('.tabs-container') || document;

      qsa('.tab-btn',   container).forEach(function (b) { b.classList.remove('active'); });
      qsa('.tab-panel', container).forEach(function (p) { p.classList.remove('active'); });

      btn.classList.add('active');
      var panel = document.getElementById(target);
      if (panel) panel.classList.add('active');
    });
  });
}

/* ============================================================
   TEXT-TO-SPEECH (Web Speech API)
============================================================ */
var ttsState = {
  utterance: null,
  words: [],
  wordEls: [],
  currentWordIndex: 0,
  isPaused: false,
  isPlaying: false
};

function initTTS() {
  var textBox  = document.getElementById('readingText');
  var btnPlay  = document.getElementById('ttsPlay');
  var btnPause = document.getElementById('ttsPause');
  var btnStop  = document.getElementById('ttsStop');
  var speedSel = document.getElementById('ttsSpeed');

  if (!textBox || !btnPlay) return;

  if (!('speechSynthesis' in window)) {
    var notice = document.getElementById('ttsNotice');
    if (notice) notice.style.display = 'block';
    [btnPlay, btnPause, btnStop].forEach(function (b) { if (b) b.disabled = true; });
    return;
  }

  // Wrap each word in a span for highlighting
  var rawText = textBox.dataset.rawText || textBox.innerText;
  textBox.dataset.rawText = rawText;
  var tokens  = rawText.split(/(\s+)/);
  var html    = '';
  var wordIdx = 0;
  tokens.forEach(function (token) {
    if (/\S/.test(token)) {
      html += '<span class="word" data-index="' + wordIdx + '">' + escapeHtml(token) + '</span>';
      wordIdx++;
    } else {
      html += escapeHtml(token);
    }
  });
  textBox.innerHTML = html;
  ttsState.wordEls  = qsa('.word', textBox);
  ttsState.words    = ttsState.wordEls.map(function (el) { return el.textContent; });

  if (speedSel) {
    speedSel.addEventListener('change', function () {
      if (ttsState.isPlaying) {
        var pos = ttsState.currentWordIndex;
        stopTTS();
        playTTSFrom(pos);
      }
    });
  }

  btnPlay.addEventListener('click', function () {
    if (ttsState.isPaused) {
      window.speechSynthesis.resume();
      ttsState.isPaused  = false;
      ttsState.isPlaying = true;
      updateTTSButtons(true, false);
    } else if (!ttsState.isPlaying) {
      playTTSFrom(0);
    }
  });

  if (btnPause) {
    btnPause.addEventListener('click', function () {
      if (ttsState.isPlaying && !ttsState.isPaused) {
        window.speechSynthesis.pause();
        ttsState.isPaused  = true;
        ttsState.isPlaying = false;
        updateTTSButtons(false, true);
      }
    });
  }

  if (btnStop) {
    btnStop.addEventListener('click', function () { stopTTS(); });
  }
}

function playTTSFrom(startIndex) {
  stopTTS(true);

  var textBox  = document.getElementById('readingText');
  var speedSel = document.getElementById('ttsSpeed');
  if (!textBox) return;

  var fullText = ttsState.words.slice(startIndex).join(' ');
  var utter    = new SpeechSynthesisUtterance(fullText);
  utter.rate   = speedSel ? parseFloat(speedSel.value) : 1;
  utter.lang   = 'en-US';

  ttsState.currentWordIndex = startIndex;
  ttsState.utterance        = utter;
  ttsState.isPlaying        = true;
  ttsState.isPaused         = false;

  utter.onboundary = function (event) {
    if (event.name === 'word') {
      clearWordHighlights();
      var charIndex = event.charIndex;
      var count     = 0;
      for (var i = startIndex; i < ttsState.words.length; i++) {
        if (count >= charIndex) {
          ttsState.currentWordIndex = i;
          highlightWord(i);
          break;
        }
        count += ttsState.words[i].length + 1;
      }
    }
  };

  utter.onend = function () {
    clearWordHighlights();
    ttsState.isPlaying        = false;
    ttsState.isPaused         = false;
    ttsState.currentWordIndex = 0;
    updateTTSButtons(false, false);
  };

  utter.onerror = function () {
    ttsState.isPlaying = false;
    ttsState.isPaused  = false;
    updateTTSButtons(false, false);
  };

  updateTTSButtons(true, false);
  window.speechSynthesis.speak(utter);
}

function stopTTS(silent) {
  window.speechSynthesis.cancel();
  clearWordHighlights();
  ttsState.isPlaying        = false;
  ttsState.isPaused         = false;
  ttsState.currentWordIndex = 0;
  if (!silent) updateTTSButtons(false, false);
}

function highlightWord(index) {
  if (ttsState.wordEls[index]) {
    ttsState.wordEls[index].classList.add('highlight');
    ttsState.wordEls[index].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }
}

function clearWordHighlights() {
  ttsState.wordEls.forEach(function (el) { el.classList.remove('highlight'); });
}

function updateTTSButtons(playing, paused) {
  var btnPlay  = document.getElementById('ttsPlay');
  var btnPause = document.getElementById('ttsPause');
  var btnStop  = document.getElementById('ttsStop');
  if (btnPlay)  btnPlay.disabled  = playing && !paused;
  if (btnPause) btnPause.disabled = !playing || paused;
  if (btnStop)  btnStop.disabled  = !playing && !paused;
}

function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/* ============================================================
   AUDIO RECORDER (MediaRecorder API)
============================================================ */
var recState = {
  mediaRecorder: null,
  chunks: [],
  stream: null,
  timerInterval: null,
  seconds: 0,
  audioBlob: null
};

function initRecorder() {
  var btnStart  = document.getElementById('recStart');
  var btnStop   = document.getElementById('recStop');
  var btnSubmit = document.getElementById('recSubmit');
  var preview   = document.getElementById('audioPreview');
  var timerEl   = document.getElementById('recTimer');
  var indicator = document.getElementById('recIndicator');
  var statusEl  = document.getElementById('recStatus');

  if (!btnStart) return;

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    if (statusEl) statusEl.textContent = 'Recording not supported in this browser.';
    btnStart.disabled = true;
    return;
  }

  btnStart.addEventListener('click', async function () {
    try {
      recState.stream  = await navigator.mediaDevices.getUserMedia({ audio: true });
      recState.chunks  = [];
      recState.seconds = 0;

      var options = {};
      if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
        options.mimeType = 'audio/webm;codecs=opus';
      } else if (MediaRecorder.isTypeSupported('audio/webm')) {
        options.mimeType = 'audio/webm';
      } else if (MediaRecorder.isTypeSupported('audio/ogg')) {
        options.mimeType = 'audio/ogg';
      }

      recState.mediaRecorder = new MediaRecorder(recState.stream, options);

      recState.mediaRecorder.ondataavailable = function (e) {
        if (e.data && e.data.size > 0) recState.chunks.push(e.data);
      };

      recState.mediaRecorder.onstop = function () {
        var mimeType       = recState.mediaRecorder.mimeType || 'audio/webm';
        recState.audioBlob = new Blob(recState.chunks, { type: mimeType });
        var url            = URL.createObjectURL(recState.audioBlob);

        if (preview) {
          preview.style.display = 'block';
          var audio = preview.querySelector('audio');
          if (audio) { audio.src = url; audio.load(); }
        }
        if (btnSubmit) btnSubmit.disabled = false;
        if (statusEl)  statusEl.textContent = 'Recording ready. Preview and submit below.';
      };

      recState.mediaRecorder.start(250);

      recState.timerInterval = setInterval(function () {
        recState.seconds++;
        if (timerEl) timerEl.textContent = formatTime(recState.seconds);
      }, 1000);

      btnStart.disabled = true;
      if (btnStop)   btnStop.disabled = false;
      if (indicator) indicator.classList.add('active');
      if (statusEl)  statusEl.textContent = 'Recording...';
      if (preview)   preview.style.display = 'none';
      if (btnSubmit) btnSubmit.disabled = true;

    } catch (err) {
      if (statusEl) statusEl.textContent = 'Microphone access denied: ' + err.message;
      console.error('Recorder error:', err);
    }
  });

  if (btnStop) {
    btnStop.addEventListener('click', function () {
      if (recState.mediaRecorder && recState.mediaRecorder.state !== 'inactive') {
        recState.mediaRecorder.stop();
      }
      if (recState.stream) {
        recState.stream.getTracks().forEach(function (t) { t.stop(); });
      }
      clearInterval(recState.timerInterval);
      btnStart.disabled = false;
      btnStop.disabled  = true;
      if (indicator) indicator.classList.remove('active');
    });
  }

  if (btnSubmit) {
    btnSubmit.addEventListener('click', function () {
      if (!recState.audioBlob) return;

      var materialId = btnSubmit.dataset.materialId;
      if (!materialId) { alert('Material ID missing.'); return; }

      var formData = new FormData();
      var ext      = recState.audioBlob.type.includes('ogg') ? 'ogg' : 'webm';
      formData.append('audio', recState.audioBlob, 'recording.' + ext);
      formData.append('material_id', materialId);

      btnSubmit.disabled    = true;
      btnSubmit.textContent = 'Uploading...';
      if (statusEl) statusEl.textContent = 'Uploading recording...';

      var base = btnSubmit.dataset.base || '';
      fetch(base + 'student/submit_recording.php', {
        method: 'POST',
        body: formData
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          if (statusEl) {
            statusEl.innerHTML = '<span style="color:#065F46;font-weight:600;">Recording submitted successfully!</span>';
          }
          var successBox = document.getElementById('submitSuccess');
          if (successBox) successBox.style.display = 'block';
          btnSubmit.textContent = 'Submitted';
        } else {
          if (statusEl) statusEl.textContent = 'Error: ' + (data.error || 'Upload failed.');
          btnSubmit.disabled    = false;
          btnSubmit.textContent = 'Submit Recording';
        }
      })
      .catch(function (err) {
        if (statusEl) statusEl.textContent = 'Network error. Please try again.';
        btnSubmit.disabled    = false;
        btnSubmit.textContent = 'Submit Recording';
        console.error(err);
      });
    });
  }
}

function formatTime(secs) {
  var m = Math.floor(secs / 60).toString().padStart(2, '0');
  var s = (secs % 60).toString().padStart(2, '0');
  return m + ':' + s;
}

/* ============================================================
   CONFIRM DELETE BUTTONS
============================================================ */
function initConfirmButtons() {
  qsa('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm || 'Are you sure?')) {
        e.preventDefault();
      }
    });
  });
}

/* ============================================================
   SIDEBAR TOGGLE (mobile)
============================================================ */
function initSidebarToggle() {
  var sidebarToggle = document.getElementById('sidebarToggle');
  var sidebar       = document.getElementById('appSidebar');
  var overlay       = document.getElementById('sidebarOverlay');
  if (!sidebarToggle || !sidebar) return;

  sidebarToggle.addEventListener('click', function () {
    sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('open');
  });

  if (overlay) {
    overlay.addEventListener('click', function () {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
    });
  }
}

/* ============================================================
   AUTO-DISMISS ALERTS after 5s
============================================================ */
function initAutoDismissAlerts() {
  qsa('.alert').forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity .4s';
      alert.style.opacity    = '0';
      setTimeout(function () { if (alert.parentNode) alert.remove(); }, 400);
    }, 5000);
  });
}
