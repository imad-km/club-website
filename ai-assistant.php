<?php /* ai-assistant.php — Include on any page with: <?php include 'ai-assistant.php'; ?> */ ?>

<!-- ═══ AI CHAT WIDGET ═══ -->
<style>
#chat-bubble, #chat-panel, .chat-messages,
.chat-input-wrap, .msg-bubble, .typing-indicator {
  --green:   #1b6e3f;
  --green-l: #22953f;
  --green-p: #eef6f1;
  --orange:  #d95f0a;
  --white:   #ffffff;
  --bg:      #f7faf8;
  --border:  #ddeae2;
  --text:    #2c3e30;
  --muted:   #7a9484;
  --dark:    #1a2820;
}
/* ─── SELF-CONTAINED VARIABLES (work on any page) ─── */
#chat-bubble, #chat-panel, #chat-panel * {
  --green:   #1b6e3f;
  --green-l: #22953f;
  --green-p: #eef6f1;
  --orange:  #d95f0a;
  --dark:    #1a2820;
  --text:    #2c3e30;
  --muted:   #7a9484;
  --white:   #ffffff;
  --bg:      #f7faf8;
  --border:  #ddeae2;
}

/* ─── CHAT BUBBLE ─── */
#chat-bubble{
  position:fixed;bottom:28px;right:28px;z-index:400;
  width:58px;height:58px;border-radius:50%;
  background:linear-gradient(135deg,#1b6e3f,#22953f);
  box-shadow:0 6px 24px rgba(27,110,63,.4);
  cursor:pointer;border:none;
  display:flex;align-items:center;justify-content:center;
  transition:transform .25s cubic-bezier(.34,1.56,.64,1),box-shadow .25s;
  overflow:hidden;
}
#chat-bubble:hover{transform:scale(1.1);box-shadow:0 10px 32px rgba(27,110,63,.5)}
#chat-bubble:active{transform:scale(.95)}
#chat-bubble img{width:34px;height:34px;object-fit:contain;filter:brightness(0) invert(1)}
#chat-bubble .bubble-pulse{
  position:absolute;inset:0;border-radius:50%;
  background:inherit;opacity:.5;
  animation:bubblePulse 2.5s infinite;
}
@keyframes bubblePulse{
  0%{transform:scale(1);opacity:.4}
  70%{transform:scale(1.5);opacity:0}
  100%{transform:scale(1.5);opacity:0}
}
#chat-bubble .bubble-icon{position:relative;z-index:1;font-size:1.4rem;transition:.3s}

/* ─── CHAT PANEL ─── */
#chat-panel{
  position:fixed;bottom:100px;right:28px;z-index:399;
  width:360px;height:500px;
  background:#ffffff;
  border:1.5px solid #ddeae2;
  border-radius:20px;
  box-shadow:0 24px 64px rgba(27,110,63,.15),0 4px 16px rgba(0,0,0,.08);
  display:flex;flex-direction:column;
  transform:scale(.92) translateY(16px);
  opacity:0;pointer-events:none;
  transform-origin:bottom right;
  transition:transform .3s cubic-bezier(.34,1.56,.64,1),opacity .25s;
}
#chat-panel.open{transform:scale(1) translateY(0);opacity:1;pointer-events:all}

