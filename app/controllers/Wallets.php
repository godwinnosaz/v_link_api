<?php 


Class Wallets extends Controller 
{
  protected $userModel;
  protected $serverKey;
  protected $walletModel;

  public function __construct()
  {
    $this->userModel = $this->model('User');
    $this->walletModel = $this->model('Wallet');
    $this->serverKey  = 'secret_server_key'.date("H");
  }

  public function solanaConnect()
  {
      $recipientPublicKey = '3sMwQKv5rct8jFjwvWE5ZfJvCxHmtP4H2U6z9vU7A4sA'; // Replace with actual recipient's public key
      $amount = 1000000; // Amount in lamports
  
    //   // Prepare data for the transaction
      $data = [
          'publicKey' => $recipientPublicKey,
          'amount' => $amount,
      ];
      
      // Get the Solana balance (assuming this method checks or displays balance)
    //   $this->getSolanaBalance($data);
      $res = $this->getSmartContractBalance();
  
      // Send the transaction
      $res2  = $this->sendTransactionxx($data);

      print_r(json_encode($res2));
      exit;
  }
  
  public function sendTransactionxx($data)
  {
    //   $secretKey = $data['key'];
      $recipientPublicKey = $data['publicKey'];
      $lamportsToSend = $data['amount'];
  
      // Create and serialize the transaction
      $serializedTransaction = $this->createAndSerializeTransaction($recipientPublicKey, $lamportsToSend);
      print_r(json_encode("Serialized Transaction: " . $serializedTransaction . PHP_EOL));
      // Set the Solana RPC endpoint URL
      $url = URL; // Change to the appropriate Solana endpoint
  
      // Prepare the request data
      $postData = json_encode([
          'jsonrpc' => '2.0',
          'id' => 1,
          'method' => 'sendTransaction',
          'params' => [$serializedTransaction, ['skipPreflight' => true]]
      ]);
  
      // Initialize cURL session
      $ch = curl_init($url);
  
      // Set cURL options
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type: application/json'
      ]);
  
      // Execute the request and get the response
      $response = curl_exec($ch);
      $error = curl_error($ch);
      curl_close($ch);
  
      // Check for errors and output the response
      if ($error) {
          echo "cURL Error: " . $error . PHP_EOL;
      } else {
          echo "Response: " . $response . PHP_EOL;
      }
  }
  


}