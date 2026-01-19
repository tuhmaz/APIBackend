<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class SmtpTestService
{
    /**
     * Test SMTP connection with enhanced diagnostics
     */
    public function testConnection($config = null)
    {
        try {
            // Get SMTP configuration
            $smtpConfig = $this->getSmtpConfig($config);
            
            // Validate configuration
            $this->validateConfig($smtpConfig);
            
            // Test DNS resolution first
            $this->testDnsResolution($smtpConfig['host']);
            
            // Test port connectivity
            $this->testPortConnectivity($smtpConfig['host'], $smtpConfig['port']);
            
            // Test SMTP connection
            return $this->performSmtpTest($smtpConfig);
            
        } catch (Exception $e) {
            Log::error('SMTP Test Service Error: ' . $e->getMessage(), [
                'config' => $smtpConfig ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $this->getDetailedErrorMessage($e),
                'error_code' => $e->getCode(),
                'suggestions' => $this->getSuggestions($e)
            ];
        }
    }
    
    /**
     * Get SMTP configuration from database or provided config
     */
    private function getSmtpConfig($config = null)
    {
        if ($config) {
            return $config;
        }
        
        return [
            'host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
            'port' => (int) Setting::get('mail_port', config('mail.mailers.smtp.port')),
            'username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
            'password' => Setting::get('mail_password', config('mail.mailers.smtp.password')),
            'encryption' => Setting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
            'timeout' => 30,
            'auth_mode' => 'login'
        ];
    }
    
    /**
     * Validate SMTP configuration
     */
    private function validateConfig($config)
    {
        if (empty($config['host'])) {
            throw new Exception('SMTP host is required');
        }
        
        if (empty($config['port']) || !is_numeric($config['port'])) {
            throw new Exception('Valid SMTP port is required');
        }
        
        if (!in_array($config['encryption'], ['ssl', 'tls', ''])) {
            throw new Exception('Invalid encryption type. Use ssl, tls, or leave empty');
        }
        
        // Validate port and encryption combination
        if ($config['port'] == 465 && $config['encryption'] !== 'ssl') {
            Log::warning('Port 465 typically requires SSL encryption');
        }
        
        if ($config['port'] == 587 && $config['encryption'] !== 'tls') {
            Log::warning('Port 587 typically requires TLS encryption');
        }
    }
    
    /**
     * Test DNS resolution
     */
    private function testDnsResolution($host)
    {
        $ip = gethostbyname($host);
        if ($ip === $host) {
            throw new Exception("DNS resolution failed for host: {$host}");
        }
        
        Log::info("DNS resolution successful for {$host}: {$ip}");
        return $ip;
    }
    
    /**
     * Test port connectivity
     */
    private function testPortConnectivity($host, $port)
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        
        if (!$connection) {
            throw new Exception("Cannot connect to {$host}:{$port} - {$errstr} (Error: {$errno})");
        }
        
        fclose($connection);
        Log::info("Port connectivity test successful for {$host}:{$port}");
    }
    
    /**
     * Perform actual SMTP test
     */
    private function performSmtpTest($config)
    {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = !empty($config['username']);
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->Port = $config['port'];
            $mail->Timeout = $config['timeout'];
            
            // Encryption settings
            if ($config['encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($config['encryption'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            // SSL/TLS options for self-signed certificates
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Enable verbose debug output
            $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            $mail->Debugoutput = function($str, $level) {
                Log::debug("SMTP Debug Level {$level}: " . trim($str));
            };
            
            // Test connection
            if (!$mail->smtpConnect()) {
                throw new Exception('SMTP connection failed');
            }
            
            // Test authentication if credentials provided
            if (!empty($config['username'])) {
                if (!$mail->authenticate($config['username'], $config['password'])) {
                    throw new Exception('SMTP authentication failed');
                }
            }
            
            $mail->smtpClose();
            
            return [
                'success' => true,
                'message' => 'SMTP connection and authentication successful',
                'details' => [
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'encryption' => $config['encryption'],
                    'auth' => !empty($config['username'])
                ]
            ];
            
        } catch (Exception $e) {
            $mail->smtpClose();
            throw $e;
        }
    }
    
    /**
     * Get detailed error message
     */
    private function getDetailedErrorMessage($exception)
    {
        $message = $exception->getMessage();
        
        // Common error patterns and their explanations
        $errorPatterns = [
            '/Connection refused/' => 'Connection refused - The SMTP server is not accepting connections on this port',
            '/Connection timed out/' => 'Connection timeout - The server is not responding within the timeout period',
            '/Name or service not known/' => 'DNS resolution failed - The hostname cannot be resolved',
            '/Certificate verification failed/' => 'SSL certificate verification failed - Try disabling SSL verification',
            '/Authentication failed/' => 'SMTP authentication failed - Check username and password',
            '/Must issue a STARTTLS command first/' => 'TLS required - The server requires TLS encryption'
        ];
        
        foreach ($errorPatterns as $pattern => $explanation) {
            if (preg_match($pattern, $message)) {
                return $explanation . ' (Original error: ' . $message . ')';
            }
        }
        
        return $message;
    }
    
    /**
     * Get suggestions based on error
     */
    private function getSuggestions($exception)
    {
        $message = $exception->getMessage();
        $suggestions = [];
        
        if (strpos($message, 'Connection refused') !== false) {
            $suggestions[] = 'Check if the SMTP server is running and accessible';
            $suggestions[] = 'Verify the port number (common ports: 25, 465, 587)';
            $suggestions[] = 'Check firewall settings';
        }
        
        if (strpos($message, 'timeout') !== false) {
            $suggestions[] = 'Increase the timeout value';
            $suggestions[] = 'Check network connectivity';
            $suggestions[] = 'Verify the server is not overloaded';
        }
        
        if (strpos($message, 'Certificate') !== false) {
            $suggestions[] = 'Try disabling SSL certificate verification';
            $suggestions[] = 'Update SSL certificates';
            $suggestions[] = 'Use a different encryption method';
        }
        
        if (strpos($message, 'Authentication') !== false) {
            $suggestions[] = 'Verify username and password';
            $suggestions[] = 'Check if the account is locked or disabled';
            $suggestions[] = 'Try using app-specific passwords if available';
        }
        
        return $suggestions;
    }
    
    /**
     * Get common SMTP configurations for popular providers
     */
    public function getCommonConfigurations()
    {
        return [
            'gmail' => [
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'auth_required' => true
            ],
            'outlook' => [
                'host' => 'smtp-mail.outlook.com',
                'port' => 587,
                'encryption' => 'tls',
                'auth_required' => true
            ],
            'yahoo' => [
                'host' => 'smtp.mail.yahoo.com',
                'port' => 587,
                'encryption' => 'tls',
                'auth_required' => true
            ],
            'custom_ssl' => [
                'host' => 'your-smtp-server.com',
                'port' => 465,
                'encryption' => 'ssl',
                'auth_required' => true
            ],
            'custom_tls' => [
                'host' => 'your-smtp-server.com',
                'port' => 587,
                'encryption' => 'tls',
                'auth_required' => true
            ]
        ];
    }
}
