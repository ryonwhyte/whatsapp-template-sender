<?php

if (!defined('ABSPATH')) {
    exit;
}

class WTS_API_Service
{
    private $access_token;
    private $phone_number_id;
    private $business_account_id;
    private $api_url = 'https://graph.facebook.com/v19.0/';

    public function __construct()
    {
        $this->access_token = get_option('wts_access_token');
        $this->phone_number_id = get_option('wts_phone_number_id');
        $this->business_account_id = get_option('wts_business_account_id');
    }

    public function send_template_message($to, $template_name, $parameters = array(), $language = 'en')
    {
        if (empty($this->access_token) || empty($this->phone_number_id)) {
            return array(
                'success' => false,
                'message' => 'API credentials not configured. Please check settings.',
                'response' => 'Missing access token or phone number ID'
            );
        }

        $to = $this->format_phone_number($to);
        
        if (!$this->is_valid_phone_number($to)) {
            return array(
                'success' => false,
                'message' => 'Invalid phone number format',
                'response' => 'Phone number validation failed'
            );
        }

        $payload = $this->build_template_payload($to, $template_name, $parameters, $language);
        
        $response = $this->make_api_request($payload);
        
        return $response;
    }

    private function format_phone_number($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) == 10) {
            $phone = '1' . $phone;
        }
        
        return $phone;
    }

    private function is_valid_phone_number($phone)
    {
        return preg_match('/^[1-9]\d{10,14}$/', $phone);
    }

    private function build_template_payload($to, $template_name, $parameters, $language = 'en')
    {
        $payload = array(
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => array(
                'name' => $template_name,
                'language' => array('code' => $language)
            )
        );

        if (!empty($parameters)) {
            $template_parameters = array();
            foreach ($parameters as $param) {
                $template_parameters[] = array(
                    'type' => 'text',
                    'text' => $param
                );
            }

            $payload['template']['components'] = array(
                array(
                    'type' => 'body',
                    'parameters' => $template_parameters
                )
            );
        }

        return $payload;
    }

    private function make_api_request($payload)
    {
        $url = $this->api_url . $this->phone_number_id . '/messages';
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message(),
                'response' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'message' => 'Message sent successfully',
                'response' => $response_data,
                'message_id' => isset($response_data['messages'][0]['id']) ? $response_data['messages'][0]['id'] : null
            );
        } else {
            $error_message = 'API Error';
            if (isset($response_data['error']['message'])) {
                $error_message = $response_data['error']['message'];
            }

            return array(
                'success' => false,
                'message' => 'Failed to send message: ' . $error_message,
                'response' => $response_data
            );
        }
    }

    public function get_available_templates()
    {
        if (empty($this->access_token) || empty($this->business_account_id)) {
            return array();
        }

        $url = $this->api_url . $this->business_account_id . '/message_templates';
        
        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
            ),
            'timeout' => 30
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array();
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code >= 200 && $response_code < 300 && isset($response_data['data'])) {
            return $response_data['data'];
        }

        return array();
    }

    public function test_connection()
    {
        if (empty($this->access_token) || empty($this->phone_number_id) || empty($this->business_account_id)) {
            return array(
                'success' => false,
                'message' => 'API credentials not configured. Please enter Access Token, Phone Number ID, and Business Account ID.'
            );
        }

        $url = $this->api_url . $this->phone_number_id;
        
        $args = array(
            'method' => 'GET',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
            ),
            'timeout' => 15
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'message' => 'Connection successful'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'API connection failed with status code: ' . $response_code
            );
        }
    }
}