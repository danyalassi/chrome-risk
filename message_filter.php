<?php
$filePath = 'https://'.$_SERVER['HTTP_HOST'].'/diogenes22.txt';

function getSwearWordsFromFile($filePath) {
    $swearWords = array();
    $file = fopen($filePath, 'r');
    if ($file) {
        while (($line = fgets($file)) !== false) {
            $line = trim($line);
            if (!empty($line)) {
                $swearWords[] = $line;
            }
        }
        fclose($file);
    }
    return $swearWords;
}

$swearWords = getSwearWordsFromFile($filePath);

function censorWord($word) {
    return str_repeat("*", strlen($word));
}

function filterMessage($message, $bannedWords) {
    $profanityCheckURL = "https://community-purgomalum.p.rapidapi.com/json?text=" . urlencode($message);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $profanityCheckURL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "X-RapidAPI-Host: community-purgomalum.p.rapidapi.com",
            "X-RapidAPI-Key: 152656d635msh73144a392b9934ap1f00d9jsn7ee780e8ade8"
        ],
    ]);

    $profanityCheckResponse = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        if (trim($profanityCheckResponse) === 'true') {
            $message = str_replace($url, '***', $message);
        }
    }

    foreach ($bannedWords as $bannedWord) {
        $pattern = '/\b' . preg_quote($bannedWord, '/') . '\s*\w*\b/i';
        $message = preg_replace($pattern, '***', $message);
    }

    return $message;
}

function filterUsername($username, $bannedWords) {
    $profanityCheckURL = "https://auth.roblox.com/v1/validators/username?Username=" . urlencode($username) . "&Birthday=2024-01-01T00:00:00.000Z";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $profanityCheckURL,
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        $responseJson = json_decode($response, true);

        if (isset($responseJson['errors'])) {
            return "Could not generate a valid username. Please choose another username.";
        } else {
            $didGenerateNewUsername = $responseJson['didGenerateNewUsername'];
            if ($didGenerateNewUsername) {
                return filterMessage($username, $bannedWords);
            } else {
                $filteredUsername = filterMessage($username, $bannedWords);
                if ($filteredUsername !== $username) {
                    return "This word has been flagged, please contact danyalassi23@gmail.com if it isn't a bad word.";
                } else {
                    return $username;
                }
            }
        }
    }
}


?>
