<?php

require_once(dirname('__FILE__') . '/vendor/autoload.php');

if(!isset($_POST['nasid'])){
  /*
   * Initial entry point for the user. The `$_GET` vars wil contain some
   * information about the referring AP, user MAC and identification challenge.
   * At this point it is possible to request additional information from the
   * user, like a facebook login or e-mail address.
   *
   * An example entry url will look like:
   * http://{{your_url}}/?res=notyet&uamip=10.1.4.1
     &uamport=3990&challenge=1e6060a5a0af04e0b487e702b52d5d57
     &called=00-10-F3-1C-58-E1&mac=9C-B7-0D-88-56-56&ip=10.1.4.34
     &nasid=interexperts.efw&sessionid=505ac70b00000001
     &userurl=http%3a%2f%2fwww.nu.nl%2f&md=6B3338E95420A43F53C8641762F7103C
   *
   */

  ?>
  <form method='POST' action='/'>
   <fieldset>
     <label>User e-mail</label><br />
     <input type='email' required='required' name='user-email' />
     <br /><br />
     <button>Sign in</button>
  </fieldset>
  <fieldset>
    <legend>Hidden variables (shown for debugging purposes)</legend>
    <label>Nas ID (device_id)</label><br />
    <input type='text' required='required' name='nasid' value='<?php echo $_GET['nasid']?>' />
    <br /><br />
    <label>Challenge</label><br />
    <input type='text' required='required' name='challenge' value='<?php echo $_GET['challenge']?>' />
    <br /><br />
    <label>API-endpoint (no ending `/`)</label><br />
    <input type='text' required='required' name='api_endpoint' value='' />
    <br /><br />
    <label>API-key</label><br />
    <input type='text' required='required' name='api_key' value='' />
  </fieldset>
  <?php
}else{
  /*
   * The user has agreed to the terms of service and provided additional
   * information like an e-mailaddress. Here you can save this data to the
   * database and continue with authentication on the accesspoint.
   */

  /* To authenticate the user we will first contact the hotspotmanager.nl API
   * to request a new identification key. For all following requests we will
   * be using the `httpful` library (http://phphttpclient.com/).
   */
  $response = \Httpful\Request::post("{$_POST['api_endpoint']}/code/")
    ->addHeader('X-Api-Key', $_POST['api_key'])
    ->send();
  // var_dump($response); // Uncomment this line to see extended debugging info
  $code = $response->body->codes[0];

  /*
   * Now that we have a new validation code it is necessary to generate a
   * challenge URL that the accesspoint can use to verify the user against
   * the hotspotmanager.nl authentication service. To retrieve this challenge
   * URL we will query the API with our device identifier, accesspoint
   * challenge code and the identification code we got in the last API call.
   *
   * You can add the parameter `userurl` to redirect the user after completing
   * their authentication on the accesspoint.
   */
  $response = \Httpful\Request::get("{$_POST['api_endpoint']}/code/{$code->username}/challenge/{$_POST['nasid']}/{$_POST['challenge']}/")
    ->addHeader('X-Api-Key', $_POST['api_key'])
    ->send();
  // var_dump($response); // Uncomment this line to see extended debugging info
  $challenge_url = $response->body->challenge_url;

  /*
   * Finally, we transfer the user to the accesspoint challenge URL that
   * logs them into the network and grants them internet access. In this example
   * we require you to click the link. In production you can automatically
   * redirect the user in order to skip this step.
   */
   echo "<a href='{$challenge_url}'>Click to finalize</a>";
}
