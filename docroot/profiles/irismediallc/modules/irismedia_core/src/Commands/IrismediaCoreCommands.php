<?php

namespace Drupal\irismedia_core\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class IrismediaCoreCommands extends DrushCommands {

  /**
   * Command description here.
   *
   * @param $arg1
   *   Argument description.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   * @option option-name
   *   Description
   * @usage irismedia_core-commandName foo
   *   Usage description
   *
   * @command irismedia_core:commandName
   * @aliases foo
   */
  public function commandName($arg1, $options = ['option-name' => 'default']) {
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Import data from setmore
   * @param $arg1
   * @command irismedia_core:setmore
   * @aliases setmore
   */

  public function haldleSetmore($arg1) {
    $this->logger()->success(dt('Achievement unlocked.'));
    $this->getSetmore();
  }

  /**
   * An example of the table output format.
   *
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @field-labels
   *   group: Group
   *   token: Token
   *   name: Name
   * @default-fields group,token,name
   *
   * @command irismedia_core:token
   * @aliases token
   *
   * @filter-default-field name
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function token($options = ['format' => 'table']) {
    $all = \Drupal::token()->getInfo();
    foreach ($all['tokens'] as $group => $tokens) {
      foreach ($tokens as $key => $token) {
        $rows[] = [
          'group' => $group,
          'token' => $key,
          'name' => $token['name'],
        ];
      }
    }
    return new RowsOfFields($rows);
  }

public function getSetmore() {
  
  $token = "r1/2d1a70c2dbt_YT_NUL5AubdQkYCj001c3GVOLfZtfUC7v";
  $ntoken = "https://developer.setmore.com/api/v1/o/oauth2/token?refreshToken={$token}";
  $this->logger()->success(dt($ntoken));

  $client = \Drupal::httpClient();

  try {
    $response = $client->get($ntoken);
    $data = json_decode($response->getBody());

  }
  catch (RequestException $e) {
    watchdog_exception('my_module', $e->getMessage());
  }
  $access_token = $data->data->token->access_token;
  $headers = json_encode(array('Content-Type' => 'application/json', 'Authorization'=> "Bearer $access_token" ));

  print_r($headers);

  // $s_key = 's75863c9f97a6f3f5ce830a88c87b198e6492ad14'
  // s_url ='https://developer.setmore.com/api/v1/bookingapi/services'
  # print(headers)
  // services = requests.get(s_url, headers=headers).json()
  # print(services)

  // $headers = {'Content-Type': 'application/json', 'Authorization': 'Bearer $access_token'};

  // $headers = {'Content-Type': 'application/json', 'Authorization': 'Bearer $access_token'};



  /*
  print(ntoken)
  response = requests.get(ntoken)
  atoken = response.json()['data']['token']['access_token']
  print(atoken)

  headers = {'Content-Type': 'application/json', 'Authorization': 'Bearer %s'%atoken}

  print(headers)

  # headers = {'Content-Type': 'application/json', 'Authorization': 'Bearer %s'%atoken}
s_key = 's75863c9f97a6f3f5ce830a88c87b198e6492ad14'
s_url ='https://developer.setmore.com/api/v1/bookingapi/services'
# print(headers)
services = requests.get(s_url, headers=headers).json()
# print(services)
print("\r")
s_list = services['data']['services']
# print(s_list)

dict = {}
for key in s_list:
    dict[key['key']] = key['service_name']
#     dict['key4'] = 'is'
print("\r")
print("\r")
print(dict)
print("\r")
print("\r")

surl ='https://developer.setmore.com/api/v1/bookingapi/appointments?startDate=04-01-2022&endDate=04-01-2022&customerDetails=true'
content_res = requests.get(surl, headers=headers).json()
print(content_res)

appts = content_res['data']['appointments']

#print(content_res['data']['appointments'])

for key in appts:
    print("\n")
#     print (key)
    if 'label' in key:
        print(key['label'])

    if 'cost' in key:
        print(key['cost'])

    if 'start_time' in key:
        print(key['start_time'])
        
#     print("\n")
    service_key = key['service_key']
    print('Service key new', dict[service_key])
    
#     print("\n")
#     customer_key = key['customer_key']
#     print('Customer key', customer_key)
    
    for key in key:
        customer_info = appt[key]
        print(key, '->', appt[key])
        if key == "customer":
            print("Niroj--",type(customer_info))
            print(customer_info)
            for key in customer_info:
                print(key,customer_info[key])
                */
}

  // public function
}