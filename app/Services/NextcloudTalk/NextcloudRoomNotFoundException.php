<?php

namespace App\Services\NextcloudTalk;

/**
 * Ein Talk-Raum ist für das technische Konto nicht (mehr) erreichbar – meist wurde er in
 * Nextcloud gelöscht oder das technische Konto aus dem Raum entfernt.
 */
class NextcloudRoomNotFoundException extends NextcloudTalkException {}
