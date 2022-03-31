<?php

namespace Drupal\irismedia_core\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;
use Drupal\irismedia_core\csvParser;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;

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
   * @param $year
   * @command irismedia_core:setmore
   * @aliases setmore
   */

  public function haldleSetmore($year) {
    $this->logger()->success(dt('Achievement unlocked.'));

    $module_path = \Drupal::service('file_system')->realpath(\Drupal::service('module_handler')->getModule('irismedia_core')->getPath());
    $url = $module_path."/files/".$year.".csv";
    $row = 1;
    if (($handle = fopen($url, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

            $num = count($data);
            if ($row == 1) {
              // this is header
              for ($c=0; $c < $num; $c++) {
                $header[$c] = str_replace(array('/',' '),array('_','_'),$data[$c]);
                $row++;
              }
              $apt_data['header'] = $header;
            }
            else {
              for ($c=0; $c < $num; $c++) {
                $row_data[$c] = $data[$c];
                $row++;
              }
              $apt_data['data'][] = $row_data;
            }
        }
        fclose($handle);
    }
    // Start importing data
    $this->parseData($apt_data);
  }

  private function parseData($row, $header=false) {
    $i=0;
    foreach($row['data'] as $item) {
      if ($i > 100) {
        // return;
      }
      if (empty($item[7])) {
        print_r("\n".$i." Skipping Booking :".$item[18]);
        continue;
      }
      // Service info
      $service_title = $item[2];
      $service_query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
      $service_query->condition('title', $service_title);
      $service_query->condition('type','services','=');
      $service_nid = $service_query->execute();
      $service_nid = reset($service_nid);
      if (empty($service_nid)) {
        // print_r("\n New service :".$service_title."\n");
        $service_node = Node::create([
          'type' => 'services',
          'title' => $service_title,
          'field_price' => $item[3],
        ]);
        $service_node->save();
        $service_nid = $service_node->id();
      }

      // Staff info
      $staff_title = $item[4];
      $staff_query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
      $staff_query->condition('title', $staff_title);
      $staff_query->condition('type','staff','=');
      $staff_nid = $staff_query->execute();
      $staff_nid = reset($staff_nid);
      if (empty($staff_nid)) {
        // print_r("\n New Staff :".$staff_title."\n");
        $staff_node = Node::create([
          'type' => 'staff',
          'title' => $staff_title,
        ]);
        $staff_node->save();
        $staff_nid = $staff_node->id();
      }

      // Create user
      $name = $item[7]; // phone number as username
      $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $name]);
      $user = reset($users);
      if (!empty($users) && !empty($user->id())) {
        $customer_id = $user->id();
        // print_r("\n Existing Customer ".$customer_id."\n");
      } else {
        // print_r("\n Adding New Customer ".$name."\n");
        $user = User::create();
        $user->setUsername($name); // This username must be unique and accept only [a-Z,0-9, - _ @].
        $user->setPassword('test');
        $user->setEmail($item[8]);
        $fullname = explode(" ",$item[5]);
        $user->set("field_first_name", $fullname[0]);
        $user->set("field_last_name", empty($fullname[1]) ? '': $fullname[1]);
        $user->set("field_phone", $name);
        $user->set("field_zip", $item[15]);
        $user->addRole('customer'); // E.g: authenticated.
        $user->enforceIsNew();
        $user->activate();
        $user->save();
        $customer_id = $user->id();  
      }

      // Appointment info
      $booking_id = $item[18];
      $guid_id = md5($item[18]."-".strtotime($start_time));
      $booking_query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
      $booking_query->condition('field_id', $booking_id);
      $booking_query->condition('type','booking','=');
      $booking_nid = $booking_query->execute();
      $booking_nid = reset($booking_nid);

      if (empty($booking_nid)) {
        print_r("\n".$i." Creating New Booking :".$booking_id);
        $time_diff = explode("-",$item[1]);
        $start_time = $item[0].' '.$time_diff[0]; //"Mar 05 2022 9:00 AM";
        $end_time = $item[0].' '.$time_diff[1]; //"Mar 05 2022 9:00 AM";

        $booking_fields = [
          'type' => 'booking',
          'title' => $item[0].' at '. $item[1],
          'field_service' => ['target_id' => $service_nid],
          'field_customer' => ['target_id' => $customer_id],
          'field_date' => ['value' => date("Y-m-d\TH:i:s",strtotime($start_time)), 'end_value' =>  date("Y-m-d\TH:i:s",strtotime($end_time))],
          'field_staff' => ['target_id' => $staff_nid],
          'field_year' => ['target_id' => $this->getLabelTid(date("Y",strtotime($start_time)),'year')],
          'field_month' => ['target_id' => $this->getLabelTid(date("F",strtotime($start_time)),'month')],
          'field_day' => ['target_id' => $this->getLabelTid(date("d",strtotime($start_time)),'day')],
          'field_label' => ['target_id' => $this->getLabelTid($item[13],'label')],
          'field_promo_code' => $item[14],
          'field_status' => $item[16],
          'field_comments' => $item[17],
          'field_id' => $booking_id,
          'field_booking_from' => $item[19],
          'field_guid' => $guid,
        ];

        // print_r($item);
        // print_r($booking_fields);

        $booking_node = Node::create($booking_fields);
        $booking_node->save();
        $booking_nid = $booking_node->id();
      // } else {
        // print_r("\n".$i." Booking ID:".$booking_id." already exist");
        // $label = ['target_id' => $this->getLabelTid($item[13],'label')];
        // $booking_node = Node::load($booking_nid);
        // $booking_node->set('field_label',$label);
        // $booking_node->save();
        // $booking_nid = $booking_node->id();
      // }
    $i++;
    }
  }

  

  function getLabelTid($term_name,$vocabulary_name) {
    if (!empty($term_name)) {

      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term_name, 'vid' => $vocabulary_name]);
      if (empty($term)) {
        $term = Term::create([
          'name' => $term_name,
          'vid' => $vocabulary_name,
        ]);
        $term->save();
        $tid = $term->id();
      } else {
        $term = reset($term);
        $tid = $term->id();
      }

    } else {
      $tid = NULL;
    }
    return $tid;
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
  
  // $csv = new csvParser();
  print_r("Hello world");



  // $token = "r1/2d1a70c2dbt_YT_NUL5AubdQkYCj001c3GVOLfZtfUC7v";
  // $ntoken = "https://developer.setmore.com/api/v1/o/oauth2/token?refreshToken={$token}";
  // $this->logger()->success(dt($ntoken));

  // $client = \Drupal::httpClient();

  // try {
  //   $response = $client->get($ntoken);
  //   $data = json_decode($response->getBody());

  // }
  // catch (RequestException $e) {
  //   watchdog_exception('my_module', $e->getMessage());
  // }
  // $access_token = $data->data->token->access_token;
  // $headers = json_encode(array('Content-Type' => 'application/json', 'Authorization'=> "Bearer $access_token" ));

  // print_r($headers);

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
