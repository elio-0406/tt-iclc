const csrf = document.querySelector('meta[name="csrf-token"]').content;
const startBtn = document.getElementById('startBtn');
const stopBtn  = document.getElementById('stopBtn');
const statusEl = document.getElementById('status');
const transcriptEl = document.getElementById('transcript');
const scoreEl = document.getElementById('score');
const feedbackEl = document.getElementById('feedback');
const playbackEl = document.getElementById('playback');

const exerciseId = stopBtn.dataset.exerciseId;

const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
let recognition = null, transcriptText = '';

let mediaRecorder = null, mediaStream = null, chunks = [];
let mediaSupported = false;

function pickMime() {
  if (window.MediaRecorder && MediaRecorder.isTypeSupported) {
    if (MediaRecorder.isTypeSupported('audio/webm')) return 'audio/webm';   
    if (MediaRecorder.isTypeSupported('audio/mp4'))  return 'audio/mp4';    
    if (MediaRecorder.isTypeSupported('audio/ogg'))  return 'audio/ogg';    
    if (MediaRecorder.isTypeSupported('audio/wav'))  return 'audio/wav';
  }
  return '';
}

async function startRecording() {
  transcriptText = '';
  transcriptEl.textContent = '';
  chunks = [];
  mediaStream = null;
  mediaRecorder = null;

  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({
      audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
        channelCount: 1,
        sampleRate: 16000,       
        suppressLocalAudioPlayback: true, 
      }
    });
    const mime = pickMime();
    mediaRecorder = new MediaRecorder(
      mediaStream,
      mime ? { mimeType: mime,
              audioBitsPerSecond: 128000
       } : undefined,
    );
    mediaSupported = true;

    mediaRecorder.ondataavailable = e => {
      if (e.data && e.data.size > 0) chunks.push(e.data);
    };
    mediaRecorder.start(300);
    console.log('MediaRecorder bắt đầu, mime =', mime || '(auto)');
  } catch (err) {
    console.warn('MediaRecorder hong khả dụng:', err);
    mediaSupported = false;
  }

  if (Recognition) {
    recognition = new Recognition();
    recognition.lang = 'en-US';
    recognition.interimResults = true;
    recognition.continuous = true;
    recognition.onresult = (e) => {
      let t = '';
      for (let i = e.resultIndex; i < e.results.length; i++) {
        t += e.results[i][0].transcript;
      }
      transcriptText = t.trim();
      transcriptEl.textContent = transcriptText || '(đang nghe...)';
    };
    try { recognition.start(); } catch {}
  }

  statusEl.textContent = 'Đang ghi âm...';
  startBtn.disabled = true; 
  stopBtn.disabled = false;
}

async function stopRecording() {
  stopBtn.disabled = true;

  if (recognition) { try { recognition.stop(); } catch {} recognition = null; }

  let audioBlob = null;
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    const stopped = new Promise(resolve => {
      mediaRecorder.addEventListener('stop', resolve, { once: true });
    });
    try { mediaRecorder.requestData(); } catch {}
    mediaRecorder.stop();
    await stopped;

    try { mediaStream?.getTracks().forEach(t => t.stop()); } catch {}

    const mime = (chunks[0]?.type) || mediaRecorder.mimeType || 'audio/webm';
    audioBlob = chunks.length ? new Blob(chunks, { type: mime }) : null;

    if (audioBlob) {
      const url = URL.createObjectURL(audioBlob);
      playbackEl.src = url;
      playbackEl.classList.remove('hidden');
      console.log('blob size =', audioBlob.size, 'type =', audioBlob.type);
    } else {
      console.warn('Không có âm thanh được ghi.');
    }
  }

  statusEl.textContent = 'Đang chấm điểm...';

  if ((!transcriptText || transcriptText.trim() === '') && mediaSupported && audioBlob) {
    try {
      let filename = 'rec.webm';
      if (audioBlob.type.includes('mp4')) filename = 'rec.m4a';
      if (audioBlob.type.includes('ogg')) filename = 'rec.ogg';
      if (audioBlob.type.includes('wav')) filename = 'rec.wav';

      const fd = new FormData();
      fd.append('audio', audioBlob, filename);

      const res = await fetch('/exercises/transcribe', {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': csrf }
      });

      const text = await res.text();
      let j; try { j = JSON.parse(text); } catch (e) {
        console.error('ko phải JSON:', text);
        throw e;
      }
      if (j.ok) {
        transcriptText = j.transcript || '';
      } else {
        console.error('lỗi: ', j.diag || j.error);
      }
    } catch (e) {
      console.error('Chuyển đổi ghi âm ko thành công:', e);
    }
  }

  const res = await fetch('/exercises/get-result', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrf,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      exercise_id: exerciseId,
      transcript: transcriptText || ''
    })
  });

  const raw = await res.text();
  let data; 
  try { data = JSON.parse(raw); } 
  catch (e) { console.error('Non-JSON response:', raw); alert('Server error '+res.status); startBtn.disabled = false; return; }

  if (data.ok) {
    transcriptEl.textContent = data.transcript || transcriptText || '(trống)';
    scoreEl.textContent = (data.score ?? 0) + '/100';
    feedbackEl.textContent = data.feedback || '';
    statusEl.textContent = '';
  } else {
    statusEl.textContent = 'Hãy thử lại';
    console.error(data);
  }

  startBtn.disabled = false;
}

startBtn.addEventListener('click', startRec);
stopBtn.addEventListener('click', stopRec);
