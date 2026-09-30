<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Settings for the AI assistant (Claude). Values come from .env:
 *   ANTHROPIC_API_KEY = sk-ant-...
 *   ANTHROPIC_MODEL   = claude-opus-5-5
 * Without an API key the app falls back to built-in rule-based triage.
 */
class AI extends BaseConfig
{
    public string $apiKey  = '';
    public string $model   = 'claude-opus-5-5';
    public string $effort  = 'low';
    public float $timeout  = 45.0;
    public int $maxTokens  = 4000;

    public function __construct()
    {
        parent::__construct();

        $this->apiKey = (string) (env('ANTHROPIC_API_KEY') ?? '');
        $this->model  = (string) (env('ANTHROPIC_MODEL') ?: $this->model);
    }
}
