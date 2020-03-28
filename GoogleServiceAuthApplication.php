<?php

namespace GoogleCustom;

use Google_Client;

class GoogleServiceAuthApplication {
  /**
   * Google client.
   *
   * @var array
   */
  protected $client;

  public function __construct($conf) {
    if (isset($conf['google_auth_file'])) {
      putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $conf['google_auth_file']);
    }
    $this->client = new Google_Client();
    $this->client->useApplicationDefaultCredentials();
    $this->client->setScopes($conf['gcontacts_scopes']);
    $this->client->setSubject($conf['gcontacts_account']);
  }

  /**
   * @return array
   */
  public function getClient() {
    return $this->client;
  }
}
      