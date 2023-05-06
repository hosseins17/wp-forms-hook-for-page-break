<?php
/*
Plugin Name:  page-break-form
Description:  connect form, excel file and db
Version:      1.0
Author:       hossein shahidi
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Domain Path:  /
*/

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
  register_activation_hook(__FILE__, 'amm_activation_function');

  // callback function to create table
  function amm_activation_function()
  {
      global $wpdb;

      if ($wpdb->get_var("show tables like '" . amm_create_my_table() . "'") != amm_create_my_table()) {

          $mytable = 'CREATE TABLE `' . amm_create_my_table() . '` (
            `id` int(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `code` int(255) UNSIGNED NOT NULL,
            `fname` VARCHAR(30) NOT NULL,
            `lname` VARCHAR(30) NOT NULL,
            `phoneNumber` varchar(11) NOT NULL,
            `verifyCode` int(6),
            `isValid` BOOLEAN NOT NULL default 0)
             ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;';

          require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
          dbDelta($mytable);
      }
  }

  function amm_create_my_table()
  {
      global $wpdb;
      return $wpdb->prefix . "my-form-users";
  }

  function amm_remove_database() {
      global $wpdb;
      $table_name = $wpdb->prefix . "my-form-users";
      $sql = "DROP TABLE IF EXISTS $table_name;";
      $wpdb->query($sql);
  }

  register_deactivation_hook( __FILE__, 'amm_remove_database' );





function wpf_dev_skip_empty_pages() {
?>

<script type="text/javascript">

    jQuery(function($){

        // Initialize the empty action to track which button was clicked (prev/next)
        var action = "";

        // When next/prev button is clicked, store the action so we know which direction to skip
        $( document ).on( 'click', '.wpforms-page-button', function( event ) {
            action = $(this).data( 'action' );
			var page = $(this).data( 'page' );

            var taxi_code = document.getElementById('wpforms-231-field_1').value;
			console.log(page);
			if(page==1 && action=="next"){
                document.getElementsByClassName('wpforms-page-2')[0].style.display="none";
				$.post("?rest_route=/domainName/v1/checkCode",{
				  apikey: "dwqEk23dDf23",
				  taxi_code: taxi_code
				},function(data,status){
					console.log(data);
					if(data == "not found"){
                        document.getElementsByClassName('wpforms-page-prev')[0].click();
                        document.getElementById('wpforms-231-field_1-container').innerHTML+='<p class="error">تاکسیران گرامی لطفا از صحت کد تاکسیرانی خود اطمینان حاصل کنید.</p>';
					}else{
                        document.getElementsByClassName('wpforms-page-2')[0].style.display="block";
                    }
				});
			}
            if(page==2 && action=="next"){
                document.getElementsByClassName('wpforms-submit-container')[0].innerHTML+='<button  id="step3prev" class="wpforms-page-button wpforms-page-prev" style="display:none" data-action="prev" data-page="3" data-formid="231" aria-disabled="false" aria-describedby="">قبلی</button>';
                document.getElementsByClassName('wpforms-page-3')[0].style.display="none";
                $.post("?rest_route=/domainName/v1/checkCodeStep2",{
                    apikey: "dwqEk23dDf23",
                    taxi_code: taxi_code,
                    verifyCode: document.getElementById('wpforms-231-field_5').value
                },function(data,status){
                    console.log(data);
                    if(data == 0){
                        document.getElementById('step3prev').click();
                        document.getElementById('wpforms-231-field_5-container').innerHTML+='<p class="error">تاکسیران گرامی لطفا از صحت کد فعالسازی خود اطمینان حاصل کنید.</p>';
                    }else{
                        document.getElementsByClassName('wpforms-page-3')[0].style.display="block";
                    }
                });
            }
        });

    });

</script>

<?php
}

add_action( 'wpforms_wp_footer_end', 'wpf_dev_skip_empty_pages', 30 );


