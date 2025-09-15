<?php

declare(strict_types=1);

use Laravel\Boost\Install\CodeEnvironment\AugmentCode;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;

beforeEach(function () {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $this->augmentCode = new AugmentCode($this->strategyFactory);
});

afterEach(function () {
    Mockery::close();
});

test('installMcp automatically configures VS Code when .vscode directory exists', function () {
    $testDir = sys_get_temp_dir() . '/test-vscode-' . uniqid();
    $vsCodeDir = $testDir . '/.vscode';
    
    // Create test directory structure
    mkdir($vsCodeDir, 0755, true);
    
    // Change to test directory
    $originalCwd = getcwd();
    chdir($testDir);
    
    try {
        $result = $this->augmentCode->installMcp('test-server', 'test-command', ['arg1'], ['ENV_VAR' => 'value']);
        
        expect($result)->toBe(true);
        
        // Check that settings.json was created with correct content
        $settingsPath = $vsCodeDir . '/settings.json';
        expect(file_exists($settingsPath))->toBe(true);
        
        $settings = json_decode(file_get_contents($settingsPath), true);
        expect($settings)->toHaveKey('mcpServers');
        expect($settings['mcpServers'])->toHaveKey('test-server');
        expect($settings['mcpServers']['test-server'])->toBe([
            'command' => 'test-command',
            'args' => ['arg1'],
            'env' => ['ENV_VAR' => 'value']
        ]);
        
    } finally {
        // Restore original directory
        chdir($originalCwd);
        
        // Clean up test directory
        if (file_exists($vsCodeDir . '/settings.json')) {
            unlink($vsCodeDir . '/settings.json');
        }
        rmdir($vsCodeDir);
        rmdir($testDir);
    }
});

test('installMcp provides JetBrains instructions when .idea directory exists', function () {
    $testDir = sys_get_temp_dir() . '/test-jetbrains-' . uniqid();
    $ideaDir = $testDir . '/.idea';
    
    // Create test directory structure
    mkdir($ideaDir, 0755, true);
    
    // Change to test directory
    $originalCwd = getcwd();
    chdir($testDir);
    
    try {
        // Capture output
        ob_start();
        $result = $this->augmentCode->installMcp('test-server', 'test-command', ['arg1'], ['ENV_VAR' => 'value']);
        $output = ob_get_clean();
        
        expect($result)->toBe(true);
        expect($output)->toContain('JETBRAINS IDE CONFIGURATION REQUIRED');
        expect($output)->toContain('Name: test-server');
        expect($output)->toContain('Command: test-command');
        expect($output)->toContain('Args: arg1');
        expect($output)->toContain('ENV_VAR=value');
        expect($output)->toContain('https://docs.augmentcode.com/jetbrains/setup-augment/mcp');
        
    } finally {
        // Restore original directory
        chdir($originalCwd);
        
        // Clean up test directory
        rmdir($ideaDir);
        rmdir($testDir);
    }
});

test('installMcp provides Vim instructions when .nvimrc exists', function () {
    $testDir = sys_get_temp_dir() . '/test-vim-' . uniqid();
    
    // Create test directory structure
    mkdir($testDir, 0755, true);
    file_put_contents($testDir . '/.nvimrc', '');
    
    // Change to test directory
    $originalCwd = getcwd();
    chdir($testDir);
    
    try {
        // Capture output
        ob_start();
        $result = $this->augmentCode->installMcp('test-server', 'test-command');
        $output = ob_get_clean();
        
        expect($result)->toBe(true);
        expect($output)->toContain('VIM/NEOVIM CONFIGURATION REQUIRED');
        expect($output)->toContain('Name: test-server');
        expect($output)->toContain('Command: test-command');
        expect($output)->toContain('https://docs.augmentcode.com/vim/setup-augment/install-vim-neovim');
        
    } finally {
        // Restore original directory
        chdir($originalCwd);
        
        // Clean up test directory
        unlink($testDir . '/.nvimrc');
        rmdir($testDir);
    }
});

test('installMcp provides general instructions when no specific IDE detected', function () {
    $testDir = sys_get_temp_dir() . '/test-general-' . uniqid();
    
    // Create test directory structure (no IDE-specific files)
    mkdir($testDir, 0755, true);
    
    // Change to test directory
    $originalCwd = getcwd();
    chdir($testDir);
    
    try {
        // Capture output
        ob_start();
        $result = $this->augmentCode->installMcp('test-server', 'test-command');
        $output = ob_get_clean();
        
        expect($result)->toBe(true);
        expect($output)->toContain('AUGMENT CODE CONFIGURATION REQUIRED');
        expect($output)->toContain('VS Code:');
        expect($output)->toContain('JetBrains IDEs');
        expect($output)->toContain('Vim/Neovim:');
        expect($output)->toContain('Name: test-server');
        expect($output)->toContain('Command: test-command');
        
    } finally {
        // Restore original directory
        chdir($originalCwd);
        
        // Clean up test directory
        rmdir($testDir);
    }
});
