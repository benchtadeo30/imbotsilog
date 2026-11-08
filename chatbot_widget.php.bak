<?php // Customer-service NLP chatbot widget with TTS ?>
<style>
/* ===== WIDGET CONTAINER ===== */
#cb-widget { position: fixed; bottom: 24px; right: 24px; z-index: 1500; font-family: 'Inter', Arial, sans-serif; }

/* ===== TOGGLE BUTTON ===== */
#cb-toggle { 
    width: 56px; height: 56px; border-radius: 50%; 
    background: linear-gradient(135deg,#ff7a00,#ff9a3c); color:#000; 
    border:none; font-size:24px; cursor:pointer; 
    box-shadow: 0 6px 24px rgba(255,122,0,0.4), 0 2px 6px rgba(0,0,0,0.3);
    transition: transform .15s, box-shadow .15s; 
    display: flex; align-items: center; justify-content: center;
}
#cb-toggle:hover { transform: scale(1.08); box-shadow: 0 8px 28px rgba(255,122,0,0.5), 0 4px 8px rgba(0,0,0,0.3); }
#cb-toggle:active { transform: scale(0.95); }

/* ===== PANEL ===== */
#cb-panel { 
    display: none; 
    position: fixed; bottom: 90px; right: 24px; 
    width: 360px; max-width: calc(100vw - 48px); 
    height: 500px; max-height: 75vh; 
    background: linear-gradient(180deg,#161616 0%,#0f0f0f 100%); 
    border: 1px solid rgba(255,122,0,0.18); 
    border-radius: 20px; 
    box-shadow: 
        0 20px 50px rgba(0,0,0,0.7),
        0 8px 24px rgba(0,0,0,0.5),
        inset 0 1px 0 rgba(255,255,255,0.03);
    flex-direction: column; overflow: hidden; z-index: 1501; 
}
#cb-panel.open { display: flex; animation: panelIn 0.3s cubic-bezier(0.16,1,0.3,1); }
@keyframes panelIn { 
    from { opacity: 0; transform: translateY(20px) scale(0.95); } 
    to { opacity: 1; transform: none; } 
}

/* ===== HEADER ===== */
#cb-header { 
    padding: 14px 16px; 
    background: linear-gradient(180deg,rgba(255,122,0,0.12),rgba(255,122,0,0.04)); 
    border-bottom: 1px solid rgba(255,122,0,0.1); 
    color:#fff; font-weight:700; font-size:14px; 
    display:flex; justify-content:space-between; align-items:center; 
    position: relative;
}
#cb-header::after {
    content: ''; position: absolute; bottom: 0; left: 16px; right: 16px;
    height: 1px; background: linear-gradient(90deg,transparent,rgba(255,122,0,0.3),transparent);
}
#cb-header button { 
    background:transparent; border:none; color:#8a8a8a; font-size:18px; 
    cursor:pointer; width: 32px; height: 32px; 
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}
#cb-header button:hover { background: rgba(255,255,255,0.08); color: #fff; }

/* ===== BODY ===== */
#cb-body { 
    flex: 1; overflow-y: auto; padding: 16px; 
    display: flex; flex-direction: column; gap: 12px; 
    scroll-behavior: smooth;
}
#cb-body::-webkit-scrollbar { width: 4px; }
#cb-body::-webkit-scrollbar-track { background: transparent; }
#cb-body::-webkit-scrollbar-thumb { background: rgba(255,122,0,0.3); border-radius: 2px; }

/* ===== MESSAGE BASE ===== */
.cb-msg { 
    padding: 12px 16px; 
    border-radius: 20px; 
    max-width: 82%; 
    font-size: 13.5px; 
    line-height: 1.55; 
    white-space: pre-wrap; 
    position: relative; 
    animation: msgIn 0.3s cubic-bezier(0.16,1,0.3,1); 
}
@keyframes msgIn { 
    from { opacity: 0; transform: translateY(12px) scale(0.96); } 
    to { opacity: 1; transform: none; } 
}

/* ===== USER MESSAGE (RIGHT) ===== */
.cb-msg.user { 
    align-self: flex-end; 
    background: linear-gradient(135deg,rgba(255,122,0,0.35) 0%,rgba(255,122,0,0.2) 100%); 
    color:#fff; 
    border: 1px solid rgba(255,122,0,0.45); 
    border-bottom-right-radius: 6px; 
    box-shadow: 
        0 4px 16px rgba(255,122,0,0.18),
        0 1px 3px rgba(0,0,0,0.3);
}

