<!-- ══════════════ TERMINAL ══════════════ -->
<div class="app" id="app-container">

  <!-- Sidebar mobile backdrop overlay -->
  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <!-- Top bar -->
  <div class="topbar">
    <button class="menu-toggle-btn" id="menu-toggle" aria-label="Toggle Sidebar">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
    </button>
    <div class="topbar-dots">
      <div class="dot dot-r" title="Close"></div>
      <div class="dot dot-y" title="Minimize"></div>
      <div class="dot dot-g" title="Maximize"></div>
    </div>
    <div class="topbar-title"><span>PHPTerminal</span> — cPanel Shell</div>
    <div class="topbar-right">
      <div class="info-pill">
        <div class="status-dot"></div>
        <span class="user-pill-text"><?= htmlspecialchars($whoami) ?>@<?= htmlspecialchars($hostname) ?></span>
      </div>
      <form method="POST" style="margin:0">
        <input type="hidden" name="logout" value="1">
        <button type="submit" class="logout-btn">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          logout
        </button>
      </form>
    </div>
  </div>

  <div class="main">

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div>
        <div class="sidebar-section-title">Server Info</div>
        <div class="info-row">
          <div class="info-row-label">User</div>
          <div class="info-row-value acc"><?= htmlspecialchars($whoami) ?></div>
        </div>
        <div class="info-row">
          <div class="info-row-label">Host</div>
          <div class="info-row-value acc2"><?= htmlspecialchars($hostname) ?></div>
        </div>
        <div class="info-row">
          <div class="info-row-label">PHP</div>
          <div class="info-row-value"><?= htmlspecialchars($phpVersion) ?></div>
        </div>
        <div class="info-row">
          <div class="info-row-label">OS</div>
          <div class="info-row-value"><?= htmlspecialchars($serverOs) ?></div>
        </div>
        <div class="info-row">
          <div class="info-row-label">Directory</div>
          <div class="info-row-value" id="sidebar-cwd" style="font-size:10px"><?= htmlspecialchars($cwd) ?></div>
        </div>
      </div>

      <div>
        <div class="sidebar-section-title">Quick Commands</div>
        <?php foreach ($this->config['quick_commands'] as $label => $command): ?>
          <button class="quick-btn" onclick="runQuick(<?= htmlspecialchars(json_encode($command)) ?>)"><span class="qk-prefix">$</span> <?= htmlspecialchars($label) ?></button>
        <?php endforeach; ?>
      </div>

      <div>
        <div class="sidebar-section-title">Recent History</div>
        <div id="sidebar-history">
          <!-- Populated by JS -->
        </div>
      </div>

      <div>
        <div class="sidebar-section-title">Tips</div>
        <div style="font-size:10px; color: var(--text-mute); line-height:1.8">
          ↑ / ↓ — history<br>
          Ctrl+L — clear<br>
          Tab / → — autosuggest<br>
          Click quick cmd →
        </div>
      </div>
    </div>

    <!-- Terminal -->
    <div class="terminal-wrap">
      <div class="output-area" id="output">
        <div class="welcome-banner">
          <div class="welcome-title">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="var(--accent4)" style="margin-right: 6px;"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            PHP Terminal
          </div>
          <div class="welcome-sub">
            Connected as <strong style="color:var(--accent)"><?= htmlspecialchars($whoami) ?></strong>
            on <strong style="color:var(--accent2)"><?= htmlspecialchars($hostname) ?></strong>
            · PHP <?= htmlspecialchars($phpVersion) ?><br>
            Type a command below or choose a quick command from the sidebar. Type <strong style="color:var(--accent)">help</strong> for list of commands.
          </div>
        </div>
      </div>

      <!-- Input bar -->
      <div class="input-bar">
        <div class="input-prompt">
          <span class="prompt-user"><?= htmlspecialchars($whoami) ?></span>
          <span class="prompt-at">@</span>
          <span class="prompt-host"><?= htmlspecialchars(explode('.', $hostname)[0]) ?></span>
          <span class="prompt-dollar">$</span>
        </div>
        
        <div class="input-suggest-container">
          <div id="cmd-suggest"></div>
          <input
            type="text"
            id="cmd-input"
            placeholder="enter command…"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
            autofocus
          >
        </div>

        <button class="clear-btn" id="clear-btn" title="Clear (Ctrl+L)">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          clear
        </button>
        <button class="run-btn" id="run-btn">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
          RUN
        </button>
      </div>
    </div>

  </div><!-- /main -->
</div><!-- /app -->
