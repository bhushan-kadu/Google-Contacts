<?php

namespace GoogleCustom;

include_once 'GoogleServiceAuthApplication.php';


class GoogleContacts {
  /**
   * Request object.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  protected $baseUrl;

  protected $etag;

  protected $userId;

  protected $xmlString;

  protected $xmlData;

  protected $conf;

  public function __construct($conf) {
    $this->conf = $conf;
    $this->baseUrl = $conf['gcontacts_base_url'];
    $auth = new GoogleServiceAuthApplication($conf);
    $client = $auth->getClient();
    $this->httpClient = $client->authorize();
  }

  /**
   * Create new Google Contacts entry
   * 
   * @param array $data
   *   Contains flat array of new details to update a Google Contacts entry.
   * @return bool
   *   Flat array Google Contacts entry details if successful and FALSE if failed.
   */
  public function create($data) {
    extract($data);
    $xmlString = <<<XML
  <atom:entry xmlns:atom="http://www.w3.org/2005/Atom" xmlns:gd="http://schemas.google.com/g/2005">
    <atom:category scheme="http://schemas.google.com/g/2005#kind" term="http://schemas.google.com/contact/2008#contact"/>
    <gd:name>
      <gd:fullName>$fullName</gd:fullName>
      <gd:namePrefix>$namePrefix</gd:namePrefix>
      <gd:givenName>$givenName</gd:givenName>
      <gd:additionalName>$additionalName</gd:additionalName>
      <gd:familyName>$familyName</gd:familyName>
      <gd:nameSuffix>$nameSuffix</gd:nameSuffix>
    </gd:name>
    <gd:birthday when="$birthday"/>
    <atom:content type="text">$content</atom:content>
    <gd:email rel="http://schemas.google.com/g/2005#home" address="$emailHome" />
    <gd:email rel="http://schemas.google.com/g/2005#work" primary="true" address="$emailWork" />
    <gd:phoneNumber rel="http://schemas.google.com/g/2005#home">$phoneNumberHome</gd:phoneNumber>
    <gd:phoneNumber rel="http://schemas.google.com/g/2005#mobile">$phoneNumberMobile</gd:phoneNumber>
    <gd:phoneNumber rel="http://schemas.google.com/g/2005#work">$phoneNumberWork</gd:phoneNumber>
    <gd:structuredPostalAddress rel="http://schemas.google.com/g/2005#home" primary="true">
      <gd:formattedAddress>$formattedAddress</gd:formattedAddress>
      <gd:street>$street</gd:street>
      <gd:pobox>$pobox</gd:pobox>
      <gd:neighborhood>$neighborhood</gd:neighborhood>
      <gd:city>$city</gd:city>
      <gd:region>$region</gd:region>
      <gd:postcode>$postcode</gd:postcode>
      <gd:country>$country</gd:country>
    </gd:structuredPostalAddress>
    <gd:userDefinedField key="Most convenient time to call" value="$mostConvenientTimeToCall"/>
    <gd:userDefinedField key="Preferred product" value="$preferredProduct"/>
    <gd:groupMembershipInfo deleted="false" href="$group"/>
  </atom:entry>
XML;
    $options = [
      'headers' => [
        'Content-Type' => 'application/atom+xml',
        'GData-Version' => '3.0',
      ],
      'body' => $xmlString,
    ];
    $response = $this->httpClient->post($this->baseUrl, $options);
    if ($response->getReasonPhrase() == 'Created' && $response->getStatusCode() == 201) {
      // Successful
      $xmlString = $response->getBody()->getContents();
      $data = new \SimpleXMLElement($xmlString);
      $this->xmlString = $xmlString;
      $this->xmlData = $data;
      return $this->flatData($data);
    }
    else {
      // Failed
      echo $response->getBody()->getContents();
      return FALSE;
    }
  }

