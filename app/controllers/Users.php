<?php 


Class Users extends Controller 
{
  private $userModel;
  private $serverKey;

  public function __construct()
  {
    $this->userModel = $this->model('User');
    $this->serverKey  = 'secret_server_key'.date("H");
  }

  public function loginfunc()
  {
    $jsonData = $this->getData();
    if (!isset($jsonData['email']) || !isset($jsonData['password'])) {
      $response = array(
        'status' => 'false',

        'message' => 'Enter login details',

      );

      print_r(json_encode($response));
      exit;
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

      $loginData = $this->getData();

      // Init data
      $data = [

        'email' => trim($jsonData['email']),
        'password' => trim($jsonData['password']),
        'email_err' => '',
        'msg' => '',
        'loginStatus' => '',
        'password_err' => '',
      ];
      // Validate Email
      if (empty($data['email'])) {
        $data['email_err'] = 'Please enter email';
      }

      // Validate password
      if (empty($data['password'])) {
        $data['password_err'] = 'Please enter password';
      }
      if ((empty($data['email_err'])) && (empty($data['password_err']))) {
        if ($this->userModel->findUserByEmail1($data['email'])) {
          $loginDatax = $this->userModel->loginUser($data['email']);
          $postPassword = $data['password'];
         
          $hash_password = $loginDatax->password;
          $email = $loginDatax->email;
          $user_id = $loginDatax->user_id;
       if ((password_verify($postPassword, $hash_password))) {



            $tokenX = $token = "token" . md5(date("dmyhis") . rand(1222, 89787)) . md5(date("dmyhis") . rand(1222, 89787)) . md5(date("dmyhis") . rand(1222, 89787)) . md5(date("dmyhis") . rand(1222, 89787)) . md5(date("dmyhis") . rand(1222, 89787));
            $this->userModel->updateToken($user_id, $tokenX);

            $loginData = $this->userModel->findLoginByToken($tokenX);
             
            $userData = $this->userModel->findUserByEmail_det($loginData->email);
            $initData = [
              'loginData' => $loginData,
              'userData' => $userData,
            ];

            $datatoken = [
              'user_id' => $user_id,
              'appToken' => $initData['loginData']->token,

            ];
            $JWT_token = $this->getMyJsonID($datatoken, $this->serverKey);
            $response = array(
              'status' => true,
              'access_token' => $JWT_token,
              'datatoken' => $datatoken,
              'message' => 'success',
              'data' => $initData,

            );


            print_r(json_encode($response));
            exit;
          } else {
            $response = array(
              'status' => 'false',
              'message' => 'Invalid password',

            );

            print_r(json_encode($response));
            exit;
          }

        } else {


          $response = array(
            'status' => 'false',

            'message' => 'invalid user login detail',
            'data' => $data,
          );

          print_r(json_encode($response));
          exit;
        }
      } else {
        $response = array(
          'status' => 'false',
          'message' => 'All input fields must be complete',
          'data' => $data,
        );

        print_r(json_encode($response));
        exit;
      }


    } else {

      $response = array(
        'status' => 'false',

        'message' => 'Invalid server method',

      );

      print_r(json_encode($response));
      exit;
    }

    
  }


  
  public function register_user()
  {
      
      $sentData = $this->getData();
      $data = array(
          "fullname" => trim($sentData["fullname"]),
          "uname" => trim($sentData["uname"]),
          "email" => trim($sentData["email"]),
          "x_link" => trim($sentData["x_link"]),
          "user_id" =>  $this->generateUniqueId(),
          "insta_link" => trim($sentData["insta_link"]),
          "linkedin_link" => trim($sentData["linkedin_link"]),
          "image" => $_FILES["image"],
          "password"=> trim($sentData["password"]),
          "confirm_password"=> trim($sentData["confirm_password"]),
      );

      foreach ($data as $key => $value) {
          if (is_string($value) && $value === "") {
              $res = json_encode(array(
                  "status" => false,
                  "message" => "Incomplete params: " . $key . " is required."
              ));

              print_r($res);
              exit;
          }
      }

      if (!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
        print_r(json_encode(array(
            "status" => false,
            "message" => "Invalid email."
        )));
        exit;
    }

    if($this->userModel->findUserByEmail1($data['email'])){
      print_r(json_encode(array(
        "status" => false,
        "message" => "User Already registered ."
    )));
    exit;
    }

      $new_image_names = [];

      $extensions = ["jpeg", "png", "jpg"];
      $types = ["image/jpeg", "image/jpg", "image/png"];

      $image_fields = ['image'];

      foreach ($image_fields as $image_field) {
          if (isset($data[$image_field])) {
              $img_name = $data[$image_field]['name'];
              $img_type = $data[$image_field]['type'];
              $tmp_name = $data[$image_field]['tmp_name'];
              $img_explode = explode('.', $img_name);
              $img_ext = end($img_explode);

              if (in_array($img_ext, $extensions) === true) {
                  if (in_array($img_type, $types) === true) {
                      $time = time();
                      $new_img_name = $time . "_" . $img_name;
                      if (move_uploaded_file($tmp_name,  "assets/img/attachment/" . $new_img_name)) {
                          $new_image_names[$image_field] = $new_img_name;
                      } else {
                          $response = array(
                              'status' => 'false',
                              'message' => "Upload failed for $image_field",
                          );
                          print_r(json_encode($response));
                          exit;
                      }
                  } else {
                      $response = array(
                          'status' => 'false',
                          'message' => "Invalid file type for $image_field. Allowed types are: " . implode(', ', $types),
                      );
                      print_r(json_encode($response));
                      exit;
                  }
              } else {
                  $response = array(
                      'status' => 'false',
                      'message' => "Invalid file extension for $image_field. Allowed extensions are: " . implode(', ', $extensions),
                  );
                  print_r(json_encode($response));
                  exit;
              }
          } else {
              $response = array(
                  'status' => 'false',
                  'message' => "$image_field not set",
              );
              print_r(json_encode($response));
              exit;
          }
      }

      foreach ($new_image_names as $key => $value) {
          $data[$key] = $value;
      }


      
      if ($this->userModel->register_user($data)) {

          ///////// sends email




          //////////end


          $res = json_encode(array(
              'status' => true,
              'message' => 'registeration successful'
          ));
          print_r($res);
          exit;


      } else {
          
          $res = json_encode(array(
              'status' => false,
              'message' => 'registeration failed'
          ));
          print_r($res);
          exit;
      }



  }



  public function forgetPassword() {

    $sentData = $this->getData();

    $data = [
      'email'=> $sentData['email'],
    ];

    if ($this->userModel->findUserByEmail1($data['email'])) {

      ///////sends email with otp////

      $data['otp'] = $this->generateSixDigitValue();


      if($this->emailSent($data)){

        $this->userModel->updateResetToken($data);
        $res = json_encode(array(
          'status'=> true,
          'message'=> 'otp sent'
          ));
          print_r($res);
      }else {
        $res = json_encode(array(
          'status'=> false,
          'message'=> 'otp not sent'
          ));
          print_r($res);
      }





    }
  }

}