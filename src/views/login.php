<!-- ══════════════ LOGIN ══════════════ -->
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo-wrap">
      <svg class="login-logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Terminal Window Frame -->
        <rect x="2" y="3" width="20" height="18" rx="2.5" stroke="var(--border)" stroke-width="1.5" />
        <line x1="2" y1="8" x2="22" y2="8" stroke="var(--border)" stroke-width="1.5" />
        
        <!-- Command Prompt Chevron (>) -->
        <path d="M6 11l2.5 1.5L6 14M8.5 12.5" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        
        <!-- Cursor Line (_) -->
        <line x1="10.5" y1="14" x2="14.5" y2="14" stroke="var(--accent2)" stroke-width="2" stroke-linecap="round" />
        
        <!-- Top bar macOS style Dots -->
        <circle cx="5" cy="5.5" r="0.75" fill="var(--accent3)" />
        <circle cx="7.5" cy="5.5" r="0.75" fill="var(--accent4)" />
        <circle cx="10" cy="5.5" r="0.75" fill="var(--accent5)" />
      </svg>
      <div class="login-logo">PHPTerminal</div>
    </div>
    <div class="login-sub">CPANEL SHELL ACCESS · AUTHENTICATE TO CONTINUE</div>
    <form method="POST">
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" autofocus autocomplete="current-password" placeholder="Enter access password">
      </div>
      <button type="submit" class="login-btn">
        <span>CONNECT</span>
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
      </button>
      <?php if ($authError): ?>
        <div class="auth-err">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <span><?= htmlspecialchars($authError) ?></span>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>