  /**
   * Update a Google Contacts entry
   * 
   * @param sting $query
   *   Fulltext query on contacts data fields.
   * @param array $data
   *   Contains flat array of new details to update a Google Contacts entry.
   * @return mixed
   *   Flat array Google Contacts entry details if successful and FALSE if failed.
   */
  public function update($query, $data) {
    $this->query($query);
    if (!empty($data['content'])) {
      $this->xmlData->content = $data['content'];
    }
    if (!empty($data['fullName'])) {
      $this->xmlData->children('gd', TRUE)->name->children('gd', TRUE)->fullName = $data['fullName'];
    }
    if (!empty($data['namePrefix'])) {
      $this->xmlData->children('gd', TRUE)->name->children('gd', TRUE)->namePrefix = $data['namePrefix'];
    }
    if (!empty($data['givenName'])) {
      $this->xmlData->children('gd', TRUE)->name->children('gd', TRUE)->givenName = $data['givenName'];
    }
    if (!empty($data['additionalName'])) {
      $this->xmlData->children('gd', TRUE)->name->children('gd', TRUE)->additionalName = $data['additionalName'];
    }
    if (!empty($data['familyName'])) {
      $this->xmlData->children('gd', TRUE)->name->children('gd', TRUE)->familyName = $data['familyName'];
    }
    if (!empty($data['nameSuffix'])) {
      $this->xmlData->children('gd', TRUE)->name->children('gd', TRUE)->nameSuffix = $data['nameSuffix'];
    }
    if (!empty($data['birthday'])) {
      $this->xmlData->children('gContact', TRUE)->birthday->attributes()->when = $data['birthday'];
    }
    $index = 0;
    foreach ($this->xmlData->children('gd', TRUE)->email as $email) {
      $rel = (string) $email->attributes()->rel;
      if (!empty($data['emailWork']) && strstr($rel, 'work') !== FALSE) {
        $this->xmlData->children('gd', TRUE)->email[$index]->attributes()->address = $data['emailWork'];
      }
      elseif (!empty($data['emailHome']) && strstr($rel, 'home') !== FALSE) {
        $this->xmlData->children('gd', TRUE)->email[$index]->attributes()->address = $data['emailHome'];
      }
      ++$index;
    }
    $index = 0;
    foreach ($this->xmlData->children('gd', TRUE)->phoneNumber as $phoneNumber) {
      $rel = (string) $phoneNumber->attributes()->rel;
      if (!empty($data['phoneNumberMobile']) && strstr($rel, 'mobile') !== FALSE) {
        $this->xmlData->children('gd', TRUE)->phoneNumber[$index] = $data['phoneNumberMobile'];
      }
      elseif (!empty($data['phoneNumberWork']) && strstr($rel, 'work') !== FALSE) {
        $this->xmlData->children('gd', TRUE)->phoneNumber[$index] = $data['phoneNumberWork'];
      }
      elseif (!empty($data['phoneNumberHome']) && strstr($rel, 'home') !== FALSE) {
        $this->xmlData->children('gd', TRUE)->phoneNumber[$index] = $data['phoneNumberHome'];
      }
      ++$index;
    }
    if (!empty($data['formattedAddress'])) {
      $this->xmlData->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->formattedAddress = $data['formattedAddress'];    
    }
    if (!empty($data['street'])) {
      $this->xmlData->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->street = $data['street'];    
    }
    if (!empty($data['pobox'])) {
      $this->xmlData->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->pobox = $data['pobox'];    
    }
    if (!empty($data['neighborhood'])) {
      $this->xmlData->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->neighborhood = $data['neighborhood'];    
    }
    if (!empty($data['city'])) {
      $this->xmlData->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->city = $data['city'];    
    }
    if (!empty($data['region'])) {
      $this->xmlData->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->region = $data['region'];    
    }
    if (!empty($data['postcode'])) {
      $this->xmlData->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->postcode = $data['postcode'];    
    }
    if (!empty($data['country'])) {
      $this->xmlData->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->country = $data['country'];
    }
    $index = 0;
    foreach ($this->xmlData->children('gContact', TRUE)->userDefinedField as $userDefinedField) {
      $key = (string) $userDefinedField->attributes()->key;
      if (!empty($data['preferredProduct']) && $key == 'Preferred product') {
        $this->xmlData->children('gContact', TRUE)->userDefinedField[$index]->attributes()->value = $data['preferredProduct'];
      }
      elseif (!empty($data['mostConvenientTimeToCall']) && $key == 'Most convenient time to call') {
        $this->xmlData->children('gContact', TRUE)->userDefinedField[$index]->attributes()->value = $data['mostConvenientTimeToCall'];
      }
      ++$index;
    }
    $this->xmlData->children('gContact', TRUE)->groupMembershipInfo->attributes()->href = $data['group'];
    $options = [
      'headers' => [
        'If-Match' => $this->etag,
        'Content-Type' => 'application/atom+xml',
        'GData-Version' => '3.0',
      ],
      'body' => $this->xmlData->asXML(),
    ];
    $response = $this->httpClient->put("$this->baseUrl/$this->userId", $options);
    if ($response->getReasonPhrase() == 'OK' && $response->getStatusCode() == 200) {
      // Successful
      $xmlString = $response->getBody()->getContents();
      $data = new \SimpleXMLElement($xmlString);
      $this->xmlString = $xmlString;
      $this->xmlData = $data;
      return $this->flatData($data);
    }
    else {
      // Failed
      echo $response->getBody()->getContents();
      return FALSE;
    }
  }

