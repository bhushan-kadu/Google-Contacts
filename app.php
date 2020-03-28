<?php
if (!empty($_POST)) {
  include_once 'vendor/autoload.php';
  include_once 'GoogleContacts.php';
  
  $googleSubjectAccount = 'mpscexam@updateindia.website';
  $defaultGroup = "http://www.google.com/m8/feeds/groups/$googleSubjectAccount/base/6";
  $conf = [
    'google_auth_file' => 'google_auth.json',
    'gcontacts_account' => $googleSubjectAccount,
    'gcontacts_base_url' => 'https://www.google.com/m8/feeds/contacts/default/full',
    'gcontacts_scopes' => [
      'https://www.googleapis.com/auth/contacts',
      'https://www.google.com/m8/feeds',
    ],
  ];
  $gcontacts = new \GoogleCustom\GoogleContacts($conf);
  $data = $_POST;
  if (empty($data['fullName'])) {
    $data['fullName'] = $data['namePrefix'] . ' ' . $data['givenName'] . ' ' . $data['additionalName'] . ' ' . $data['familyName'] . ' ' . $data['nameSuffix'];
  }
  $street = empty($data['street']) ? '' : $data['street'] . ', ';
  $neighborhood = empty($data['neighborhood']) ? '' : $data['neighborhood'] . ', ';
  $city = empty($data['city']) ? '' : $data['city'] . ', ';
  $postcode = empty($data['postcode']) ? '' : $data['postcode'] . ' ';
  $region = empty($data['region']) ? '' : $data['region'] . ', ';
  $data['formattedAddress'] = $street . $neighborhood . $city . $postcode . $region . $data['country'];
  $data['birthday'] = date('Y-m-d', strtotime($data['birthday']));
  $data['group'] = $defaultGroup;  
  // Escape the values to XML safe
  foreach ($data as $param => $value) {
    $data[$param] = htmlspecialchars($value, ENT_XML1, 'UTF-8');
  }
  if (isset($_POST['create'])) {
    $response  = $gcontacts->create($data);
    if (!$response) {
      echo '<p style="color: red;">Creation of Google Contacts entry failed.</p>';
    }
    else {
      echo '<p style="color: green;">Creation of Google Contacts entry successful.</p>';
    }
  }
  elseif (isset($_POST['query'])) {
    $response  = $gcontacts->query($_POST['term']);
    if (!$response) {
      echo '<p style="color: red;">Query of Google Contacts entry failed.</p>';
    }
    else {
      echo '<p style="color: green;">Query of Google Contacts entry successful. Below are the data returned from Google:</p>';
      print_r($response);
    }
  }
  elseif (isset($_POST['update'])) {
    $response  = $gcontacts->update($_POST['term'], $data);
    if (!$response) {
      echo '<p style="color: red;">Update of Google Contacts entry failed.</p>';
    }
    else {
      echo '<p style="color: green;">Update of Google Contacts entry successful. Below are the data returned from Google:</p>';
      print_r($response);
    }
  }
  elseif (isset($_POST['delete'])) {
    $response  = $gcontacts->delete($_POST['term']);
    if (!$response) {
      echo '<p style="color: red;">Deletion of Google Contacts entry failed.</p>';
    }
    else {
      echo '<p style="color: green;">Deletion of Google Contacts entry successful.</p>';
    }
  }
}
?>
<!DOCTYPE html>
<html>
  <body>
    <form action="app.php" method="post">
      <h3>Name:</h3>
      <div>
        <label for="namePrefix">Prefix:</label>
        <input type="text" name="namePrefix" value="Mr.">
      </div>
      <div>
        <label for="givenName">First:</label>
        <input type="text" name="givenName" value="John">
      </div>
      <div>
        <label for="additionalName">Middle:</label>
        <input type="text" name="additionalName" value="D.">
      </div>
      <div>
        <label for="familyName">Last:</label>
        <input type="text" name="familyName" value="Doe">
      </div>
      <div>
        <label for="nameSuffix">Suffix:</label>
        <input type="text" name="nameSuffix" value="Sr.">
      </div>
      <h3>Phone number:</h3>
      <div>
        <label for="phoneNumberHome">Home:</label>
        <input type="text" name="phoneNumberHome" value="+6328888888">
      </div>
      <div>
        <label for="phoneNumberMobile">Mobile:</label>
        <input type="text" name="phoneNumberMobile" value="+639198888888">
      </div>
      <div>
        <label for="phoneNumberWork">Work:</label>
        <input type="text" name="phoneNumberWork" value="+632777777">
      </div>
      <h3>Email:</h3>
      <div>
        <label for="emailHome">Home:</label>
        <input type="text" name="emailHome" value="john@yahoo.com">
      </div>
      <div>
        <label for="emailWork">Work:
        <input type="text" name="emailWork" value="john@drupal.org"></label>
      </div>
      <h3>Address:</h3>
      <div>
        <label for="street">Street:</label>
        <textarea rows="4" cols="50" name="street" style="display: block;">#101 Arca Drive</textarea>
      </div>
      <div>
        <label for="pobox">PO Box:</label>
        <input type="text" name="pobox" value="">
      </div>
      <div>
        <label for="neighborhood">Neighborhood:</label>
        <input type="text" name="neighborhood" value="">
      </div>
      <div>
        <label for="city">City:</label>
        <input type="text" name="city" value="Manila">
      </div>
      <div>
        <label for="region">State/Province:</label>
        <input type="text" name="region" value="NCR">
      </div>
      <div>
        <label for="postcode">ZIP/Postal Code:</label>
        <input type="text" name="postcode" value="1900">
      </div>
      <div>
        <label for="country">Country/Region:</label>
        <input type="text" name="country" value="Philippines">
      </div>
      <h3>Other:</h3>
      <div>
        <label for="birthday">Birthday:</label>
        <input type="text" name="birthday" value="January 1, 1970">
      </div>
      <div>
        <label for="mostConvenientTimeToCall">Most convenient time to call:</label>
        <input type="text" name="mostConvenientTimeToCall" value="after lunch">
      </div>
      <div>
        <label for="preferredProduct">Preferred product:</label>
        <input type="text" name="preferredProduct" value="Drupal 8 Module development ebook">
      </div>
      <h3>Note:</h3>
      <div>
         <textarea rows="4" cols="50" name="content">
Hi,

I would like to request more details about Drupal 8 Module development ebook.

Thanks.
         </textarea>
      </div>
      <input type="submit" name="create" value="Create">
      <div>
        <label for="term">Enter term(s) fulltext query on any contacts data fields separated by space (please populate this field for Update, Query and Delete):</label><br />
        <input type="text" name="term" value="john@yahoo.com Doe">
      </div>
      <input type="submit" name="query" value="Query"><br />
      <input type="submit" name="update" value="Update"><br />
      <input type="submit" name="delete" value="Delete"><br />
    </form>
  </body>
</html>