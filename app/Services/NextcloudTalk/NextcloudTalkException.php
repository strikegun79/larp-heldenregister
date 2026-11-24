<?php

namespace App\Services\NextcloudTalk;

use RuntimeException;

/**
 * Issue #31: Fehler bei einem Nextcloud-Talk-Aufruf. Die Meldung ist deutsch, für die Anzeige
 * gedacht und enthält nie Zugangsdaten.
 */
class NextcloudTalkException extends RuntimeException {}