function sendSms($to,$verify_code){
    $data=array('username' =>"myusername", 'password'=>"mypass", 'to' =>"$to", 'from' => "num", "text" =>"text:".$verify_code);
    $post_data = http_build_query($data);
    $handle = curl_init('link');
    curl_setopt($handle, CURLOPT_HTTPHEADER, array(
        'content-type' => 'application/x-www-form-urlencoded'
    ));
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
    $response = curl_exec($handle);

}

function domainName_func(WP_REST_Request $request) {
	global $wpdb;
 	$spreadsheet = IOFactory::load( 'wp-content/uploads/2023/04/taxiDB.xlsx' );

      // Get the active worksheet
      $worksheet = $spreadsheet->getActiveSheet();

      // Loop through each row of data starting from row 1 (not assuming the first row contains headers)
      foreach ( $worksheet->getRowIterator( 1 ) as $row ) {
          // Get the user code from column A (assuming the user code is in column A)
          $user_code = $worksheet->getCell( 'A' . $row->getRowIndex() )->getValue();

          // Check if the user code matches the code entered by the user
          if ( $user_code == $request['taxi_code'] ) {
              // If there is a match, retrieve the other information about the user from the remaining columns
              $firstName = $worksheet->getCell( 'B' . $row->getRowIndex() )->getValue();
              $lastName = $worksheet->getCell( 'C' . $row->getRowIndex() )->getValue();
              $phoneNumber = $worksheet->getCell( 'D' . $row->getRowIndex() )->getValue();

              // Store the user's name and phone number in WordPress user meta or session variables
              // Replace 'user_name' and 'phone_number' with the actual meta keys you want to use
              $verify = mt_rand(000000,999999);
              $result = $wpdb->get_results ( "SELECT * FROM  `".amm_create_my_table()."` WHERE `code` = ".$user_code );
                $flag=true;
              foreach ( $result as $r )
              {
                  if (isset($r->id)){
                    $flag=false;
                    $firstName=$r->fname;
                    $lastName=$r->lname;

                    //add update query for verifyCode
                    $wpdb->get_results ( "UPDATE `".amm_create_my_table()."`SET `verifyCode` = $verify WHERE `code` = ".$request['taxi_code']);
                      
                    break;
                  }
              }
              if ($flag){
                  $wpdb->insert( amm_create_my_table(), array(
                      'code' => $user_code,
                      'fname' => $firstName,
                      'lname' => $lastName,
                      'phoneNumber' => $phoneNumber,
                      'verifyCode'=>$verify,
                      'isValid' => 0
                  ) );
              }

              break;
        }else{
			  $firstName="not";
			  $lastName="found";
		  }
      }
    sendSms($phoneNumber,$verify);
	return $firstName ." ". $lastName;
}



function domainName_checkCodeStep2(WP_REST_Request $request){
    global $wpdb;
    $result = $wpdb->get_results ( "SELECT * FROM  `".amm_create_my_table()."` WHERE `code` = ".$request['taxi_code']);
    $flag=true;
    foreach ( $result as $r )
    {
        if (isset($r->id)){
            $flag=false;
            $firstName=$r->fname;
            $lastName=$r->lname;
            if($r->verifyCode==$request['verifyCode']){
                //code is okay
                $res=1;
                //update isValid in db
                $resultUpdate = $wpdb->get_results ( "UPDATE `".amm_create_my_table()."`SET `isValid` = 1 WHERE `code` = ".$request['taxi_code']);
            }else{
                //code is invalid
                $res=0;
            }
            break;
        }
    }
    return $res;
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'domainName/v1', '/checkCode', array(
    'methods' => 'POST',
    'callback' => 'domainName_func',
  ) );
} );

add_action( 'rest_api_init', function () {
    register_rest_route( 'domainName/v1', '/checkCodeStep2', array(
        'methods' => 'POST',
        'callback' => 'domainName_checkCodeStep2',
    ) );
} );

