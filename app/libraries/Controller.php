<?php
/*
 *Base Controller
 *Loads the models and views
*/

class Controller
{
    private $userModel;
    private $auth_header;
    private $serverKey;

    private $rpcClient;

    public function __construct()
    {
        $this->userModel = $this->model('User');
        $this->rpcClient = new SolanaRpcClient('https://api.devnet.solana.com');
    }

    // Load model
    public function model($model)
    {
        // Require model file
        require_once '../app/models/' . $model . '.php';

        // Instantiate model
        return new $model();
    }

    // Load view
    public function view($view, $data = [])
    {
        // Check for view file
        if (file_exists('../app/views/' . $view . '.php')) {
            require_once '../app/views/' . $view . '.php';
        } else {
            // View doesn't exist
            die("view does not exist");
        }
    }

    public function generateUniqueId()
    {
        // Generate a UUID
        $uuid = uniqid('', true);

        // Get the current timestamp
        $timestamp = microtime(true);

        // Hash them together to ensure uniqueness
        $uniqueId = hash('sha256', $uuid . $timestamp);

        return $uniqueId;
    }

    public function getSmartContractBalance($publicKey)
    {
        // Set the Solana RPC endpoint URL
        $url = 'https://api.devnet.solana.com'; // Change to the appropriate Solana endpoint

        // Prepare the request data
        $postData = json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getBalance',
            'params' => [$publicKey]
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

        // Check for errors and handle response
        if ($error) {
            throw new RuntimeException("cURL Error: " . $error);
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['result']['value'])) {
            return $responseData['result']['value']; // Balance in lamports
        } else {
            throw new RuntimeException("Failed to get balance: " . json_encode($responseData));
        }
    }

    public function generateSolanaKeypair()
    {
        // Generate a new keypair
        $keypair = Keypair::generate();
    
        // Extract the secret key and public key
        $secretKey = $keypair->getSecretKey();
        $publicKey = $keypair->getPublicKey()->toBase58();
    
        // Convert the secret key to base64 for storage
        $encodedSecretKey = base64_encode($secretKey);
    
        // Return the keypair data and the encoded secret key
        return [
            'keypair' => $keypair,
            'encodedSecretKey' => $encodedSecretKey,
            'publicKey' => $publicKey,
        ];
    }

    public function createAndSerializeTransaction($recipientPublicKey, $lamportsToSend)
    {
        // Generate a new keypair
        $keypairData = $this->generateSolanaKeypair();
        $payerKeypair = $keypairData['keypair'];
        $payerPublicKey = $payerKeypair->getPublicKey();
    
        // Convert recipient public key string to PublicKey object
        $recipientPublicKeyObj = new PublicKey($recipientPublicKey);
    
        // Fetch the recent blockhash
        $recentBlockhash = $this->fetchRecentBlockhash();
    
        // Create a transaction
        $transaction = new Transaction();
    
        // Set the recent blockhash and fee payer
        $transaction->recentBlockhash = $recentBlockhash;
        $transaction->feePayer = $payerPublicKey;
        
        // Create AccountMeta objects
        $payerAccountMeta = new AccountMeta($payerPublicKey, true, true);
        $recipientAccountMeta = new AccountMeta($recipientPublicKeyObj, true, false);
    
        // Add instructions to the transaction (e.g., a simple transfer)
        $transaction->add(
            new TransactionInstruction(
                SystemProgram::getProgramId(), // Use SystemProgram ID for a simple transfer
                [$payerAccountMeta, $recipientAccountMeta],
                Buffer::from(json_encode(['lamports' => $lamportsToSend])) // Ensure this is the correct format
            )
        );
    
        // Sign the transaction with the payer's keypair
        $transaction->sign($payerKeypair);
    
        // Serialize the transaction
        $serializedTransaction = $transaction->serialize(); // This returns binary data
        
        // Encode the serialized transaction in base64
        $encodedTransaction = base64_encode($serializedTransaction);
    
        // Return the encoded transaction
        return $encodedTransaction;
    }

    public function fetchRecentBlockhash()
    {
        // Define Solana RPC endpoint
        $rpcUrl = 'https://api.devnet.solana.com'; // or your desired RPC URL

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $rpcUrl . '/getRecentBlockhash');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true); // Use POST method

        // Execute cURL session and get the response
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            curl_close($ch);
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        // Close cURL session
        curl_close($ch);

        // Decode the response
        $data = json_decode($response, true);

        // Check if recent blockhash is present
        if (!isset($data['result']['value']['blockhash'])) {
            throw new Exception('Failed to fetch recent blockhash.');
        }

        return $data['result']['value']['blockhash'];
    }

    public function generateSixDigitValue() {
        $random = mt_rand(0, 999);
        $timeString = date('s'); 
        $combined = $random . $timeString;
        return substr(str_pad($combined, 6, '0', STR_PAD_LEFT), -6);
    }
    public function initializeContract($signerKeypair, $newAccountKeypair)
    {
        // Create a transaction
        $transaction = new Transaction();
        
        // Add initialization instruction
        $instruction = new TransactionInstruction(
            new PublicKey('YourProgramPublicKeyHere'), // Replace with your program's public key
            [
                new AccountMeta($signerKeypair->getPublicKey(), true, true),
                new AccountMeta($newAccountKeypair->getPublicKey(), true, true),
                new AccountMeta(SystemProgram::getProgramId(), false, false),
            ]
        );

        $transaction->add($instruction);
        $transaction->sign($signerKeypair, $newAccountKeypair);

        // Serialize and encode the transaction
        $serializedTransaction = base64_encode($transaction->serialize());

        // Send this transaction to the Solana network
        return $serializedTransaction;
    }

    public function sendSol($recipientPublicKey, $lamportsToSend)
    {
        // Generate a new keypair
        $keypairData = $this->generateSolanaKeypair();
        $payerKeypair = $keypairData['keypair'];

        // Create a transaction to send SOL
        $transaction = $this->createAndSerializeTransaction($recipientPublicKey, $lamportsToSend);

        // Send this transaction to the Solana network
        return $transaction;
    }

    public function getData()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (json_encode($data) === 'null') {
            return $data = $_POST;
        } else {
            return $data;
        }
    }

    public function getMyJsonID($token, $serverKey)
    {
        return JWT::encode($token, $serverKey);
    }

    public function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $request_headers = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public function bearer()
    {
        $this->auth_header = $this->getAuthorizationHeader();
        if ($this->auth_header && preg_match('#Bearer\s(\S+)#', $this->auth_header, $matches)) {
            return $matches[1];
        }
    }

    public function myJsonID($bearer, $serverKey)
    {
        $myJsonID = JWT::decode($bearer, $serverKey);
        return $myJsonID !== 401 ? $myJsonID : false;
    }

    public function serverKey()
    {
        return 'secret_server_keysa' . date("M");
    }

    public function RouteProtection()
    {
        $headers = $this->getAuthorizationHeader();

        if (!isset($headers)) {
            $response = ['error' => 'Authorization header missing', 'status' => 401];
            print_r(json_encode($response));
        } else {
            $jwt = str_replace('Bearer ', '', $headers);
            $decoded = $this->myJsonID($jwt, $this->serverKey);

            if (!$decoded) {
                $response = ['error' => 'Invalid token', 'status' => 401];
                print_r(json_encode($response));
            } else {
                return $this->getuserbyid();
            }
        }
    }

    public function getuserbyid()
    {
        $bearer = $this->bearer();
        if ($bearer) {
            $userId = $this->myJsonID($bearer, $this->serverKey);
            if (!isset($userId)) {
                $response = [
                    'status' => 'false',
                    'message' => 'Oops Something Went Wrong x get!!',
                ];
                print_r(json_encode($response));
                exit;
            }
            $vb = $this->userModel->getuserbyid($userId->user_id);

            if (empty($userId->user_id)) {
                $response = [
                    'status' => 'false',
                    'message' => 'No user with this userID!'
                ];
                print_r(json_encode($response));
            } else {
                return $vb;
            }
        }
    }
}
