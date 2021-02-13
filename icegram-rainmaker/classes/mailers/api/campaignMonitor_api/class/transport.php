<?php

defined( 'Rm_REST_GET' ) or define( 'Rm_REST_GET', 'GET' );
defined( 'Rm_REST_POST' ) or define( 'Rm_REST_POST', 'POST' );
defined( 'Rm_REST_PUT' ) or define( 'Rm_REST_PUT', 'PUT' );
defined( 'Rm_REST_DELETE' ) or define( 'Rm_REST_DELETE', 'DELETE' );
if ( false === defined( 'Rm_REST_SOCKET_TIMEOUT' ) ) {
	define( 'Rm_REST_SOCKET_TIMEOUT', 10 );
}
if ( false === defined( 'Rm_REST_CALL_TIMEOUT' ) ) {
	define( 'Rm_REST_CALL_TIMEOUT', 10 );
}

if ( ! function_exists( "Rm_REST_TRANSPORT_get_available" ) ) {
	function Rm_REST_TRANSPORT_get_available( $requires_ssl, $log ) {
		return new Rm_REST_CurlTransport( $log );
	}
}

if ( ! function_exists( "Rm_REST_TRANSPORT_can_use_raw_socket" ) ) {
	function Rm_REST_TRANSPORT_can_use_raw_socket( $requires_ssl ) {
		if ( function_exists( 'fsockopen' ) ) {
			if ( $requires_ssl ) {
				return extension_loaded( 'openssl' );
			}

			return true;
		}

		return false;
	}
}

if ( ! class_exists( 'Rm_REST_BaseTransport' ) ) {
	class Rm_REST_BaseTransport {

		var $_log;

		function __construct( $log ) {
			$this->_log = $log;
		}

		function split_and_inflate( $response, $may_be_compressed ) {
			$ra = explode( "\r\n\r\n", $response );

			$result  = array_pop( $ra );
			$headers = array_pop( $ra );

			if ( $may_be_compressed && preg_match( '/^Content-Encoding:\s+gzip\s+$/im', $headers ) ) {
				$original_length = strlen( $response );
				$result          = gzinflate( substr( $result, 10, - 8 ) );

				$this->_log->log_message( 'Inflated gzipped response: ' . $original_length . ' bytes ->' .
				                          strlen( $result ) . ' bytes', get_class(), Rm_REST_LOG_VERBOSE );
			}

			return array( $headers, $result );
		}

	}
}
/**
 * Provide HTTP request functionality via cURL extensions
 *
 * @author tobyb
 * @since 1.0
 */
if ( ! class_exists( 'Rm_REST_CurlTransport' ) ) {
	class Rm_REST_CurlTransport extends Rm_REST_BaseTransport {

		var $_curl_zlib;

		function __construct( $log ) {
			parent::__construct( $log );

			$curl_version     = curl_version();
			$this->_curl_zlib = isset( $curl_version['libz_version'] );
		}

		/**
		 * @return string The type of transport used
		 */
		function get_type() {
			return 'cURL';
		}

		function make_call( $call_options ) {
			$url                     = $call_options['route'];
			$headers                 = array();
			$args                    = array();
			$headers['Content-Type'] = $call_options['contentType'];
			if ( array_key_exists( 'authdetails', $call_options ) && isset( $call_options['authdetails'] ) ) {
				if ( array_key_exists( 'username', $call_options['authdetails'] ) && array_key_exists( 'password', $call_options['authdetails'] ) ) {
					# Authenticating using basic auth for retrieving user's API key.
					$auth                     = base64_encode( $call_options['authdetails']['username'] . ':' . $call_options['authdetails']['password'] );
					$headers['Authorization'] = 'Basic ' . $auth;
				} elseif ( array_key_exists( 'access_token', $call_options['authdetails'] ) ) {
					# Authenticating using OAuth.
					$access_token = $call_options['authdetails']['access_token'];
					// $headers[] = 'Authorization: Bearer '.$access_token;
					$headers['Authorization'] = 'Bearer ' . $access_token;
				} elseif ( array_key_exists( 'api_key', $call_options['authdetails'] ) ) {
					# Authenticating using an API key.
					$api_key                  = $call_options['authdetails']['api_key'];
					$auth                     = $api_key . ':nopass';
					$headers['Authorization'] = 'Basic ' . base64_encode( $auth );
				}
			}
			switch ( $call_options['method'] ) {
				case Rm_REST_PUT:
					$headers[] = 'Content-Length: ' . strlen( $call_options['data'] );
					$args      = $call_options['data'];
					break;
				case Rm_REST_POST:
					$args = $call_options['data'];
					break;
			}
			$response = wp_remote_get( $url,
				array(
					'method'  => $call_options['method'],
					'headers' => $headers,
					'timeout' => Rm_REST_CALL_TIMEOUT,
					'body'    => $args,

				) );

			if ( ! is_wp_error( $response ) ) {
				$code    = wp_remote_retrieve_response_code( $response );
				$message = wp_remote_retrieve_response_message( $response );
				if ( $code == 200 && $message == 'OK' ) {
					$body   = wp_remote_retrieve_body( $response );
					$result = array(
						'code'     => $code,
						'response' => $body
					);

					return $result;
				}
			} elseif ( ! is_wp_error( $response ) ) {
				$code  = wp_remote_retrieve_response_code( $response );
				$error = wp_remote_retrieve_response_message( $response );
			}
		}
	}
}

