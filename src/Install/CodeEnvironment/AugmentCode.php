<?php

declare(strict_types=1);

namespace Laravel\Boost\Install\CodeEnvironment;

use Laravel\Boost\Contracts\Agent;
use Laravel\Boost\Contracts\McpClient;
use Laravel\Boost\Install\Enums\Platform;

/**
 * Augment Code environment for VS Code.
 *
 * Note: Augment Code is a plugin that works across multiple IDEs (VS Code, JetBrains, Vim/Neovim).
 * This implementation focuses on VS Code integration since it uses file-based configuration
 * that Laravel Boost can automatically manage. For other IDEs, users need to configure
 * MCP servers manually through the Augment Code plugin settings.
 *
 * @see https://docs.augmentcode.com/introduction
 */
class AugmentCode extends CodeEnvironment implements Agent, McpClient
{
    public function name(): string
    {
        return 'augmentcode';
    }

    public function displayName(): string
    {
        return 'Augment Code';
    }

    public function systemDetectionConfig(Platform $platform): array
    {
        // Augment Code works in VS Code, JetBrains IDEs, and Vim/Neovim
        // We detect any of these IDEs since Augment Code can be installed in any of them
        return match ($platform) {
            Platform::Darwin => [
                'paths' => [
                    '/Applications/Visual Studio Code.app',
                    '/Applications/PhpStorm.app',
                    '/Applications/IntelliJ IDEA.app',
                    '/Applications/WebStorm.app',
                    '/Applications/PyCharm.app',
                    '/usr/local/bin/nvim',
                    '/opt/homebrew/bin/nvim',
                ],
            ],
            Platform::Linux => [
                'command' => 'which code || which phpstorm || which idea || which webstorm || which pycharm || which nvim || which vim',
            ],
            Platform::Windows => [
                'paths' => [
                    '%ProgramFiles%\\Microsoft VS Code',
                    '%LOCALAPPDATA%\\Programs\\Microsoft VS Code',
                    '%ProgramFiles%\\JetBrains\\PhpStorm*',
                    '%ProgramFiles%\\JetBrains\\IntelliJ IDEA*',
                    '%ProgramFiles%\\JetBrains\\WebStorm*',
                    '%ProgramFiles%\\JetBrains\\PyCharm*',
                    '%ProgramFiles%\\Neovim',
                ],
            ],
        };
    }

    public function projectDetectionConfig(): array
    {
        return [
            'paths' => ['.vscode', '.idea'],
            'files' => ['.vscode/settings.json', '.idea/workspace.xml', '.nvimrc', '.vimrc'],
        ];
    }

    public function mcpConfigPath(): string
    {
        return '.vscode/settings.json';
    }

    public function mcpConfigKey(): string
    {
        return 'mcpServers';
    }

    public function agentName(): string
    {
        return 'Augment Agent';
    }

    public function mcpClientName(): string
    {
        return 'Augment Code';
    }

    public function guidelinesPath(): string
    {
        return '.vscode/augment-guidelines.md';
    }

    /**
     * Override MCP installation to handle multiple IDE environments
     *
     * @param array<int, string> $args
     * @param array<string, string> $env
     */
    public function installMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $basePath = getcwd();

        // Try VS Code first (automatic configuration)
        if (is_dir($basePath . '/.vscode')) {
            return $this->installVSCodeMcp($key, $command, $args, $env);
        }

        // Check for JetBrains IDE (manual configuration required)
        if (is_dir($basePath . '/.idea')) {
            $this->displayJetBrainsInstructions($key, $command, $args, $env);
            return true;
        }

        // Check for Vim/Neovim (manual configuration required)
        if (file_exists($basePath . '/.nvimrc') || file_exists($basePath . '/.vimrc')) {
            $this->displayVimInstructions($key, $command, $args, $env);
            return true;
        }