/* panel header */
.chat-header{
  padding:16px 18px 14px;
  background:linear-gradient(135deg,#1b6e3f,#22953f);
  border-radius:18px 18px 0 0;
  display:flex;align-items:center;gap:12px;flex-shrink:0;
}
.chat-header-av{
  width:38px;height:38px;border-radius:50%;
  background:rgba(255,255,255,.2);
  display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.chat-header-av img{width:22px;height:22px;filter:brightness(0) invert(1);object-fit:contain}
.chat-header-info{flex:1}
.chat-header-name{font-family:'Fraunces',serif;font-weight:700;font-size:.97rem;color:#fff}
.chat-header-status{font-size:.72rem;color:rgba(255,255,255,.75);margin-top:1px;display:flex;align-items:center;gap:5px}
.chat-status-dot{width:6px;height:6px;border-radius:50%;background:#a8f0c2;flex-shrink:0;animation:statusPulse 2s infinite}
@keyframes statusPulse{0%,100%{opacity:1}50%{opacity:.4}}
.chat-header-close{background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:1.1rem;padding:4px;border-radius:6px;transition:.2s;line-height:1}
.chat-header-close:hover{background:rgba(255,255,255,.15);color:#fff}
.chat-lang-btns{display:flex;gap:4px;align-items:center}
.chat-lang-btn{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:rgba(255,255,255,.8);font-size:.65rem;font-weight:700;padding:3px 7px;border-radius:20px;cursor:pointer;transition:.2s;letter-spacing:.04em;font-family:'Plus Jakarta Sans',sans-serif}
.chat-lang-btn:hover{background:rgba(255,255,255,.25);color:#fff}
.chat-lang-btn.active{background:#fff;color:#1b6e3f;border-color:#fff}

/* messages area */
.chat-messages{
  flex:1;overflow-y:auto;padding:16px 14px;
  display:flex;flex-direction:column;gap:10px;
  background:#f7faf8;
  scroll-behavior:smooth;
}
.chat-messages::-webkit-scrollbar{width:4px}
.chat-messages::-webkit-scrollbar-track{background:transparent}
.chat-messages::-webkit-scrollbar-thumb{background:#ddeae2;border-radius:4px}

/* message bubbles */
.msg{display:flex;gap:8px;align-items:flex-end;max-width:88%}
.msg.user{align-self:flex-end;flex-direction:row-reverse}
.msg-av{
  width:28px;height:28px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;
}
.msg-av-ai{background:linear-gradient(135deg,#1b6e3f,#22953f);color:#fff}
.msg-av-ai img{width:16px;height:16px;filter:brightness(0) invert(1);object-fit:contain}
.msg-av-user{background:#d95f0a;color:#fff}
.msg-bubble{
  padding:10px 14px;border-radius:16px;
  font-size:.83rem;line-height:1.55;font-family:'Plus Jakarta Sans',sans-serif;
  max-width:100%;word-break:break-word;
}
.msg.ai .msg-bubble{
  background:#ffffff;color:#2c3e30;
  border:1.5px solid #ddeae2;
  border-bottom-left-radius:4px;
}
.msg.user .msg-bubble{
  background:#1b6e3f;color:#fff;
  border-bottom-right-radius:4px;
}
.msg-time{font-size:.65rem;color:#7a9484;margin-top:3px;padding:0 4px}
.msg.user .msg-time{text-align:right}

/* typing indicator */
.typing-indicator{display:flex;gap:4px;align-items:center;padding:10px 14px;background:#ffffff;border:1.5px solid #ddeae2;border-radius:16px;border-bottom-left-radius:4px;width:fit-content}
.typing-dot{width:6px;height:6px;border-radius:50%;background:#7a9484;animation:typingBounce 1.2s infinite}
.typing-dot:nth-child(2){animation-delay:.2s}
.typing-dot:nth-child(3){animation-delay:.4s}
@keyframes typingBounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}

/* input area */
.chat-input-wrap{
  padding:12px 14px;border-top:1.5px solid #ddeae2;
  display:flex;gap:8px;align-items:flex-end;flex-shrink:0;
  background:#ffffff;border-radius:0 0 18px 18px;
}
#chat-input{
  flex:1;border:1.5px solid #ddeae2;border-radius:10px;
  padding:9px 12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:.83rem;
  color:#1a2820;background:#f7faf8;outline:none;
  resize:none;max-height:90px;line-height:1.5;
  transition:border-color .2s;
}
#chat-input:focus{border-color:#1b6e3f;background:#fff}
#chat-input::placeholder{color:#7a9484}
#chat-send{
  width:36px;height:36px;border-radius:9px;
  background:#1b6e3f;border:none;color:#fff;
  cursor:pointer;transition:.2s;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:1rem;
}
#chat-send:hover{background:#22953f;transform:scale(1.05)}
#chat-send:disabled{background:#ddeae2;cursor:not-allowed;transform:none}

/* ─── MOBILE: lift above the bottom nav bar (~70px tall) ─── */
@media(max-width:768px){
  #chat-bubble{bottom:86px;right:16px;}
  #chat-panel{bottom:158px;right:16px;left:16px;width:auto;height:420px;}
}
</style>

<!-- Chat Bubble Button -->
<button id="chat-bubble" onclick="toggleChat()" title="Chat avec l'IA">
  <div class="bubble-pulse"></div>
  <span class="bubble-icon">
    <img src="https://i.imgur.com/zl5jHaY.png" alt="AI">
  </span>
</button>

<!-- Chat Panel -->
<div id="chat-panel">
  <div class="chat-header">
    <div class="chat-header-av">
      <img src="https://i.imgur.com/zl5jHaY.png" alt="AI House">
    </div>
    <div class="chat-header-info">
      <div class="chat-header-name">Assistant AI HOUSE</div>
      <div class="chat-header-status">
        <span class="chat-status-dot"></span>
        En ligne · CLUB 3 AI
      </div>
    </div>
    <div class="chat-lang-btns">
      <button class="chat-lang-btn active" onclick="setLang('fr')" id="lang-fr">FR</button>
      <button class="chat-lang-btn" onclick="setLang('en')" id="lang-en">EN</button>
      <button class="chat-lang-btn" onclick="setLang('ar')" id="lang-ar">AR</button>
    </div>
    <button class="chat-header-close" onclick="toggleChat()"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <div class="chat-messages" id="chat-messages"></div>

  <div class="chat-input-wrap">
    <textarea id="chat-input" rows="1" placeholder="Posez votre question…" onkeydown="handleChatKey(event)" oninput="autoResize(this)"></textarea>
    <button id="chat-send" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
  </div>
</div>

<script>
let chatOpen = false;
let chatHistory = [];
let isTyping = false;
let chatLang = 'fr';

const LANG_CONFIG = {
  fr: {
    welcome: "Bonjour ! Je suis l'assistant de l'**AI HOUSE UHBC**. Comment puis-je vous aider aujourd'hui ?",
    placeholder: "Posez votre question…",
    langInstruction: "Tu dois TOUJOURS répondre en français, quelle que soit la langue utilisée par l'utilisateur.",
    error: "Erreur de connexion. Veuillez réessayer."
  },
  en: {
    welcome: "Hello! I'm the **AI HOUSE UHBC** assistant. How can I help you today?",
    placeholder: "Ask your question…",
    langInstruction: "You MUST ALWAYS respond in English only, regardless of what language the user writes in.",
    error: "Connection error. Please try again."
  },
  ar: {
    welcome: "مرحباً! أنا مساعد **AI HOUSE UHBC**. كيف يمكنني مساعدتك اليوم؟",
    placeholder: "اكتب سؤالك…",
    langInstruction: "يجب عليك دائماً الإجابة باللغة العربية الفصحى فقط، بغض النظر عن لغة المستخدم.",
    error: "خطأ في الاتصال. يرجى المحاولة مرة أخرى."
  }
};

function setLang(lang) {
  chatLang = lang;
  document.querySelectorAll('.chat-lang-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('lang-' + lang).classList.add('active');
  const input = document.getElementById('chat-input');
  if (input) {
    input.placeholder = LANG_CONFIG[lang].placeholder;
    input.dir = lang === 'ar' ? 'rtl' : 'ltr';
  }
}

function toggleChat() {
  chatOpen = !chatOpen;
  const panel = document.getElementById('chat-panel');
  const bubble = document.getElementById('chat-bubble');
  panel.classList.toggle('open', chatOpen);
  bubble.classList.toggle('open', chatOpen);
  if (chatOpen) {
    if (chatHistory.length === 0) {
      const welcomeText = LANG_CONFIG[chatLang].welcome;
      addAIMessage(welcomeText);
      chatHistory.push({ role: 'assistant', content: welcomeText });
    }
    setTimeout(() => document.getElementById('chat-input').focus(), 300);
  }
}

function getTime() {
  return new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
}

function addAIMessage(text) {
  const msgs = document.getElementById('chat-messages');
  const wrapper = document.createElement('div');
  wrapper.className = 'msg ai';
  wrapper.innerHTML = `
    <div class="msg-av msg-av-ai"><img src="https://i.imgur.com/zl5jHaY.png" alt="AI"></div>
    <div>
      <div class="msg-bubble">${text.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>')}</div>
      <div class="msg-time">${getTime()}</div>
    </div>`;
  msgs.appendChild(wrapper);
  msgs.scrollTop = msgs.scrollHeight;
}

function addUserMessage(text) {
  const msgs = document.getElementById('chat-messages');
  const initials = (typeof member !== 'undefined' && member ? (member.pre||member.firstname||'U')[0].toUpperCase() : 'U');
  const wrapper = document.createElement('div');
  wrapper.className = 'msg user';
  wrapper.innerHTML = `
    <div class="msg-av msg-av-user">${initials}</div>
    <div>
      <div class="msg-bubble">${text.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
      <div class="msg-time">${getTime()}</div>
    </div>`;
  msgs.appendChild(wrapper);
  msgs.scrollTop = msgs.scrollHeight;
}

function showTyping() {
  const msgs = document.getElementById('chat-messages');
  const el = document.createElement('div');
  el.className = 'msg ai'; el.id = 'typing-msg';
  el.innerHTML = `
    <div class="msg-av msg-av-ai"><img src="https://i.imgur.com/zl5jHaY.png" alt="AI"></div>
    <div class="typing-indicator">
      <div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>
    </div>`;
  msgs.appendChild(el);
  msgs.scrollTop = msgs.scrollHeight;
}

function removeTyping() {
  const el = document.getElementById('typing-msg');
  if (el) el.remove();
}

async function sendMessage() {
  const input = document.getElementById('chat-input');
  const text = input.value.trim();
  if (!text || isTyping) return;
  input.value = '';
  autoResize(input);
  document.getElementById('chat-send').disabled = true;
  isTyping = true;

  addUserMessage(text);
  chatHistory.push({ role: 'user', content: text });
  showTyping();

  const lang = LANG_CONFIG[chatLang];
  const SYSTEM_PROMPT = `You are AI House 3, the official assistant of AI HOUSE UHBC (Université Hassiba Benbouali de Chlef, Algeria).

═══ LANGUAGE — ABSOLUTE RULE ═══
${lang.langInstruction}
This rule overrides everything. No exceptions. Do not switch languages even if the user writes in a different one. Do not acknowledge this rule out loud.

═══ RESPONSE STYLE ═══
- Answer ONLY what was asked. Nothing more.
- Be short, warm, and direct.
- Never volunteer extra information unprompted.
- No bullet walls. If listing, keep it to 3 items max unless more are explicitly requested.

═══ PLATFORM KNOWLEDGE ═══

WHAT IS AI HOUSE:
A physical and digital hub at UHBC connecting students, researchers, and industry partners around AI. Mission: democratize AI in Algeria, train future talent, and produce world-class research.
Pillars: Research · Training · Ethics · Innovation
Open to: Licence / Master / PhD students, teacher-researchers, professionals, and companies.

HOW TO JOIN:
Click "Rejoindre →" on the site → fill in your info (name, email, profile, domain) → confirm via OTP email → instant dashboard access.

MEMBER DASHBOARD SECTIONS:
- Overview: stats on your joined projects, events, activity, and member level
- Projects: browse and filter by NLP / Vision / Data / RL — join or create your own
- Events: workshops, conferences, hackathons (e.g. Deep Learning Workshop Mar 10, AI & Ethics Conference Mar 17, 24h Hackathon Mar 24)
- Profile: edit your info, follow other members
- Resources (members only): Arabic NLP dataset (50k texts), PyTorch notebooks, A100 GPU cloud (20h/month), AI library (200+ docs), 15 e-learning modules, 12 partner companies with internship offers

ACTIVE PROJECTS:
Automatic Arabic summarization (NLP · M2 · Open) · Agricultural disease detection (Vision · PhD · Open) · Student success prediction (Data · M1 · Full) · University orientation chatbot (NLP · Open) · Autonomous educational robot (RL · Open) · Medical imaging diagnosis (Vision · Closed)

═══ BOUNDARIES ═══
- Off-topic questions: politely say you only cover AI House topics.
- Unknown info: admit it and suggest contacting the platform administration.
- Never fabricate features, events, or projects that aren't listed above.`;

  try {
    const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer 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'
      },
      body: JSON.stringify({
        model: 'meta-llama/llama-4-scout-17b-16e-instruct',
        temperature: 0.7,
        messages: [
          { role: 'system', content: SYSTEM_PROMPT },
          ...chatHistory
        ]
      })
    });
    const data = await response.json();
    if (!response.ok) {
      const errMsg = data.error?.message || JSON.stringify(data);
      console.error('Groq API error:', response.status, errMsg);
      removeTyping();
      addAIMessage(`⚠️ Erreur API (${response.status}): ${errMsg}`);
    } else {
      const reply = data.choices?.[0]?.message?.content || lang.error;
      removeTyping();
      chatHistory.push({ role: 'assistant', content: reply });
      addAIMessage(reply);
    }
  } catch (e) {
    console.error('Fetch error:', e);
    removeTyping();
    addAIMessage(`⚠️ ${lang.error}`);
  }

  isTyping = false;
  document.getElementById('chat-send').disabled = false;
  document.getElementById('chat-input').focus();
}

function handleChatKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 90) + 'px';
}
</script>