if ( ! class_exists( 'Rm_REST_SocketWrapper' ) ) {
	class Rm_REST_SocketWrapper {
		var $socket;

		function open( $domain, $port ) {
			$this->socket = fsockopen( $domain, $port, $errno, $errstr, Rm_REST_SOCKET_TIMEOUT );

			if ( ! $this->socket ) {
				die( 'Error making request with ' . $errno . ': ' . $errstr );

				return false;
			} elseif ( function_exists( 'stream_set_timeout' ) ) {
				stream_set_timeout( $this->socket, Rm_REST_SOCKET_TIMEOUT );
			}

			return true;
		}

		function write( $data ) {
			fwrite( $this->socket, $data );
		}

		function read() {
			ob_start();
			fpassthru( $this->socket );

			return ob_get_clean();
		}

		function close() {
			fclose( $this->socket );
		}
	}
}

if ( ! class_exists( 'Rm_REST_SocketTransport' ) ) {
	class Rm_REST_SocketTransport extends Rm_REST_BaseTransport {

		var $_socket_wrapper;

		function __construct( $log, $socket_wrapper = null ) {
			parent::__construct( $log );

			if ( is_null( $socket_wrapper ) ) {
				$socket_wrapper = new Rm_REST_SocketWrapper();
			}

			$this->_socket_wrapper = $socket_wrapper;
		}

		/**
		 * @return string The type of transport used
		 */
		function get_type() {
			return 'Socket';
		}

		function make_call( $call_options ) {
			$start_host = strpos( $call_options['route'], $call_options['host'] );
			$host_len   = strlen( $call_options['host'] );

			$domain   = substr( $call_options['route'], $start_host, $host_len );
			$host     = $domain;
			$path     = substr( $call_options['route'], $start_host + $host_len );
			$protocol = substr( $call_options['route'], 0, $start_host );
			$port     = 80;

			$this->_log->log_message( 'Creating socket to ' . $domain . ' over ' . $protocol . ' for request to ' . $path,
				get_class( $this ), Rm_REST_LOG_VERBOSE );

			if ( $protocol === 'https://' ) {
				$domain = 'ssl://' . $domain;
				$port   = 443;
			}

			if ( $this->_socket_wrapper->open( $domain, $port ) ) {
				$inflate_response = function_exists( 'gzinflate' );

				$request = $this->_build_request( $call_options, $host, $path, $inflate_response );
				$this->_log->log_message( 'Sending <pre>' . $request . '</pre> down the socket',
					get_class( $this ), Rm_REST_LOG_VERBOSE );

				$this->_socket_wrapper->write( $request );
				$response = $this->_socket_wrapper->read();
				$this->_socket_wrapper->close();

				$this->_log->log_message( 'API Call Info for ' . $call_options['method'] . ' ' .
				                          $call_options['route'] . ': ' . strlen( $request ) .
				                          ' bytes uploaded. ' . strlen( $response ) . ' bytes downloaded',
					get_class( $this ), Rm_REST_LOG_VERBOSE );

				list( $headers, $result ) = $this->split_and_inflate( $response, $inflate_response );

				$this->_log->log_message( 'Received headers <pre>' . $headers . '</pre>',
					get_class( $this ), Rm_REST_LOG_VERBOSE );

				return array(
					'code'     => $this->_get_status_code( $headers ),
					'response' => trim( $result )
				);
			}
		}

		function _get_status_code( $headers ) {
			if ( preg_match( '%^\s*HTTP/1\.1 (?P<code>\d{3})%', $headers, $regs ) ) {
				$this->_log->log_message( 'Got HTTP Status Code: ' . $regs['code'],
					get_class( $this ), Rm_REST_LOG_VERBOSE );

				return $regs['code'];
			}

			$this->_log->log_message( 'Failed to get HTTP status code from request headers <pre>' . $headers . '</pre>',
				get_class( $this ), Rm_REST_LOG_ERROR );
			trigger_error( 'Failed to get HTTP status code from request', E_USER_ERROR );
		}

		function _build_request( $call_options, $host, $path, $accept_gzip ) {
			$request_auth_details = '';

			if ( array_key_exists( 'authdetails', $call_options ) ) {
				if ( array_key_exists( 'username', $call_options['authdetails'] ) &&
				     array_key_exists( 'password', $call_options['authdetails'] ) ) {
					# Authenticating using basic auth for retrieving user's API key.
					$request_auth_details .= 'Authorization: Basic ' . base64_encode( $call_options['authdetails']['username'] . ':' . $call_options['authdetails']['password'] ) . "\n";
				} elseif ( array_key_exists( 'access_token', $call_options['authdetails'] ) ) {
					# Authenticating using OAuth.
					$access_token         = $call_options['authdetails']['access_token'];
					$request_auth_details .= 'Authorization: Bearer ' . $access_token . "\n";
				} elseif ( array_key_exists( 'api_key', $call_options['authdetails'] ) ) {
					# Authenticating using an API key.
					$api_key              = $call_options['authdetails']['api_key'];
					$request_auth_details .= 'Authorization: Basic ' . base64_encode( $api_key . ':nopass' ) . "\n";
				}
			}

			$request =
				$call_options['method'] . ' ' . $path . " HTTP/1.1\n" .
				'Host: ' . $host . "\n" .
				$request_auth_details .
				'User-Agent: ' . $call_options['userAgent'] . "\n" .
				"Connection: Close\n" .
				'Content-Type: ' . $call_options['contentType'] . "\n";

			if ( $accept_gzip ) {
				$request .=
					"Accept-Encoding: gzip\n";
			}

			if ( isset( $call_options['data'] ) ) {
				$request .=
					'Content-Length: ' . strlen( $call_options['data'] ) . "\n\n" .
					$call_options['data'];
			}

			return $request . "\n\n";
		}
	}
}