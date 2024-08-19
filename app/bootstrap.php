<?php 

  // Load headers
  //require_once 'helpers/headers.php';

  require_once 'helpers/Program.php';

require_once 'helpers/headers.php';

//Load Config

 
require_once 'config/config.php';
require_once 'config/solana-sdk.php';

require_once 'helpers/BaseSolanaPhpSdkException.php';
require_once 'helpers/BorshException.php';
require_once 'helpers/Base58.php';
require_once 'helpers/ServiceInterface.php';
require_once 'helpers/GMPService.php';
require_once 'helpers/BCMathService.php';
  // Load helper
  require_once 'helpers/SPLTokenInstructions.php';
  require_once 'helpers/InputValidationException.php';
  require_once 'helpers/Connection.php';
  require_once 'helpers/HasPublicKey.php';
  require_once 'helpers/HasSecretKey.php';
  require_once 'helpers/Keypair.php';
  require_once 'helpers/Message.php';
  require_once 'helpers/PublicKey.php';
  require_once 'helpers/SolanaRpcClient.php';
  require_once 'helpers/Transaction.php';
  require_once 'helpers/TransactionInstruction.php';
  require_once 'helpers/AccountMeta.php';
  require_once 'helpers/Accounts.php';
  require_once 'helpers/BinaryReader.php';
  require_once 'helpers/BinaryWriter.php';
  require_once 'helpers/Bindings.php';
  require_once 'helpers/Borsh.php';
  require_once 'helpers/BorshDeserializable.php';
  require_once 'helpers/BorshObject.php';
  require_once 'helpers/BorshSerializable.php';
  require_once 'helpers/Buffer.php';
  require_once 'helpers/Commitment.php';
  require_once 'helpers/CompiledInstruction.php';
  require_once 'helpers/ConfirmOptions.php';
  require_once 'helpers/Creator.php';
  require_once 'helpers/DidData.php';
  require_once 'helpers/DidSolProgram.php';
  require_once 'helpers/Account.php';
  require_once 'helpers/Instructions.php';
  require_once 'helpers/MessageHeader.php';
  require_once 'helpers/Metadata.php';
  require_once 'helpers/MetadataData.php';
  require_once 'helpers/MetaplexProgram.php';
  require_once 'helpers/Mint.php';
  require_once 'helpers/NameRegistryStateAccount.php';
  require_once 'helpers/NonceInformation.php';
  require_once 'helpers/NtfRecordAccount.php';
  require_once 'helpers/ReverseInstructionAccount.php';
  require_once 'helpers/ServiceStruct.php';
  require_once 'helpers/ShortVec.php';
  require_once 'helpers/SignaturePubkeyPair.php';
  require_once 'helpers/Signer.php';
  require_once 'helpers/SNSError.php';
  require_once 'helpers/SnsProgram.php';
  require_once 'helpers/SPLTokenActions.php';
  require_once 'helpers/SPLTokenInstructions.php';
  require_once 'helpers/SplTokenProgram.php';
  require_once 'helpers/SystemProgram.php';
  require_once 'helpers/TodoException.php';
  require_once 'helpers/TokenInstruction.php';
  require_once 'helpers/Utils.php';
  require_once 'helpers/VerificationMethodStruct.php';
  require_once 'helpers/url_helper.php';
  
require_once 'helpers/SplTokenProgram.php';
require_once 'helpers/SystemProgram.php';

spl_autoload_register(function($className){
    require_once 'libraries/' . $className . '.php';
});




//require_once '../vendor/autoload.php';


 