# UniSender API wrapper

See the [documentation](https://support.unisender.com/index.php?/Knowledgebase/Article/View/49/0/obshaya-informaciya-pro-unisender-api#methods) available method.

# Usage

```lang=php
$unisender = new \SlowProg\UniSender\Api('api_key');

// Get a list of subscription sheets 
$lists = $unisender->getLists();

// Get email_status of email
$emailStatus = $unisender->exportContacts([
    'email' => $data['email'],
    'field_names' => ['email_status'],
]);

// Add email@domain.com
$response = $this->unisender->importContacts([
    'field_names' => ['email', 'some_addition_field'],
    'data' => [
        ['email@domain.com', 'some_value']
    ],
    'force_import' => 1
]);
```
