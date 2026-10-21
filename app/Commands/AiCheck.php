<?php

namespace App\Commands;

use App\Libraries\VetAssistant;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\AI;

/**
 * Checks the AI connection with one short question:  php spark ai:check
 * Shows which AI is used and whether it answered. The key itself is never printed.
 */
class AiCheck extends BaseCommand
{
    protected $group       = 'PawRecord';
    protected $name        = 'ai:check';
    protected $description = 'Asks the AI one short question to check the API key and the connection.';

    public function run(array $params)
    {
        $config = config(AI::class);

        CLI::write('Provider: ' . $config->provider . '   Model: ' . $config->model);

        if ($config->apiKey === '') {
            $name = $config->provider === 'gemini' ? 'GEMINI_API_KEY' : 'ANTHROPIC_API_KEY (or GEMINI_API_KEY)';
            CLI::error("No API key found. Put {$name} in .env (not env), then run this again.");
            CLI::write('Until then the app uses the offline glossary and rules.');

            return EXIT_ERROR;
        }

        CLI::write('API key: set (' . strlen($config->apiKey) . ' characters)');
        CLI::write('Asking: "What is otitis externa?" ...');

        $answer = (new VetAssistant($config))->chat([['sender' => 'user', 'content' => 'What is otitis externa? Answer in 2 sentences.']]);

        if ($answer['source'] !== 'ai') {
            CLI::error('The AI did not answer, so the offline glossary was used.');
            CLI::write('Open the newest file in writable/logs/ and look for "VetAssistant" to see why.');

            return EXIT_ERROR;
        }

        CLI::write(CLI::color('OK, the AI answered:', 'green'));
        CLI::write(mb_substr($answer['text'], 0, 400));

        return EXIT_SUCCESS;
    }
}
