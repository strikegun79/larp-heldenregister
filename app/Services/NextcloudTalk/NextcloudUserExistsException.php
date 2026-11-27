<?php

namespace App\Services\NextcloudTalk;

/** Beim Anlegen eines Kontos: den Benutzernamen gibt es in Nextcloud schon (OCS 102). */
class NextcloudUserExistsException extends NextcloudTalkException {}
