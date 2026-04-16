<?php
// Supabase REST API Connection using Service Role Key
$supabase_url = "https://usrznmvmojoyprmlbkgi.supabase.co";
$service_role_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InVzcnpubXZtb2pveXBybWxia2dpIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MjYzODIwMiwiZXhwIjoyMDg4MjE0MjAyfQ.PfLZHbyYuVAQ4hT-tvVslPEfonpXZGqFCqxBPS1Z9vQ";

class SupabaseConnection {
    private $url;
    private $key;
    
    public function __construct($url, $key) {
        $this->url = $url;
        $this->key = $key;
    }
    
    public function request($method, $table, $data = null, $filters = null) {
        $endpoint = "{$this->url}/rest/v1/{$table}";
        
        if ($filters && ($method === 'GET' || $method === 'DELETE')) {
            $query_params = [];
            foreach ($filters as $key => $value) {
                // Properly format filter value for Supabase
                $query_params[] = "{$key}=eq.{$value}";
            }
            if (!empty($query_params)) {
                $endpoint .= "?" . implode("&", $query_params);
            }
        }
        
        $ch = curl_init($endpoint);
        
        $headers = [
            "Authorization: Bearer {$this->key}",
            "apikey: {$this->key}",
            "Content-Type: application/json",
        ];
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        
        if ($data && ($method === 'POST' || $method === 'PATCH')) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code >= 400) {
            error_log("Supabase Error ($http_code): " . $response);
            return null;
        }
        
        return json_decode($response, true);
    }
    
    public function select($table) {
        return $this->request('GET', $table);
    }
    
    public function insert($table, $data) {
        $endpoint = "{$this->url}/rest/v1/{$table}";
        
        $ch = curl_init($endpoint);
        
        $headers = [
            "Authorization: Bearer {$this->key}",
            "apikey: {$this->key}",
            "Content-Type: application/json",
        ];
        
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
        ];
        
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log the response for debugging
        if ($http_code >= 400) {
            error_log("Supabase Insert Error ($http_code): " . $response);
            return false;
        }
        
        // Success - return true
        return true;
    }
    
    public function delete($table, $filters) {
        return $this->request('DELETE', $table, null, $filters);
    }
}

$conn = new SupabaseConnection($supabase_url, $service_role_key);
?>