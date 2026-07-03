const appContainer = document.getElementById('app-container');
const output       = document.getElementById('output');
const input        = document.getElementById('cmd-input');
const runBtn       = document.getElementById('run-btn');
const clearBtn     = document.getElementById('clear-btn');
const cmdSuggest   = document.getElementById('cmd-suggest');
const menuToggle   = document.getElementById('menu-toggle');
const sidebarOverlay = document.getElementById('sidebar-overlay');

let history_cmds = [];
let hist_idx     = -1;

const static_suggestions = [
  'ls -la', 'pwd', 'whoami', 'php -v', 'df -h', 'free -h', 'ps aux', 
  'env', 'uptime', 'netstat -tlnp', 'clear', 'help', 'cpanel', 'cheatsheet', 'ref', 'logout', 'composer install',
  'composer update', 'php artisan', 'php artisan migrate', 'php artisan tinker',
  'git status', 'git diff', 'git log --oneline -n 10', 'git branch',
  'composer dump-autoload', 'composer require', 'npm install', 'npm run dev',
  'yarn install', 'yarn dev', 'tail -n 50', 'cat ', 'mkdir ', 'touch ',
  'rm ', 'cp ', 'mv ', 'chmod ', 'chown '
];

// Initialize history from localStorage
try {
  const saved = localStorage.getItem('php_terminal_history');
  if (saved) {
    history_cmds = JSON.parse(saved);
  }
} catch (e) {}

// Populate sidebar history on load
updateHistorySidebar();

// Toggle Sidebar function
function toggleSidebar() {
  appContainer.classList.toggle('sidebar-active');
}

if (menuToggle) {
  menuToggle.addEventListener('click', toggleSidebar);
}
if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', toggleSidebar);
}

// ── Prompt label generator ──────────────────────────────────────────
function promptHTML(cwd, user, host) {
  const short = cwd.replace(/^\/home\/[^\/]+/, '~');
  return `<span class="prompt-user">${escHtml(user)}</span>`
       + `<span class="prompt-at">@</span>`
       + `<span class="prompt-host">${escHtml(host)}</span>`
       + `<span class="prompt-path">${escHtml(short)}</span>`
       + `<span class="prompt-dollar">$</span>`;
}

// ── Suggestion system ──────────────────────────────────────────
function getSuggestion(inputVal) {
  if (!inputVal) return '';
  const query = inputVal.toLowerCase();

  // Search local history first
  for (const cmd of history_cmds) {
    if (cmd && cmd.toLowerCase().startsWith(query) && cmd.length > query.length) {
      return cmd;
    }
  }

  // Search static suggestions list
  for (const cmd of static_suggestions) {
    if (cmd && cmd.toLowerCase().startsWith(query) && cmd.length > query.length) {
      return cmd;
    }
  }

  return '';
}

function updateSuggestion() {
  const val = input.value;
  const sug = getSuggestion(val);

  if (sug) {
    const typedLength = val.length;
    const sugSuffix = sug.substring(typedLength);
    const escapedTyped = escHtml(val).replace(/ /g, '&nbsp;');
    const escapedSuffix = escHtml(sugSuffix).replace(/ /g, '&nbsp;');
    cmdSuggest.innerHTML = `<span style="visibility:hidden">${escapedTyped}</span><span>${escapedSuffix}</span>`;
  } else {
    cmdSuggest.innerHTML = '';
  }
}

// ── Run command ──────────────────────────────────────────
async function runCommand(cmd) {
  cmd = cmd.trim();
  if (!cmd) return;

  // Intercept client-side clear command
  if (cmd.toLowerCase() === 'clear') {
    clearOutput();
    return;
  }

  // Save to history list
  if (history_cmds.length === 0 || history_cmds[0] !== cmd) {
    history_cmds.unshift(cmd);
    if (history_cmds.length > 100) history_cmds.pop();
    try {
      localStorage.setItem('php_terminal_history', JSON.stringify(history_cmds));
    } catch (e) {}
    updateHistorySidebar();
  }
  hist_idx = -1;

  // Append command entry
  const entry = document.createElement('div');
  entry.className = 'entry';
  entry.innerHTML = `
    <div class="entry-cmd">
      ${promptHTML(current_cwd, SERVER_USER, SERVER_HOST)}
      <span class="cmd-text">${escHtml(cmd)}</span>
    </div>
    <div class="entry-output-wrap">
      <div class="entry-output" id="out-pending" style="color:var(--text-dim);font-style:italic">running…</div>
    </div>
  `;
  output.appendChild(entry);
  scrollBottom();

  // Loading state
  runBtn.classList.add('loading');
  runBtn.innerHTML = `<span class="spinner"></span> WAIT`;

  try {
    const fd = new FormData();
    fd.append('cmd', cmd);
    fd.append('ajax', '1');

    const res  = await fetch(window.location.href, { method: 'POST', body: fd });
    const data = await res.json();

    // Update cwd
    if (data.cwd) {
      current_cwd = data.cwd;
      document.getElementById('sidebar-cwd').textContent = data.cwd;
    }

    const outEl = entry.querySelector('#out-pending');
    outEl.removeAttribute('id');

    if (data.error) {
      outEl.className = 'entry-output err';
      outEl.textContent = data.error;
      addCopyButton(outEl);
    } else if (data.output === '' || data.output === null) {
      outEl.className = 'entry-output empty';
      outEl.textContent = '(no output)';
    } else {
      outEl.className = 'entry-output';
      outEl.textContent = data.output;
      addCopyButton(outEl);
    }

    // Meta line
    if (data.time !== null) {
      const meta = document.createElement('div');
      meta.className = 'entry-meta';
      meta.innerHTML = data.error
        ? `<span class="fail"><svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> error</span><span>${data.time}ms</span>`
        : `<span class="ok"><svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> done</span><span>${data.time}ms</span>`;
      entry.appendChild(meta);
    }
  } catch (e) {
    const outEl = entry.querySelector('#out-pending') || entry.lastElementChild;
    if (outEl) {
      outEl.className = 'entry-output err';
      outEl.textContent = 'Request failed: ' + e.message;
      addCopyButton(outEl);
    }
  }

  runBtn.classList.remove('loading');
  runBtn.innerHTML = `<svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M8 5v14l11-7z"/></svg> RUN`;
  scrollBottom();
}

