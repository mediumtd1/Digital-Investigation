<?php

function mac_is_valid($mac) {
    return (bool) preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
}

function mac_detect_spoofing($mac) {
    try {
        $normalized = str_replace(['-', ':'], '', $mac);
        $first_octet_hex = substr($normalized, 0, 2);
        $first_octet_int = hexdec($first_octet_hex);
        // A locally administered MAC address has the second-least-significant bit of the first octet set to 1.
        return (bool) (($first_octet_int >> 1) & 1);
    } catch (Exception $e) {
        return false;
    }
}

function mac_analyze($mac) {
    if (!mac_is_valid($mac)) {
        return ["error" => "Invalid MAC Address format"];
    }
    
    $mac_normalized = strtoupper(str_replace('-', ':', $mac));
    
    $vendor = "Unknown Vendor";
    $country = "Unknown";
    $address = "Unknown";
    $is_spoofed = mac_detect_spoofing($mac_normalized);
    
    $data = [];
    try {
        $url = "https://api.maclookup.app/v2/macs/" . urlencode($mac_normalized);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $data = json_decode($response, true);
            if (!empty($data['success']) && !empty($data['found'])) {
                $vendor = $data['company'] ?? 'Unknown Vendor';
                $country = $data['country'] ?? 'Unknown';
                $address = $data['address'] ?? 'Unknown';
                if (!empty($data['isRand'])) {
                    $is_spoofed = true;
                }
            }
        } elseif ($http_code == 429) {
            return ["error" => "API Rate Limit Exceeded. Please try again later."];
        }
    } catch (Exception $e) {
        // Fallback to defaults
    }

    return [
        "mac_address" => $mac_normalized,
        "vendor" => $vendor,
        "country" => $country,
        "address" => $address,
        "is_spoofed" => $is_spoofed,
        "mac_prefix" => $data['macPrefix'] ?? substr(str_replace(':', '', $mac_normalized), 0, 6),
        "block_type" => $data['blockType'] ?? 'Unknown',
        "is_private" => $data['isPrivate'] ?? false,
        "last_api_update" => $data['updated'] ?? 'Unknown',
        "timestamp" => date("Y-m-d H:i:s")
    ];
}

function mac_analyze_ip($ip) {
    try {
        $url = "http://ip-api.com/json/" . urlencode($ip);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200 && $response !== false) {
            $data = json_decode($response, true);
            if (($data['status'] ?? '') === 'success') {
                return [
                    "ip" => $ip,
                    "country" => $data['country'] ?? null,
                    "region" => $data['regionName'] ?? null,
                    "city" => $data['city'] ?? null,
                    "isp" => $data['isp'] ?? null,
                    "zip" => $data['zip'] ?? null,
                    "lat" => $data['lat'] ?? null,
                    "lon" => $data['lon'] ?? null,
                    "timezone" => $data['timezone'] ?? null,
                    "as" => $data['as'] ?? null,
                    "status" => "success"
                ];
            } else {
                return ["error" => "API Error: " . ($data['message'] ?? 'Unknown error'), "status" => "fail"];
            }
        }
        return ["error" => "Failed to connect to IP API (HTTP $http_code)", "status" => "fail"];
    } catch (Exception $e) {
        return ["error" => $e->getMessage(), "status" => "fail"];
    }
}

function mac_get_ip_from_mac($mac) {
    try {
        $mac_normalized = strtolower(str_replace(':', '-', $mac));
        $output = shell_exec("arp -a");
        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (strpos(strtolower($line), $mac_normalized) !== false) {
                    $parts = preg_split('/\s+/', trim($line));
                    if (count($parts) >= 2) {
                        return $parts[0]; // The IP address
                    }
                }
            }
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}
?>
