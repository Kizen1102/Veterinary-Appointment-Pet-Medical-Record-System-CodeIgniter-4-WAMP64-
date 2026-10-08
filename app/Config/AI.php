<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Settings for the AI assistant. Values come from .env (never put a key in this file):
 *
 *   Claude (Anthropic):  ANTHROPIC_API_KEY = sk-ant-...   ANTHROPIC_MODEL = claude-opus-5-5
 *   Google Gemini:       GEMINI_API_KEY    = ...          GEMINI_MODEL    = gemini-flash-latest
 *                        (when it is busy, GEMINI_FALLBACK_MODEL = gemini-flash-lite-latest is tried)
 *
 * The provider is the one whose key is set (Claude first when both are set), or AI_PROVIDER = gemini / anthropic.
 * Without a key the app uses the built-in offline rules, glossary and templates.
 */
class AI extends BaseConfig
{
    /** 'anthropic' or 'gemini' */
    public string $provider = 'anthropic';

    /** The key of the chosen provider ('' = AI off, offline fallbacks only). */
    public string $apiKey  = '';
    public string $model   = 'claude-opus-5-5';
    public string $effort  = 'low';
    public float $timeout  = 45.0;
    public int $maxTokens  = 4000;

    /** Gemini only: tried when the main model is busy (503) or rate-limited (429). '' = no second model. */
    public string $fallbackModel = '';

    public function __construct()
    {
        parent::__construct();

        $anthropicKey = trim((string) (env('ANTHROPIC_API_KEY') ?? ''));
        $geminiKey    = trim((string) (env('GEMINI_API_KEY') ?? ''));

        $this->provider = strtolower((string) (env('AI_PROVIDER') ?: ($anthropicKey === '' && $geminiKey !== '' ? 'gemini' : 'anthropic')));

        if ($this->provider === 'gemini') {
            $this->apiKey        = $geminiKey;
            $this->model         = (string) (env('GEMINI_MODEL') ?: 'gemini-flash-latest');
            $this->fallbackModel = (string) (env('GEMINI_FALLBACK_MODEL') ?? 'gemini-flash-lite-latest');
        } else {
            $this->provider = 'anthropic';
            $this->apiKey   = $anthropicKey;
            $this->model    = (string) (env('ANTHROPIC_MODEL') ?: $this->model);
        }
    }
}