function addCopyButton(outEl) {
  const wrap = outEl.parentElement;
  if (!wrap) return;

  // Avoid adding duplicate buttons
  if (wrap.querySelector('.copy-output-btn')) return;

  const btn = document.createElement('button');
  btn.className = 'copy-output-btn';
  btn.title = 'Copy output';
  btn.innerHTML = `<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>`;
  
  btn.addEventListener('click', async (e) => {
    e.stopPropagation(); // prevent terminal input focus stealing
    
    const text = outEl.textContent;
    try {
      await navigator.clipboard.writeText(text);
      btn.classList.add('copied');
      btn.innerHTML = `<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="var(--accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
      
      setTimeout(() => {
        btn.classList.remove('copied');
        btn.innerHTML = `<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>`;
      }, 1500);
    } catch (err) {
      console.error('Clipboard copy failed:', err);
    }
  });

  wrap.appendChild(btn);
}

function runQuick(cmd) {
  input.value = cmd;
  input.focus();
  cmdSuggest.innerHTML = '';
  runCommand(cmd);
  input.value = '';
}

function updateHistorySidebar() {
  const histEl = document.getElementById('sidebar-history');
  if (!histEl) return;
  histEl.innerHTML = '';

  const uniqueHist = [];
  for (const c of history_cmds) {
    if (c && !uniqueHist.includes(c)) {
      uniqueHist.push(c);
    }
    if (uniqueHist.length >= 8) break;
  }

  if (uniqueHist.length === 0) {
    histEl.innerHTML = '<div style="font-size:10px; color: var(--text-mute); padding: 5px 0; font-style:italic">No history yet</div>';
    return;
  }

  uniqueHist.forEach(cmd => {
    const btn = document.createElement('button');
    btn.className = 'quick-btn';
    btn.innerHTML = `<span class="qk-prefix">$</span> ${escHtml(cmd)}`;
    btn.title = cmd;
    btn.onclick = () => runQuick(cmd);
    histEl.appendChild(btn);
  });
}

function scrollBottom() {
  output.scrollTop = output.scrollHeight;
}

function escHtml(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Input events ─────────────────────────────────────────
input.addEventListener('input', updateSuggestion);

input.addEventListener('keydown', e => {
  const currentSuggestion = getSuggestion(input.value);

  // Tab or Right Arrow (when cursor is at end of input) to auto-fill suggestion
  if (e.key === 'Tab' || (e.key === 'ArrowRight' && input.selectionStart === input.value.length)) {
    if (currentSuggestion) {
      e.preventDefault();
      input.value = currentSuggestion;
      cmdSuggest.innerHTML = '';
    }
  } else if (e.key === 'Enter') {
    const cmd = input.value;
    input.value = '';
    cmdSuggest.innerHTML = '';
    runCommand(cmd);
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    if (hist_idx < history_cmds.length - 1) {
      hist_idx++;
      input.value = history_cmds[hist_idx];
      cmdSuggest.innerHTML = '';
    }
  } else if (e.key === 'ArrowDown') {
    e.preventDefault();
    if (hist_idx > 0) {
      hist_idx--;
      input.value = history_cmds[hist_idx];
      cmdSuggest.innerHTML = '';
    } else {
      hist_idx = -1;
      input.value = '';
      cmdSuggest.innerHTML = '';
    }
  }
});

document.addEventListener('keydown', e => {
  if (e.ctrlKey && e.key === 'l') {
    e.preventDefault();
    clearOutput();
  }
});

runBtn.addEventListener('click', () => {
  const cmd = input.value;
  input.value = '';
  cmdSuggest.innerHTML = '';
  runCommand(cmd);
});

clearBtn.addEventListener('click', clearOutput);

function clearOutput() {
  output.innerHTML = '';
  input.focus();
  cmdSuggest.innerHTML = '';
}

// Keep input focused when clicking anywhere in terminal wrap except interactive elements or when selecting text
document.querySelector('.terminal-wrap').addEventListener('click', e => {
  const selectedText = window.getSelection().toString();
  if (selectedText) {
    return; // Do not steal focus if there's an active text selection
  }
  if (e.target !== input && e.target !== runBtn && e.target !== clearBtn && !runBtn.contains(e.target) && !clearBtn.contains(e.target) && !e.target.closest('.copy-output-btn')) {
    input.focus();
  }
});