/* ===== BOT MESSAGE (LEFT) ===== */
.cb-msg.bot { 
    align-self: flex-start; 
    background: linear-gradient(135deg,#1f1f1f 0%,#151515 100%); 
    color:#ececec; 
    border: 1px solid rgba(255,255,255,0.06); 
    border-bottom-left-radius: 6px; 
    box-shadow: 
        0 4px 16px rgba(0,0,0,0.4),
        0 1px 3px rgba(0,0,0,0.3),
        inset 0 1px 0 rgba(255,255,255,0.02);
}

/* ===== AVATARS ===== */
.cb-msg .avatar { 
    display: inline-flex; width: 24px; height: 24px; 
    border-radius: 50%; align-items: center; justify-content: center; 
    font-size: 11px; flex-shrink: 0; 
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}
.cb-msg.user .avatar { 
    background: linear-gradient(135deg,#ff7a00,#ff9a3c); color: #000; 
    margin-left: 8px; margin-right: 0; order: 1; 
    box-shadow: 0 2px 8px rgba(255,122,0,0.4);
}
.cb-msg.bot .avatar { 
    background: linear-gradient(135deg,#3a3a3a,#2a2a2a); color: #ff7a00; 
    order: -1; margin-right: 8px; margin-left: 0;
}

/* ===== MESSAGE CONTENT ===== */
.cb-msg-content { 
    display: inline-flex; flex-direction: column; 
    max-width: 100%; 
    gap: 4px;
}

/* ===== LISTEN BUTTON ===== */
.cb-msg .listen { 
    margin-top: 2px; 
    display: inline-flex; align-items: center; gap: 5px; 
    background: rgba(255,122,0,0.18); 
    color: #ffcc80; 
    border: 1px solid rgba(255,122,0,0.35); 
    border-radius: 20px; 
    padding: 5px 12px; 
    font-size: 10.5px; 
    font-weight: 600;
    cursor: pointer; 
    transition: all 0.18s ease; 
    flex-shrink: 0;
}
.cb-msg .listen:hover { 
    background: rgba(255,122,0,0.3); 
    border-color: rgba(255,122,0,0.55); 
    color: #fff; 
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(255,122,0,0.25);
}
.cb-msg .listen:active { transform: scale(0.96); }

/* ===== TIMESTAMP ===== */
.cb-msg .msg-time { 
    font-size: 8.5px; color: #7a7a7a; 
    margin-top: 5px; display: block; 
    font-weight: 500; letter-spacing: 0.2px;
}
.cb-msg.user .msg-time { text-align: right; }
.cb-msg.bot .msg-time { text-align: left; }

/* ===== TYPING INDICATOR ===== */
#cb-typing { 
    display: none; 
    align-self: flex-start; 
    padding: 12px 16px; 
    background: linear-gradient(135deg,#1f1f1f 0%,#151515 100%); 
    border: 1px solid rgba(255,255,255,0.05); 
    border-radius: 20px; 
    border-bottom-left-radius: 6px; 
    color: #8a8a8a; 
    font-size: 13px; 
    animation: pulse 1.5s infinite ease-in-out;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
#cb-typing.show { display: block; }
#cb-typing::after { 
    content: '⠋'; 
    display: inline-block; 
    margin-left: 8px; 
    animation: typingDots 1.4s infinite steps(10); 
    color: #ff7a00; 
    font-size: 16px; 
    line-height: 1;
}
@keyframes typingDots { 
    0% { content: '⠋'; } 
    10% { content: '⠙'; } 
    20% { content: '⠹'; } 
    30% { content: '⠸'; } 
    40% { content: '⠼'; } 
    50% { content: '⠴'; } 
    60% { content: '⠦'; } 
    70% { content: '⠧'; } 
    80% { content: '⠇'; } 
    90% { content: '⠏'; } 
    100% { content: '⠋'; } 
}
@keyframes pulse { 
    0%, 100% { opacity: 0.6; border-color: rgba(255,255,255,0.05); } 
    50% { opacity: 1; border-color: rgba(255,122,0,0.3); } 
}

/* ===== INPUT AREA ===== */
#cb-input-row { 
    display: flex; gap: 10px; 
    padding: 14px 16px; 
    border-top: 1px solid rgba(255,255,255,0.04); 
    background: rgba(0,0,0,0.2);
    border-radius: 0 0 20px 20px;
}
#cb-input { 
    flex: 1; 
    padding: 12px 16px; 
    border-radius: 12px; 
    border: 1px solid rgba(255,122,0,0.25); 
    background: #0d0d0d; 
    color: #fff; 
    font-size: 13.5px; 
    font-family: 'Inter',Arial,sans-serif; 
    transition: all 0.18s ease; 
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.4);
}
#cb-input:focus { 
    outline: none; 
    border-color: #ff7a00; 
    box-shadow: 
        inset 0 2px 6px rgba(0,0,0,0.4),
        0 0 0 3px rgba(255,122,0,0.15);
}
#cb-input::placeholder { color: #5a5a5a; }
#cb-send { 
    background: linear-gradient(135deg,#ff7a00,#ff9a3c); 
    color: #000; 
    border: none; 
    border-radius: 12px; 
    padding: 12px 20px; 
    font-weight: 700; 
    font-size: 13px;
    cursor: pointer; 
    transition: all 0.15s ease; 
    box-shadow: 0 3px 12px rgba(255,122,0,0.35);
    white-space: nowrap;
}
#cb-send:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 6px 20px rgba(255,122,0,0.45); 
    background: linear-gradient(135deg,#ff851a,#ffa64d); 
}
#cb-send:active { 
    transform: scale(0.97) translateY(0); 
    box-shadow: 0 2px 6px rgba(255,122,0,0.3); 
}
#cb-send:focus-visible { 
    outline: none; 
    box-shadow: 0 0 0 3px rgba(255,122,0,0.4), 0 6px 20px rgba(255,122,0,0.45); 
}
</style>
<div id="cb-widget">
    <button id="cb-toggle" onclick="cbToggle()">💬</button>
    <div id="cb-panel">
        <div id="cb-header">Imbo't Silog Assistant <button onclick="cbToggle()">✕</button></div>
        <div id="cb-body">
            <div id="cb-msg" class="bot"><span class="avatar">🤖</span><span class="cb-msg-content">Kumusta! Ask me about our menu, prices, hours, location, or your order. 😊</span><button class="listen" onclick="cbSpeak(this.previousElementSibling?this.previousElementSibling.textContent:this.parentNode.textContent)">🔊 Listen</button><span class="msg-time"></span></div>
        </div>
        <div id="cb-typing">Assistant is typing…</div>
        <div id="cb-input-row">
            <input id="cb-input" type="text" placeholder="Type your question..." onkeydown="if(event.key==='Enter')cbSend()">
            <button id="cb-send" onclick="cbSend()">Send</button>
        </div>
    </div>