  protected function requestContact() {
    $targetUrl = "$this->baseUrl/$this->userId?v=3.0";
    $response  = $this->httpClient->get($targetUrl);
    $xmlString = $response->getBody()->getContents();
    $data = new \SimpleXMLElement($xmlString);
    $this->etag = (string) $data->attributes('gd', TRUE)->etag;
    $this->xmlData = $data;
    $this->xmlString = $xmlString;
    return $this->flatData($data);
  }

  /**
   * Query Google Contacts
   * 
   * @param sting $query
   *   Fulltext query on contacts data fields.
   * @return mixed
   *   Flat array Google Contacts entry details if successful and FALSE if failed.
   */
  public function query($query) {
    if (!empty($query)) {
      $response   = $this->httpClient->get("$this->baseUrl?v=3.0&q=$query");
      $xmlString  = $response->getBody()->getContents();
      if ($response->getReasonPhrase() == 'OK' && $response->getStatusCode() == 200) {
        $data = new \SimpleXMLElement($xmlString);
        if (isset($data->entry[0]->id)) {
          $this->userId = basename((string) $data->entry[0]->id);
          return $this->requestContact();
        }
      }
    }
    return FALSE;
  }

  /**
   * Delete a Google Contacts entry
   * 
   * @param sting $query
   *   Fulltext query on contacts data fields.
   * @return mixed
   *   TRUE if successful and FALSE if failed.
   */
  public function delete($query) {
    if (!empty($query)) {
      $this->query($query);
    }
    if (!empty($this->etag)) {
      $options = [
        'headers' => [
          'If-Match' => $this->etag,
        ],
      ];
      $response =  $this->httpClient->delete("$this->baseUrl/$this->userId", $options);
      if ($response->getReasonPhrase() == 'Precondition Failed' && $response->getStatusCode() == 412) {
        $options = [
          'headers' => [
            'If-Match' => $this->etag,
            'X-HTTP-Method-Override' => 'DELETE',
          ],
        ];
        $response =  $this->httpClient->post("$this->baseUrl/$this->userId", $options);
      }
      if ($response->getReasonPhrase() == 'Precondition Failed' && $response->getStatusCode() == 412) {
        $options = [
          'headers' => [
            'If-Match' => '*',
            'X-HTTP-Method-Override' => 'DELETE',
          ],
        ];
        $response =  $this->httpClient->post("$this->baseUrl/$this->userId", $options);
      }
      if ($response->getReasonPhrase() == 'OK' && $response->getStatusCode() == 200) {
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function flatData($data) {
    $flatData = [];
    $flatData['userId'] = basename((string) $data->id);
    $flatData['etag'] = (string) $data->attributes('gd', TRUE)->etag;
    // Convert data to flat array
    if (isset($data->content)) {
      $flatData['content'] = (string) $data->content;
    }
    if (isset($data->children('gd', TRUE)->name->children('gd', TRUE)->fullName)) {
      $flatData['fullName'] = (string) $data->children('gd', TRUE)->name->children('gd', TRUE)->fullName;
    }
    if (isset($data->children('gd', TRUE)->name->children('gd', TRUE)->namePrefix)) {
      $flatData['namePrefix'] = (string) $data->children('gd', TRUE)->name->children('gd', TRUE)->namePrefix;
    }
    if (isset($data->children('gd', TRUE)->name->children('gd', TRUE)->givenName)) {
      $flatData['givenName'] = (string) $data->children('gd', TRUE)->name->children('gd', TRUE)->givenName;
    }
    if (isset($data->children('gd', TRUE)->name->children('gd', TRUE)->additionalName)) {
      $flatData['additionalName'] = (string) $data->children('gd', TRUE)->name->children('gd', TRUE)->additionalName;
    }
    if (isset($data->children('gd', TRUE)->name->children('gd', TRUE)->familyName)) {
      $flatData['familyName'] = (string) $data->children('gd', TRUE)->name->children('gd', TRUE)->familyName;
    }
    if (isset($data->children('gd', TRUE)->name->children('gd', TRUE)->nameSuffix)) {
      $flatData['nameSuffix'] = (string) $data->children('gd', TRUE)->name->children('gd', TRUE)->nameSuffix;
    }
    if (isset($data->children('gContact', TRUE)->birthday)) {
      $flatData['birthday'] = (string) $data->children('gContact', TRUE)->birthday;
    }
    if (isset($data->children('gd', TRUE)->email)) {
      $index = 0;
      foreach ($data->children('gd', TRUE)->email as $email) {
        $rel = (string) $email->attributes()->rel;
        if (strstr($rel, 'work') !== FALSE && isset($data->children('gd', TRUE)->email[$index]->attributes()->address)) {
          $flatData['emailWork'] = (string) $data->children('gd', TRUE)->email[$index]->attributes()->address;
        }
        elseif (strstr($rel, 'home') !== FALSE && isset($data->children('gd', TRUE)->email[$index]->attributes()->address)) {
          $flatData['emailHome'] = (string) $data->children('gd', TRUE)->email[$index]->attributes()->address;
        }
        ++$index;
      }
    }
    if (isset($data->children('gd', TRUE)->phoneNumber)) {
      $index = 0;
      foreach ($data->children('gd', TRUE)->phoneNumber as $phoneNumber) {
        $rel = (string) $phoneNumber->attributes()->rel;
        if (strstr($rel, 'mobile') !== FALSE && isset($data->children('gd', TRUE)->phoneNumber[$index])) {
          $flatData['phoneNumberMobile'] = (string) $data->children('gd', TRUE)->phoneNumber[$index];
        }
        elseif (strstr($rel, 'work') !== FALSE && isset($data->children('gd', TRUE)->phoneNumber[$index])) {
          $flatData['phoneNumberWork'] = (string) $data->children('gd', TRUE)->phoneNumber[$index];
        }
        elseif (strstr($rel, 'home') !== FALSE && isset($data->children('gd', TRUE)->phoneNumber[$index])) {
          $flatData['phoneNumberHome'] = (string) $data->children('gd', TRUE)->phoneNumber[$index];
        }
        ++$index;
      }
    }
    if (isset($data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->formattedAddress)) {
      $flatData['formattedAddress'] = (string) $data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->formattedAddress;
    }
    if (isset($data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->street)) {
      $flatData['street'] = (string) $data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->street;
    }
    if (isset($data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->pobox)) {
      $flatData['pobox'] = (string) $data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->pobox;
    }
    if (isset($data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->neighborhood)) {
      $flatData['neighborhood'] = (string) $data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->neighborhood;
    }
    if (isset($data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->city)) {
      $flatData['city'] = (string) $data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->city;
    }
    if (isset($data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->region)) {
      $flatData['region'] = (string) $data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->region;
    }
    if (isset($data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->postcode)) {
      $flatData['postcode'] = (string) $data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->postcode;
    }
    if (isset($data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->country)) {
      $flatData['country'] = (string) $data->children('gd', TRUE)->structuredPostalAddress->children('gd', TRUE)->country;
    }
    if (isset($data->children('gContact', TRUE)->userDefinedField)) {
      $index = 0;
      foreach ($data->children('gContact', TRUE)->userDefinedField as $userDefinedField) {
        $key = (string) $userDefinedField->attributes()->key;
        if ($key == 'Preferred product' && isset($data->children('gContact', TRUE)->userDefinedField[$index]->attributes()->value)) {
          $flatData['preferredProduct'] = (string) $data->children('gContact', TRUE)->userDefinedField[$index]->attributes()->value;
        }
        elseif ($key == 'Time Zone') {
          $flatData['timeZone'] = (string) $data->children('gContact', TRUE)->userDefinedField[$index]->attributes()->value;
        }
        elseif ($key == 'Most convenient time to call' && isset($data->children('gContact', TRUE)->userDefinedField[$index]->attributes()->value)) {
          $flatData['mostConvenientTimeToCall'] = (string) $data->children('gContact', TRUE)->userDefinedField[$index]->attributes()->value;
        }
        ++$index;
      }
    }
    if (isset($data->children('gContact', TRUE)->groupMembershipInfo->attributes()->href)) {
      $flatData['group'] = (string) $data->children('gContact', TRUE)->groupMembershipInfo->attributes()->href;
    }
    $flatData['timestamp'] = time();
    return $flatData;
  }
}