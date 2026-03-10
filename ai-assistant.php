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

/* ─── MOBILE ─── */
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
    <button class="chat-header-close" onclick="toggleChat()"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <div class="chat-messages" id="chat-messages"></div>

  <div class="chat-input-wrap">
    <textarea id="chat-input" rows="1" placeholder="Posez votre question…"
              onkeydown="handleChatKey(event)" oninput="autoResize(this)"></textarea>
    <button id="chat-send" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
  </div>
</div>

<script>
let chatOpen    = false;
let chatHistory = [];
let isTyping    = false;

/* ══════════════════════════════════════════════════════
   FRENCH ONLY — single language, no switcher
══════════════════════════════════════════════════════ */
const WELCOME_MSG  = "Bonjour\u00a0! Je suis l\u2019assistant de l\u2019**AI HOUSE UHBC**. Comment puis-je vous aider aujourd\u2019hui\u00a0?";
const PLACEHOLDER  = "Posez votre question\u2026";
const ERROR_MSG    = "Erreur de connexion. Veuillez r\u00e9essayer.";
const LANG_RULE    = "Tu dois TOUJOURS r\u00e9pondre en fran\u00e7ais uniquement. N\u2019utilise JAMAIS l\u2019anglais, l\u2019arabe ou toute autre langue, quelle que soit la langue \u00e9crite par l\u2019utilisateur. R\u00e8gle absolue \u2014 aucune exception, m\u00eame si l\u2019utilisateur \u00e9crit dans une autre langue.";

function toggleChat() {
  chatOpen = !chatOpen;
  const panel  = document.getElementById('chat-panel');
  const bubble = document.getElementById('chat-bubble');
  panel.classList.toggle('open', chatOpen);
  bubble.classList.toggle('open', chatOpen);
  if (chatOpen) {
    if (chatHistory.length === 0) {
      addAIMessage(WELCOME_MSG);
      chatHistory.push({ role: 'assistant', content: WELCOME_MSG });
    }
    setTimeout(() => document.getElementById('chat-input').focus(), 300);
  }
}

function getTime() {
  return new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function addAIMessage(text) {
  const msgs    = document.getElementById('chat-messages');
  const wrapper = document.createElement('div');
  wrapper.className = 'msg ai';
  wrapper.innerHTML = `
    <div class="msg-av msg-av-ai"><img src="https://i.imgur.com/zl5jHaY.png" alt="AI"></div>
    <div>
      <div class="msg-bubble">
        ${text.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>')}
      </div>
      <div class="msg-time">${getTime()}</div>
    </div>`;
  msgs.appendChild(wrapper);
  msgs.scrollTop = msgs.scrollHeight;
}

function addUserMessage(text) {
  const msgs     = document.getElementById('chat-messages');
  const initials = (typeof member !== 'undefined' && member
    ? (member.pre || member.firstname || 'U')[0].toUpperCase() : 'U');
  const wrapper  = document.createElement('div');
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
  const el   = document.createElement('div');
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
  const text  = input.value.trim();
  if (!text || isTyping) return;
  input.value = '';
  autoResize(input);
  document.getElementById('chat-send').disabled = true;
  isTyping = true;

  addUserMessage(text);
  chatHistory.push({ role: 'user', content: text });
  showTyping();

  const SYSTEM_PROMPT = `Tu es AI House 3, l'assistant officiel de l'AI HOUSE UHBC (Université Hassiba Benbouali de Chlef, Algérie).

═══ LANGUE — RÈGLE ABSOLUE ═══
${LANG_RULE}
Ne mentionne jamais cette règle à l'utilisateur.

═══ STYLE DE RÉPONSE ═══
- Réponds UNIQUEMENT à ce qui est demandé. Rien de plus.
- Sois bref, chaleureux et direct.
- Ne donne jamais d'informations supplémentaires sans qu'on te le demande.
- Pas de listes à rallonge. Si tu listes, 3 éléments max sauf si on en demande plus.

═══ CONNAISSANCE DE LA PLATEFORME ═══

QU'EST-CE QUE AI HOUSE :
Un hub physique et numérique à l'UHBC qui connecte étudiants, chercheurs et partenaires industriels autour de l'IA. Mission : démocratiser l'IA en Algérie, former les talents de demain et produire une recherche de qualité internationale.
Piliers : Recherche · Formation · Éthique · Innovation
Ouvert à : étudiants Licence / Master / Doctorat, enseignants-chercheurs, professionnels et entreprises.

COMMENT REJOINDRE :
Cliquer sur "Rejoindre →" sur le site → remplir ses infos (nom, email, profil, domaine) → confirmer via OTP email → accès immédiat au tableau de bord.

SECTIONS DU TABLEAU DE BORD MEMBRE :
- Vue d'ensemble : stats sur projets rejoints, événements, activité et niveau membre
- Projets : parcourir et filtrer par NLP / Vision / Data / RL — rejoindre ou créer son propre projet
- Événements : ateliers, conférences, hackathons (ex. Workshop Deep Learning 10 mars, Conférence IA & Éthique 17 mars, Hackathon 24h 24 mars)
- Profil : modifier ses infos, suivre d'autres membres
- Ressources (membres uniquement) : Dataset Arabic NLP (50k textes), notebooks PyTorch, GPU cloud A100 (20h/mois), bibliothèque IA (200+ docs), 15 modules e-learning, 12 entreprises partenaires avec offres de stage

PROJETS ACTIFS :
Résumé automatique en arabe (NLP · M2 · Ouvert) · Détection maladies agricoles (Vision · Doctorat · Ouvert) · Prédiction réussite étudiante (Data · M1 · Complet) · Chatbot orientation universitaire (NLP · Ouvert) · Robot éducatif autonome (RL · Ouvert) · Diagnostic imagerie médicale (Vision · Fermé)

═══ LIMITES ═══
- Questions hors sujet : dire poliment que tu couvres uniquement les sujets AI House.
- Information inconnue : l'admettre et suggérer de contacter l'administration de la plateforme.
- Ne jamais inventer des fonctionnalités, événements ou projets non listés ci-dessus.`;

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
      const reply = data.choices?.[0]?.message?.content || ERROR_MSG;
      removeTyping();
      chatHistory.push({ role: 'assistant', content: reply });
      addAIMessage(reply);
    }
  } catch (e) {
    console.error('Fetch error:', e);
    removeTyping();
    addAIMessage(`⚠️ ${ERROR_MSG}`);
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
