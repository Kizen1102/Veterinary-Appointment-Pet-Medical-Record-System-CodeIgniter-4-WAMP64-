<?php

namespace App\Controllers;

use Throwable;

/**
 * Development-only page that confirms PawRecord is installed correctly:
 * database connection, PHP extensions, tables, views and migrations.
 */
class SystemCheck extends BaseController
{
    /** Tables and views the system needs, with what each one is for. */
    public const REQUIRED = [
        'users'                => 'Accounts (owner / vet / admin)',
        'pets'                 => 'Pet profiles',
        'appointments'         => 'Clinic visits',
        'medical_records'      => 'Health Timeline — visits',
        'vaccinations'         => 'Health Timeline — vaccines',
        'medications'          => 'Medication Tracker — prescriptions',
        'medication_schedules' => 'Medication Tracker — dose times',
        'medication_logs'      => 'Medication Tracker — dose history',
        'journal_entries'      => 'Symptom & Behavior Journal',
        'journal_ai_summaries' => 'Journal — AI summaries for the vet',
        'chat_conversations'   => 'AI Chatbot — conversations',
        'chat_messages'        => 'AI Chatbot — messages',
        'notifications'        => 'Alerts / bell icon',
        'pet_health_timeline'  => 'View: combined timeline',
        'medication_adherence' => 'View: adherence %',
    ];

    /** The two views above; everything else in REQUIRED is a real table with an "id" column. */
    public const VIEWS = ['pet_health_timeline', 'medication_adherence'];

    public function index()
    {
        $extensions = [];
        foreach (['intl', 'mbstring', 'mysqli', 'curl', 'openssl', 'json'] as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }

        $db = [
            'connected' => false,
            'error'     => null,
            'driver'    => null,
            'version'   => null,
            'database'  => null,
            'tables'    => [],
            'migrated'  => 0,
            'brokenIds' => [], // table => how many rows were saved with id 0
        ];

        try {
            $conn = db_connect();
            $conn->initialize();

            $db['connected'] = true;
            $db['driver']    = $conn->DBDriver;
            $db['version']   = $conn->getVersion();
            $db['database']  = $conn->getDatabase();

            $existing = $conn->listTables();
            foreach (self::REQUIRED as $table => $purpose) {
                $exists            = in_array($table, $existing, true);
                $db['tables'][]    = [
                    'name'    => $table,
                    'purpose' => $purpose,
                    'exists'  => $exists,
                    'rows'    => $exists ? $conn->table($table)->countAllResults() : null,
                ];
            }

            // A phpMyAdmin import stops at the views, so the tables keep no PRIMARY KEY and no
            // AUTO_INCREMENT and everything saved afterwards is given id 0. That breaks signing
            // in and every page that opens one record, with "Invalid primary key: '0'".
            foreach (array_keys(self::REQUIRED) as $table) {
                if (in_array($table, self::VIEWS, true) || ! in_array($table, $existing, true)) {
                    continue;
                }

                $zeros = $conn->table($table)->where('id', 0)->countAllResults();

                if ($zeros > 0) {
                    $db['brokenIds'][$table] = $zeros;
                }
            }

            if (in_array('migrations', $existing, true)) {
                $db['migrated'] = $conn->table('migrations')->countAllResults();
            }
        } catch (Throwable $e) {
            $db['error'] = $e->getMessage();
        }

        $tablesOk = $db['connected'] && ! in_array(false, array_column($db['tables'], 'exists'), true);
        $keysOk   = $db['brokenIds'] === [];

        return view('system_check', [
            'php'        => PHP_VERSION,
            'phpOk'      => version_compare(PHP_VERSION, '8.2.0', '>='),
            'extensions' => $extensions,
            'db'         => $db,
            'tablesOk'   => $tablesOk,
            'keysOk'     => $keysOk,
            'writable'   => is_really_writable(WRITEPATH),
            'allOk'      => $tablesOk && $keysOk && ! in_array(false, $extensions, true) && version_compare(PHP_VERSION, '8.2.0', '>='),
        ]);
    }
}
