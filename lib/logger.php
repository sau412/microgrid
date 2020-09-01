<?php

function log_write($message, $severity = 7) {
        global $logger_url;
        global $project_log_name;

        $ch = curl_init($logger_url);
        $body = json_encode([
        	"source" => $project_log_name,
        	"severity" => $severity,
        	"message" => $message,
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $result_json = curl_exec($ch);
        curl_close($ch);        
        $result = json_decode($result_json, true);
        return $result;
}