</div>
<script>
function cbToggle(){ document.getElementById('cb-panel').classList.toggle('open'); }
function cbSpeak(text){
    if(!window.speechSynthesis){ alert('Text-to-speech not supported in this browser.'); return; }
    window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(text);
    u.lang = 'en-PH';
    u.rate = 1.0;
    window.speechSynthesis.speak(u);
}
function formatTime(d){ return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}); }
// Set timestamp on initial welcome message
document.addEventListener('DOMContentLoaded', function(){
    var welcomeMsg = document.getElementById('cb-msg');
    if(welcomeMsg){
        var timeEl = welcomeMsg.querySelector('.msg-time');
        if(timeEl && !timeEl.textContent) timeEl.textContent = formatTime(new Date());
    }
});
function cbAddMsg(text, cls){
    const body = document.getElementById('cb-body');
    const d = document.createElement('div');
    d.className = 'cb-msg ' + cls;
    const now = new Date();
    const avatar = cls === 'user' ? '👤' : '🤖';
    d.innerHTML = '<span class="avatar">' + avatar + '</span><span class="cb-msg-content">' + text.replace(/\n/g, '<br>') + '</span>' + (cls === 'bot' ? '<button class="listen" onclick="cbSpeak(this.parentNode.querySelector(\'.cb-msg-content\').innerText)">🔊 Listen</button>' : '') + '<span class="msg-time">' + formatTime(now) + '</span>';
    body.appendChild(d);
    body.scrollTop = body.scrollHeight;
}
function setTyping(show){
    document.getElementById('cb-typing').classList.toggle('show', show);
}
async function cbSend(){
    const inp = document.getElementById('cb-input');
    const q = inp.value.trim();
    if(!q) return;
    cbAddMsg(q, 'user');
    inp.value = '';
    setTyping(true);
    try {
        const res = await fetch('chatbot.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({message: q}) });
        const data = await res.json();
        setTyping(false);
        cbAddMsg(data.answer || 'Sorry, I could not answer that.', 'bot');
    } catch(e) {
        setTyping(false);
        cbAddMsg('Sorry, I am offline right now. Please try again later.', 'bot');
    }
}
</script>
