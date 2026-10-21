<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * asset_url() puts the file's "last changed" time on the end of a stylesheet or script URL,
 * so a browser holding the previous copy fetches the new one by itself after an update.
 *
 * @internal
 */
final class AssetUrlTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
    }

    public function testAnExistingFileCarriesTheTimeItLastChanged(): void
    {
        $url = asset_url('assets/css/pawrecord.css');

        $this->assertStringContainsString('assets/css/pawrecord.css?v=', $url);
        $this->assertSame((string) filemtime(FCPATH . 'assets/css/pawrecord.css'), explode('?v=', $url)[1]);
    }

    public function testTheStampChangesWhenTheFileDoes(): void
    {
        $file = FCPATH . 'assets/.asset-url-test.css';
        file_put_contents($file, '/* one */');
        touch($file, 1_700_000_000);

        try {
            $this->assertStringEndsWith('?v=1700000000', asset_url('assets/.asset-url-test.css'));

            file_put_contents($file, '/* two */');
            touch($file, 1_800_000_000);
            clearstatcache(true, $file);

            $this->assertStringEndsWith('?v=1800000000', asset_url('assets/.asset-url-test.css'));
        } finally {
            unlink($file);
        }
    }

    public function testAMissingFileIsLeftAlone(): void
    {
        $this->assertStringEndsWith('assets/css/not-here.css', asset_url('assets/css/not-here.css'));
    }

    public function testEveryStylesheetAndScriptOfTheLayoutsIsStamped(): void
    {
        foreach (['app', 'auth', 'public'] as $layout) {
            $html = (string) file_get_contents(APPPATH . "Views/layouts/{$layout}.php");

            // Our own css/js go through asset_url; the CDN ones are untouched
            $this->assertDoesNotMatchRegularExpression(
                "#base_url\('assets/(css|js)/#",
                $html,
                "layouts/{$layout}.php still links one of our assets with base_url(); use asset_url().",
            );
        }
    }
}
