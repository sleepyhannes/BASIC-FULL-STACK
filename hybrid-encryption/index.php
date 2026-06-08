<?php
    // HYBRID ENCRYPTION SYSTEM (AES + RSA)
    // This is a simple demo of how hybrid encryption works.
    // - RSA protects the AES key
    // - AES encrypts the actual message
$result = null;
    // Only run the encryption when the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    
    /*PART 1 – RSA KEY GENERATION (2048-bit)
      It creates:
      - Public key (used to encrypt)
      - Private key (used to decrypt)
    I’m using 2048-bit because that’s the standard secure size.
    */
    
    $config = [
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];

    $rsaKeyPair = openssl_pkey_new($config);

    // Save the private key into a variable
    openssl_pkey_export($rsaKeyPair, $privateKey);

    // Get the public key from the key pair
    $publicKeyDetails = openssl_pkey_get_details($rsaKeyPair);
    $publicKey = $publicKeyDetails["key"];

    
    /* PART 2 – AES ENCRYPTION
    AES is symmetric encryption.
    It’s much faster than RSA, so we use it to encrypt
    the actual message.

    I’m using AES-256-CBC:
    - 256-bit key
    - CBC mode
    */

    // Get message from user input
    $originalMessage = $_POST["message"];

    // Generate random 256-bit AES key (32 bytes)
    $aesKey = random_bytes(32); 

    // Generate random IV (16 bytes required for AES-256-CBC)
    $iv = random_bytes(16);

    // Encrypt the message
    $aesEncrypted = openssl_encrypt(
        $originalMessage,
        "AES-256-CBC",
        $aesKey,
        OPENSSL_RAW_DATA,
        $iv
    );

    // Encode everything to Base64 so it can be displayed properly
    $encodedAESKey = base64_encode($aesKey);
    $encodedIV = base64_encode($iv);
    $encodedEncryptedMessage = base64_encode($aesEncrypted);

    
    /*PART 3 – Encrypt the AES Key using RSA
    Here’s the important part.
    We don’t send the AES key directly.
    We encrypt it using the RSA public key first.
    */
    
    openssl_public_encrypt($aesKey, $encryptedAESKey, $publicKey);
    $encodedEncryptedAESKey = base64_encode($encryptedAESKey);

    /* PART 4 – Decrypt the AES Key using RSA
    On the receiver side, we use the RSA private key
    to recover the original AES key.
    */
    openssl_private_decrypt($encryptedAESKey, $decryptedAESKey, $rsaKeyPair);
    $encodedRecoveredAESKey = base64_encode($decryptedAESKey);
    
    /*PART 5 – AES DECRYPT MESSAGE
    Once we recover the AES key,
    we can now decrypt the encrypted message.
    */
    
    $decryptedMessage = openssl_decrypt(
        $aesEncrypted,
        "AES-256-CBC",
        $decryptedAESKey,
        OPENSSL_RAW_DATA,
        $iv
    );

    // Just to prove everything worked
    $verification = ($originalMessage === $decryptedMessage)
        ? "SUCCESS ✅ Message Matches!"
        : "FAILED ❌ Message Does Not Match!";
    // Store results for display
    $result = compact(
        "publicKey",
        "privateKey",
        "originalMessage",
        "encodedAESKey",
        "encodedIV",
        "encodedEncryptedMessage",
        "encodedEncryptedAESKey",
        "encodedRecoveredAESKey",
        "decryptedMessage",
        "verification"
    );
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hybrid Encryption System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-green-900 via-black to-green-800 min-h-screen text-white">

<div class="container mx-auto px-6 py-10">

    <h1 class="text-4xl font-bold text-center mb-8">
        🔐 Hybrid Encryption (AES-256 + RSA 2048)
    </h1>

    <!-- Input Form -->
    <div class="bg-white/10 backdrop-blur-lg p-6 rounded-2xl shadow-xl mb-10">
        <form method="POST">
            <label class="block mb-2 text-lg font-semibold">
                Enter Message:
            </label>
            <textarea 
                name="message" 
                required
                class="w-full p-3 rounded-lg text-black focus:outline-none focus:ring-2 focus:ring-green-400"
                rows="3"
                placeholder="Type your secret message here..."></textarea>

            <button 
                type="submit"
                class="mt-4 bg-green-500 hover:bg-green-600 transition px-6 py-2 rounded-lg font-semibold shadow-lg">
                Encrypt & Decrypt
            </button>
        </form>
    </div>

    <?php if ($result): ?>

    <!-- Results Section -->
    <div class="space-y-8">

        <?php
        function card($title, $content) {
            echo '
            <div class="bg-white/10 backdrop-blur-lg p-6 rounded-2xl shadow-xl">
                <h2 class="text-2xl font-bold mb-3 text-green-400">'.$title.'</h2>
                <pre class="text-sm break-words whitespace-pre-wrap">'.htmlspecialchars($content).'</pre>
            </div>';
        }
        ?>

        <?php card("RSA Public Key", $result["publicKey"]); ?>
        <?php card("RSA Private Key", $result["privateKey"]); ?>
        <?php card("Original Message", $result["originalMessage"]); ?>
        <?php card("AES Key (Base64)", $result["encodedAESKey"]); ?>
        <?php card("IV (Base64)", $result["encodedIV"]); ?>
        <?php card("AES Encrypted Message (Base64)", $result["encodedEncryptedMessage"]); ?>
        <?php card("Encrypted AES Key via RSA (Base64)", $result["encodedEncryptedAESKey"]); ?>
        <?php card("Recovered AES Key (Base64)", $result["encodedRecoveredAESKey"]); ?>
        <?php card("Decrypted Message", $result["decryptedMessage"]); ?>
        <?php card("Verification Result", $result["verification"]); ?>

    </div>

    <?php endif; ?>

</div>

</body>
</html>