        // Default: provide instructions for all supported IDEs
        $this->displayGeneralInstructions($key, $command, $args, $env);
        return true;
    }

    /**
     * Install MCP configuration for VS Code (automatic)
     */
    private function installVSCodeMcp(string $key, string $command, array $args = [], array $env = []): bool
    {
        $settingsPath = '.vscode/settings.json';

        // Ensure .vscode directory exists
        $vsCodeDir = dirname($settingsPath);
        if (!is_dir($vsCodeDir)) {
            mkdir($vsCodeDir, 0755, true);
        }

        // Read existing settings or create empty array
        $settings = [];
        if (file_exists($settingsPath)) {
            $content = file_get_contents($settingsPath);
            $settings = json_decode($content, true) ?? [];
        }

        // Initialize mcpServers if it doesn't exist
        if (!isset($settings['mcpServers'])) {
            $settings['mcpServers'] = [];
        }

        // Add the new MCP server configuration
        $serverConfig = ['command' => $command];
        if (!empty($args)) {
            $serverConfig['args'] = $args;
        }
        if (!empty($env)) {
            $serverConfig['env'] = $env;
        }

        $settings['mcpServers'][$key] = $serverConfig;

        // Write back to settings.json with pretty formatting
        $jsonContent = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        echo "\n✅ Automatically configured MCP server for VS Code\n";
        echo "   Added '{$key}' to .vscode/settings.json\n\n";

        return file_put_contents($settingsPath, $jsonContent) !== false;
    }

    /**
     * Display manual configuration instructions for JetBrains IDEs
     */
    private function displayJetBrainsInstructions(string $key, string $command, array $args = [], array $env = []): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "📋 JETBRAINS IDE CONFIGURATION REQUIRED\n";
        echo str_repeat('=', 80) . "\n";
        echo "Augment Code in JetBrains IDEs requires manual MCP server configuration.\n\n";
        echo "To configure Laravel Boost:\n";
        echo "1. Open your JetBrains IDE (PhpStorm, IntelliJ IDEA, WebStorm, etc.)\n";
        echo "2. Open the Augment Code settings panel (gear icon in Augment panel)\n";
        echo "3. Navigate to the MCP section\n";
        echo "4. Click the '+' button to add a new MCP server\n";
        echo "5. Configure the server with these details:\n";
        echo "   - Name: {$key}\n";
        echo "   - Command: {$command}\n";
        if (!empty($args)) {
            echo "   - Args: " . implode(' ', $args) . "\n";
        }
        if (!empty($env)) {
            echo "   - Environment variables:\n";
            foreach ($env as $envKey => $envValue) {
                echo "     {$envKey}={$envValue}\n";
            }
        }
        echo "\nFor detailed instructions, see:\n";
        echo "https://docs.augmentcode.com/jetbrains/setup-augment/mcp\n";
        echo str_repeat('=', 80) . "\n\n";
    }

    /**
     * Display manual configuration instructions for Vim/Neovim
     */
    private function displayVimInstructions(string $key, string $command, array $args = [], array $env = []): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "📋 VIM/NEOVIM CONFIGURATION REQUIRED\n";
        echo str_repeat('=', 80) . "\n";
        echo "Augment Code in Vim/Neovim requires manual MCP server configuration.\n\n";
        echo "To configure Laravel Boost:\n";
        echo "1. Open your Vim/Neovim configuration\n";
        echo "2. Configure the Augment Code plugin MCP settings\n";
        echo "3. Add the following MCP server configuration:\n";
        echo "   - Name: {$key}\n";
        echo "   - Command: {$command}\n";
        if (!empty($args)) {
            echo "   - Args: " . implode(' ', $args) . "\n";
        }
        if (!empty($env)) {
            echo "   - Environment variables:\n";
            foreach ($env as $envKey => $envValue) {
                echo "     {$envKey}={$envValue}\n";
            }
        }
        echo "\nFor detailed instructions, see:\n";
        echo "https://docs.augmentcode.com/vim/setup-augment/install-vim-neovim\n";
        echo str_repeat('=', 80) . "\n\n";
    }

    /**
     * Display general configuration instructions for all supported IDEs
     */
    private function displayGeneralInstructions(string $key, string $command, array $args = [], array $env = []): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "📋 AUGMENT CODE CONFIGURATION REQUIRED\n";
        echo str_repeat('=', 80) . "\n";
        echo "Augment Code supports multiple IDEs. Please configure MCP manually:\n\n";

        echo "🔧 MCP Server Details:\n";
        echo "   - Name: {$key}\n";
        echo "   - Command: {$command}\n";
        if (!empty($args)) {
            echo "   - Args: " . implode(' ', $args) . "\n";
        }
        if (!empty($env)) {
            echo "   - Environment variables:\n";
            foreach ($env as $envKey => $envValue) {
                echo "     {$envKey}={$envValue}\n";
            }
        }

        echo "\n📚 Configuration Instructions by IDE:\n\n";

        echo "VS Code:\n";
        echo "  1. Add to .vscode/settings.json under 'mcpServers' key\n";
        echo "  2. See: https://docs.augmentcode.com/setup-augment/mcp\n\n";

        echo "JetBrains IDEs (PhpStorm, IntelliJ, WebStorm, PyCharm):\n";
        echo "  1. Open Augment settings panel (gear icon)\n";
        echo "  2. Navigate to MCP section and add server\n";
        echo "  3. See: https://docs.augmentcode.com/jetbrains/setup-augment/mcp\n\n";

        echo "Vim/Neovim:\n";
        echo "  1. Configure through Augment plugin settings\n";
        echo "  2. See: https://docs.augmentcode.com/vim/setup-augment/install-vim-neovim\n\n";

        echo str_repeat('=', 80) . "\n\n";
    }
}
