# PHP Terminal Library

A secure, framework-compatible, modern PHP Web Terminal library with rich features. Designed for easy drop-in integration into any PHP framework (Laravel, CodeIgniter 4, LeafPHP, etc.) or standalone PHP files.

## Features

- **Recent Command History**: Persisted in `localStorage` (survives tab/browser closures) and displayed dynamically in the sidebar.
- **Recent Command Suggestions**: Fish-shell/zsh style inline autocomplete suggestions based on both command history and common shell commands. Accepts suggestions via `Tab` or `Right Arrow`.
- **Terminal Help**: Built-in `help` command intercepts and displays keyboard shortcuts, available quick commands, and tips.
- **Password-Only Security**: Sleek, minimalist login interface requiring only a password.
- **Mobile Compatibility**: Responsively designed; on mobile viewports, the sidebar becomes a toggleable drawer sliding smoothly via CSS transition animations.
- **Clean SVG Icons**: Completely emoji-free layout using crisp, inline SVGs for high-performance and modern developer design aesthetics.

---

## Standalone Usage

```php
// index.php
require_once 'src/Terminal.php';

$terminal = new \SrvKit\Terminal\Terminal([
    'password' => 'CS8854', // Your secure access password
]);

$terminal->handle();
```

---

## Framework Integration Guides

### 1. Laravel Integration

#### Step 1: Install
Place the library files in your project (e.g. `app/Services/Terminal.php`) or install via local Composer path.

#### Step 2: Create a Controller
Generate a controller or use a route closure:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SrvKit\Terminal\Terminal;

class TerminalController extends Controller
{
    public function index(Request $request)
    {
        $terminal = new Terminal([
            'password' => env('TERMINAL_PASSWORD', 'CS8854'),
        ]);

        // Hand over request handling to the library
        $terminal->handle();
    }
}
```

#### Step 3: Define Routes
Define a match route that handles both `GET` (render page) and `POST` (auth/execute commands) in `routes/web.php`:

```php
use App\Http\Controllers\TerminalController;

Route::match(['get', 'post'], '/terminal', [TerminalController::class, 'index']);
```

#### Step 4: Configure CSRF Protection
Since the terminal uses AJAX POST requests, you must exclude the terminal path from CSRF verification.
- **Laravel 11+** (`bootstrap/app.php`):
  ```php
  $middleware->validateCsrfTokens(except: [
      '/terminal',
  ]);
  ```
- **Laravel 10 and below** (`app/Http/Middleware/VerifyCsrfToken.php`):
  ```php
  protected $except = [
      '/terminal',
  ];
  ```

---

### 2. CodeIgniter 4 (CI4) Integration

#### Step 1: Create a Controller
Create a controller `app/Controllers/TerminalController.php`:

```php
namespace App\Controllers;

use CodeIgniter\Controller;
use SrvKit\Terminal\Terminal;

class TerminalController extends Controller
{
    public function index()
    {
        $terminal = new Terminal([
            'password' => 'CS8854', // Or load from config/env
        ]);

        $terminal->handle();
    }
}
```

#### Step 2: Define Routes
In `app/Config/Routes.php`, add:

```php
$routes->match(['get', 'post'], 'terminal', '\App\Controllers\TerminalController::index');
```

#### Step 3: Configure CSRF Filter (If Enabled)
If you have global CSRF protection enabled in `app/Config/Filters.php`, make sure to exclude your terminal route:

```php
public array $globals = [
    'before' => [
        'csrf' => ['except' => ['terminal']],
    ],
];
```

---

### 3. LeafPHP Integration

#### Step 1: Create Route
Define a route matching both GET and POST requests in your main file (e.g. `index.php`):

```php
use SrvKit\Terminal\Terminal;

app()->match('/terminal', function () {
    $terminal = new Terminal([
        'password' => 'CS8854',
    ]);
    
    $terminal->handle();
});
```

---

## Configuration Reference

You can configure the terminal by passing an array of options to the constructor:

| Option | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `password` | `string` | `'CS8854'` | Access password for login page |
| `session_name` | `string` | `'php_terminal_session'` | Name of the native PHP session cookie |
| `session_prefix` | `string` | `'php_terminal_'` | Prefix for storing variables in the session |
| `cwd` | `string\|null` | `null` | Initial current working directory (e.g. project storage/root) |
| `blocked_commands` | `array` | `['rm -rf /', 'mkfs', ...]` | Blocked dangerous commands (case-insensitive) |
| `allowed_commands` | `array\|null` | `null` | Array of allowed commands (if not null, only these are allowed) |
| `quick_commands` | `array` | `['ls -la' => 'ls -la', ...]` | Label-to-command mappings displayed in the sidebar |

### Setting Directory Programmatically

You can also set or override the working directory programmatically before handling the request:

```php
$terminal = new \SrvKit\Terminal\Terminal();
$terminal->setCwd('/path/to/your/custom/directory');
$terminal->handle();
```
