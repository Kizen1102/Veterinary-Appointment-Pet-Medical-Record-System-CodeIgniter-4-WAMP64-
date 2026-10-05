<?php

use App\Libraries\RuleBasedTriage;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RuleBasedTriageTest extends CIUnitTestCase
{
    public function testEmergencyKeywords(): void
    {
        $result = (new RuleBasedTriage())->assess('He ate a whole bar of CHOCOLATE and is shaking');

        $this->assertSame('emergency', $result['level']);
        $this->assertContains('chocolate', $result['red_flags']);
        $this->assertSame('rules', $result['source']);
    }

    public function testMostUrgentLevelWins(): void
    {
        // "vomit" is medium, but "vomiting blood" is high.
        $this->assertSame('high', (new RuleBasedTriage())->assess('vomiting blood since morning')['level']);
    }

    public function testRoutineVisitIsLow(): void
    {
        $this->assertSame('low', (new RuleBasedTriage())->assess('Annual check-up and booster shot')['level']);
    }

    public function testKeywordsOnlyMatchAtTheStartOfAWord(): void
    {
        $triage = new RuleBasedTriage();

        $this->assertSame('low', $triage->assess('Yearly check-up')['level']);         // "ear" is inside "yearly"
        $this->assertSame('medium', $triage->assess('Scratching his ears')['level']);  // "ear" starts "ears"
        $this->assertSame('medium', $triage->assess('Vomiting since morning')['level']);
    }
}
