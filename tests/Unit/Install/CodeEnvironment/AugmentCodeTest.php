<?php

declare(strict_types=1);

namespace Tests\Unit\Install\CodeEnvironment;

use Illuminate\Support\Facades\File;
use Laravel\Boost\Install\CodeEnvironment\AugmentCode;
use Laravel\Boost\Install\Contracts\DetectionStrategy;
use Laravel\Boost\Install\Detection\DetectionStrategyFactory;
use Laravel\Boost\Install\Enums\Platform;
use Mockery;

beforeEach(function () {
    $this->strategyFactory = Mockery::mock(DetectionStrategyFactory::class);
    $this->strategy = Mockery::mock(DetectionStrategy::class);
    $this->augmentCode = new AugmentCode($this->strategyFactory);
});

afterEach(function () {
    Mockery::close();
});

test('name returns augmentcode', function () {
    expect($this->augmentCode->name())->toBe('augmentcode');
});

test('displayName returns Augment Code', function () {
    expect($this->augmentCode->displayName())->toBe('Augment Code');
});

test('agentName returns Augment Agent', function () {
    expect($this->augmentCode->agentName())->toBe('Augment Agent');
});

test('mcpClientName returns Augment Code', function () {
    expect($this->augmentCode->mcpClientName())->toBe('Augment Code');
});

test('mcpConfigPath returns .vscode/settings.json', function () {
    expect($this->augmentCode->mcpConfigPath())->toBe('.vscode/settings.json');
});

test('mcpConfigKey returns mcpServers', function () {
    expect($this->augmentCode->mcpConfigKey())->toBe('mcpServers');
});

test('systemDetectionConfig returns multiple IDE paths for Darwin', function () {
    $config = $this->augmentCode->systemDetectionConfig(Platform::Darwin);

    expect($config)->toBe([
        'paths' => [
            '/Applications/Visual Studio Code.app',
            '/Applications/PhpStorm.app',
            '/Applications/IntelliJ IDEA.app',
            '/Applications/WebStorm.app',
            '/Applications/PyCharm.app',
            '/usr/local/bin/nvim',
            '/opt/homebrew/bin/nvim',
        ],
    ]);
});

test('systemDetectionConfig returns multiple IDE detection command for Linux', function () {
    $config = $this->augmentCode->systemDetectionConfig(Platform::Linux);

    expect($config)->toBe([
        'command' => 'which code || which phpstorm || which idea || which webstorm || which pycharm || which nvim || which vim',
    ]);
});

test('systemDetectionConfig returns multiple IDE paths for Windows', function () {
    $config = $this->augmentCode->systemDetectionConfig(Platform::Windows);

    expect($config)->toBe([
        'paths' => [
            '%ProgramFiles%\\Microsoft VS Code',
            '%LOCALAPPDATA%\\Programs\\Microsoft VS Code',
            '%ProgramFiles%\\JetBrains\\PhpStorm*',
            '%ProgramFiles%\\JetBrains\\IntelliJ IDEA*',
            '%ProgramFiles%\\JetBrains\\WebStorm*',
            '%ProgramFiles%\\JetBrains\\PyCharm*',
            '%ProgramFiles%\\Neovim',
        ],
    ]);
});

test('projectDetectionConfig returns multiple IDE paths and files', function () {
    $config = $this->augmentCode->projectDetectionConfig();

    expect($config)->toBe([
        'paths' => ['.vscode', '.idea'],
        'files' => ['.vscode/settings.json', '.idea/workspace.xml', '.nvimrc', '.vimrc'],
    ]);
});



test('implements Agent interface', function () {
    expect($this->augmentCode)->toBeInstanceOf(\Laravel\Boost\Contracts\Agent::class);
});

test('implements McpClient interface', function () {
    expect($this->augmentCode)->toBeInstanceOf(\Laravel\Boost\Contracts\McpClient::class);
});
