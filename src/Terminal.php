<?php

namespace SrvKit\Terminal;

class Terminal
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Constructor
     *
     * @param array $config Configuration parameters
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'password' => 'CS8854',
            'session_name' => 'php_terminal_session',
            'session_prefix' => 'php_terminal_',
            'cwd' => null,
            'blocked_commands' => ['rm -rf /', 'mkfs', ':(){:|:&};:', 'dd if=/dev/zero'],
            'allowed_commands' => null, // null means all commands are allowed (except blocked)
            'quick_commands' => [
                'ls -la' => 'ls -la',
                'pwd' => 'pwd',
                'whoami' => 'whoami',
                'php -v' => 'php -v',
                'df -h' => 'df -h',
                'free -h' => 'free -h',
                'ps aux' => 'ps aux',
                'env' => 'env',
                'os-release' => 'cat /etc/os-release',
                'uptime' => 'uptime',
                'ports' => 'netstat -tlnp 2>/dev/null || ss -tlnp',
                'find php' => "find . -name '*.php' | head -20",
            ]
        ], $config);
    }

    /**
     * Safe helper to start the session natively if not already active.
     */
    protected function startNativeSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set session name
            session_name($this->config['session_name']);
            // Session cookie security parameters
            if (!headers_sent()) {
                session_set_cookie_params([
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            session_start();
        }
    }

    /**
     * Get a value from session (framework-safe or native $_SESSION fallback)
     */
    protected function sessionGet($key, $default = null)
    {
        $fullKey = $this->config['session_prefix'] . $key;

        if (function_exists('session') && is_callable('session')) {
            try {
                $laravelSession = session();
                if (method_exists($laravelSession, 'get')) {
                    return $laravelSession->get($fullKey, $default);
                }
            } catch (\Throwable $e) {}
        }

        $this->startNativeSession();
        return $_SESSION[$fullKey] ?? $default;
    }

    /**
     * Set a value in session (framework-safe or native $_SESSION fallback)
     */
    protected function sessionSet($key, $value)
    {
        $fullKey = $this->config['session_prefix'] . $key;

        if (function_exists('session') && is_callable('session')) {
            try {
                $laravelSession = session();
                if (method_exists($laravelSession, 'put')) {
                    $laravelSession->put($fullKey, $value);
                    return;
                } elseif (method_exists($laravelSession, 'set')) {
                    $laravelSession->set($fullKey, $value);
                    return;
                }
            } catch (\Throwable $e) {}
        }

        $this->startNativeSession();
        $_SESSION[$fullKey] = $value;
    }

    /**
     * Destroy terminal session parameters
     */
    protected function sessionDestroy()
    {
        $fullKeyAuth = $this->config['session_prefix'] . 'authenticated';
        $fullKeyCwd = $this->config['session_prefix'] . 'cwd';

        if (function_exists('session') && is_callable('session')) {
            try {
                $laravelSession = session();
                if (method_exists($laravelSession, 'forget')) {
                    $laravelSession->forget($fullKeyAuth);
                    $laravelSession->forget($fullKeyCwd);
                    return;
                } elseif (method_exists($laravelSession, 'remove')) {
                    $laravelSession->remove($fullKeyAuth);
                    $laravelSession->remove($fullKeyCwd);
                    return;
                }
            } catch (\Throwable $e) {}
        }

        $this->startNativeSession();
        unset($_SESSION[$fullKeyAuth]);
        unset($_SESSION[$fullKeyCwd]);
    }

    /**
     * Verify if the request is an AJAX request
     */
    public function isAjaxRequest()
    {
        return isset($_POST['ajax']) || 
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * Execute a shell command and return output, error, path, and duration.
     *
     * @param string $cmd Raw command string
     * @return array
     */
    public function execute($cmd)
    {
        $cmd = trim($cmd);
        if (empty($cmd)) {
            return [
                'output' => '',
                'error' => null,
                'cwd' => $this->getCwd(),
                'time' => 0
            ];
        }

        // Intercept help command
        if (strtolower($cmd) === 'help') {
            return [
                'output' => $this->getHelpText(),
                'error' => null,
                'cwd' => $this->getCwd(),
                'time' => 0
            ];
        }

        // Intercept cpanel cheatsheet command
        if (in_array(strtolower($cmd), ['cpanel', 'cheatsheet', 'ref'])) {
            return [
                'output' => $this->getCheatsheetText(),
                'error' => null,
                'cwd' => $this->getCwd(),
                'time' => 0
            ];
        }

        // Security check
        if (!$this->isCommandAllowed($cmd)) {
            return [
                'output' => null,
                'error' => 'Command blocked for safety.',
                'cwd' => $this->getCwd(),
                'time' => 0
            ];
        }

        $start = microtime(true);
        $commandOutput = null;
        $commandError = null;

        $cwd = $this->getCwd();

        // Execution structure with multiple fallbacks
        try {
            // proc_open configuration
            if (function_exists('proc_open')) {
                $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];
                // Run in CWD directory
                $process = proc_open('cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1', $descriptors, $pipes);

                if (is_resource($process)) {
                    fclose($pipes[0]);
                    $commandOutput = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    proc_close($process);
                }
            } elseif (function_exists('shell_exec')) {
                $commandOutput = shell_exec('cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1');
            } elseif (function_exists('exec')) {
                $lines = [];
                exec('cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1', $lines);
                $commandOutput = implode("\n", $lines);
            } elseif (function_exists('system')) {
                ob_start();
                system('cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1');
                $commandOutput = ob_get_clean();
            } elseif (function_exists('passthru')) {
                ob_start();
                passthru('cd ' . escapeshellarg($cwd) . ' && ' . $cmd . ' 2>&1');
                $commandOutput = ob_get_clean();
            } else {
                $commandError = 'No execution function available (proc_open, shell_exec, exec, system, passthru are disabled).';
            }
        } catch (\Throwable $e) {
            $commandError = 'Execution error: ' . $e->getMessage();
        }

        // Handle directory change (cd) tracking
        if (preg_match('/^\s*cd\s+(.+)/', $cmd, $matches)) {
            $target = trim($matches[1]);
            // Remove quotes if present
            if (preg_match('/^["\'](.*)["\']$/', $target, $innerMatches)) {
                $target = $innerMatches[1];
            }
            
            // Treat relative/absolute paths
            if ($target === '~') {
                $newCwd = getenv('HOME') ?: getcwd();
            } else {
                $newCwd = realpath($cwd . '/' . $target);
            }

            if ($newCwd && is_dir($newCwd)) {
                $this->sessionSet('cwd', $newCwd);
                $cwd = $newCwd;
            } else {
                $commandError = 'cd: ' . $target . ': No such file or directory';
            }
        }

        $execTime = round((microtime(true) - $start) * 1000, 2);

        return [
            'output' => $commandOutput,
            'error' => $commandError,
            'cwd' => $cwd,
            'time' => $execTime,
        ];
    }

    /**
     * Check if the command is allowed based on allowed and blocked configurations
     */
    protected function isCommandAllowed($cmd)
    {
        // 1. Check blocked commands
        if (!empty($this->config['blocked_commands'])) {
            foreach ($this->config['blocked_commands'] as $blocked) {
                if (stripos($cmd, $blocked) !== false) {
                    return false;
                }
            }
        }

        // 2. Check allowed commands (if configured)
        if (!empty($this->config['allowed_commands'])) {
            $isAllowed = false;
            foreach ($this->config['allowed_commands'] as $allowed) {
                if (stripos($cmd, $allowed) !== false) {
                    $isAllowed = true;
                    break;
                }
            }
            return $isAllowed;
        }

        return true;
    }

    /**
     * Get tracked CWD from session or system default
     */
    protected function getCwd()
    {
        $cwd = $this->sessionGet('cwd');
        if (!$cwd || !is_dir($cwd)) {
            $cwd = $this->config['cwd'] ?? null;
            if (!$cwd || !is_dir($cwd)) {
                $cwd = getcwd();
            } else {
                $cwd = realpath($cwd);
            }
            $this->sessionSet('cwd', $cwd);
        }
        return $cwd;
    }

    /**
     * Set the current working directory programmatically.
     *
     * @param string $cwd Path to directory
     * @return bool
     */
    public function setCwd($cwd)
    {
        if ($cwd && is_dir($cwd)) {
            $realPath = realpath($cwd);
            $this->sessionSet('cwd', $realPath);
            return true;
        }
        return false;
    }

    /**
     * Standard Help response content
     */
    protected function getHelpText()
    {
        return <<<HELP
================================================================================
                    PHP WEB TERMINAL — HELP & SHORTCUTS
================================================================================

COMMANDS:
  help               Show this help screen
  cpanel             Show cPanel Terminal Reference cheatsheet (Composer, SSH, MySQL)
  clear              Clear the terminal screen
  logout             Terminate session and return to login
  whoami             Show current user
  pwd                Show current working directory
  ls -la             List directory contents with details
  php -v             Show PHP version information

KEYBOARD SHORTCUTS:
  Up Arrow / Down Arrow    Navigate command history
  Tab / Right Arrow        Accept auto-suggestions
  Ctrl + L                 Clear terminal screen

TIPS:
  - Command suggestions are displayed inline in a muted color.
  - To quick-fill and run a command, click any command button in the sidebar.
  - Use 'cd <dir>' to change directories. The directory is tracked across sessions.
  - All command execution falls back to available PHP methods automatically.
================================================================================
HELP;
    }

    /**
     * Get customized cPanel cheatsheet text dynamically replacing username/home.
     */
    protected function getCheatsheetText()
    {
        $home = getenv('HOME') ?: '/home/' . (get_current_user() ?: 'username');
        $username = basename($home);

        return <<<CHEATSHEET
================================================================================
                ⚡ cPANEL TERMINAL REFERENCE CHEATSHEET (DYNAMIC) ⚡
================================================================================
Detected User: {$username}
Detected Home: {$home}

1. SSH KEY GENERATION:
   ssh-keygen -t rsa -b 4096 -f ~/.ssh/id_rsa -N "" -C "your@email.com"
     (Generate RSA 4096-bit key; replace email with your own)
   ssh-keygen -t ed25519 -f ~/.ssh/id_ed25519 -N ""
     (Modern ed25519 key - faster/secure)
   cat ~/.ssh/id_rsa.pub
     (Print public key to copy to GitHub/servers)
   ls -la ~/.ssh/
     (List all SSH keys)

2. COMPOSER SETUP ON cPANEL:
   php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
     (Step 1: Download installer)
   HOME={$home} php composer-setup.php
     (Step 2: Run installer with HOME environment variable)
   mkdir -p ~/bin && mv composer.phar ~/bin/composer && chmod +x ~/bin/composer
     (Step 3: Move to bin and make executable)
   php ~/bin/composer --version
     (Step 4: Verify installation)
   echo "alias composer='HOME={$home} php ~/bin/composer'" >> ~/.bash_profile
     (Persist composer alias)
   source ~/.bash_profile
     (Reload bash profile to apply)

3. COMPOSER DAILY COMMANDS (PRE-ALIAS):
   HOME={$home} php ~/bin/composer install
     (Install all packages from composer.json)
   HOME={$home} php ~/bin/composer install --no-dev
     (Production install, skip dev packages)
   HOME={$home} php ~/bin/composer install --no-cache
     (Install fresh ignoring cache)
   HOME={$home} php -d memory_limit=-1 ~/bin/composer install
     (Install with unlimited memory limit for large projects)
   HOME={$home} php ~/bin/composer update
     (Update all package dependencies)
   HOME={$home} php ~/bin/composer require vendor/package
     (Add/require new package)
   HOME={$home} php ~/bin/composer dump-autoload
     (Regenerate class autoload files)

4. MYSQL DATABASE ACTIONS:
   mysql -u {$username}_dbuser -p {$username}_dbname < ~/repositories/my-project/database.sql
     (Import SQL file, prompts for password)
   mysql -u {$username}_dbuser -p{$username}_dbpass {$username}_dbname < ~/path/to/file.sql
     (Import SQL file with password inline - no space after -p)

5. QUICK DEBUG & INFO:
   whoami                        (Current cPanel username)
   php -v                        (Current PHP version)
   echo \$HOME                   (Current HOME directory)
   cat ~/.bash_profile           (Check shell profile configuration)
   ls -la ~/bin/                 (Verify composer is installed in bin)
   cat ~/.ssh/authorized_keys    (View authorized SSH keys on server)
================================================================================
CHEATSHEET;
    }

    /**
     * Handle the request and render the interface or process AJAX commands.
     */
    public function handle()
    {
        $authenticated = $this->sessionGet('authenticated') === true;
        $authError = null;

        // Process Logout
        if (isset($_POST['logout'])) {
            $this->sessionDestroy();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Process Authentication
        if (isset($_POST['password'])) {
            if ($_POST['password'] === $this->config['password']) {
                $this->sessionSet('authenticated', true);
                // Redirect to current request URI to prevent Form Resubmission prompt on reload
                $redirectUrl = $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'];
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $authError = 'Invalid password.';
            }
        }

        // Process AJAX Command Execution
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            if (!$authenticated) {
                echo json_encode([
                    'output' => null,
                    'error' => 'Session expired. Please log in again.',
                    'cwd' => getcwd(),
                    'time' => 0
                ]);
                exit;
            }

            $cmd = $_POST['cmd'] ?? '';
            $response = $this->execute($cmd);
            echo json_encode($response);
            exit;
        }

        // Render Page
        $this->render($authenticated, $authError);
    }

    /**
     * Render the HTML/CSS/JS page.
     *
     * @param bool $authenticated
     * @param string|null $authError
     */
    public function render($authenticated, $authError = null)
    {
        $cwd = $this->getCwd();
        $phpVersion = phpversion();
        $serverOs = php_uname('s') . ' ' . php_uname('r');
        $hostname = gethostname();
        
        $whoami = 'unknown';
        if (function_exists('shell_exec')) {
            $whoami = @trim(shell_exec('whoami 2>/dev/null')) ?: 'unknown';
        }
        if ($whoami === 'unknown' && function_exists('get_current_user')) {
            $whoami = @get_current_user();
        }

        include __DIR__ . '/views/layout.php';
    }
}
