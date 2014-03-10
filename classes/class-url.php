<?php

/**
 * Class Dispatch_Url
 *
 * @property string $scheme
 * @property string $domain
 * @property string $path
 * @property string $front
 * @property bool $has_front
 * @property array $front_segments
 * @property array $segments
 * @property array $relative_segments
 * @property string $query
 * @property array $query_vars
 *
 */
class Dispatch_Url {

  /**
   * @var array
   */
  private $_url;

  /**
   * @var string
   */
  private $_domain;

  /**
   * @var string
   */
  private $_front_segment;

  /**
   * @var array
   */
  private $_segments = array();

  /**
   * @var array
   */
  private $_front_segments;

  /**
   * @var array
   */
  private $_relative_segments;

  /**
   * Initialize class upon loading.
   */
  function __construct( $url ) {
    $this->parse_url( $url );
  }

  /**
   * Returns true if it is a root URL
   *
   * Note: Ignores front segments
   */
  function is_root() {
    return 0 < count( $this->_relative_segments );
  }

  /**
   * Returns the list of segments in reverse
   *
   */
  function reversed_relative_segments() {
    return array_reverse( $this->_relative_segments );
  }

  /**
   * @param string
   */
  function parse_url( $url ) {
    $this->_url = parse_url( $url );
    preg_match( '#^https?://([^/]+)(/?.*)$#', home_url(), $matches );
    $this->_domain = $matches[1];
    $this->_front_segment = isset( $matches[2] ) ? $matches[2] : false;
  }

  /**
   * @return string
   */
  function scheme() {
    return is_ssl() ? 'https' : 'http';
  }

  /**
   * @return string
   */
  function path() {
    return $this->_url['path'];
  }

  /**
   * @return string
   */
  function domain() {
    return $this->_domain;
  }

  function front_segment() {
    return $this->_front_segment;
  }
  /**
   * @return array
   */
  function front_segments() {
    if ( ! isset( $this->_front_segments ) ) {
      if ( 1 <= strlen( $front = $this->front_segment() ) ) {
        $this->_front_segments = explode( '/', trim( $front, '/' ) );
      } else {
        $this->_front_segments = array();
      }
    }
    return $this->_front_segments;
  }

  /**
   * @return bool
   */
  function has_front() {
    return 0 < count( self::front_segments() );
  }

  /**
   * @return array
   */
  function relative_segments() {
    if ( ! isset( $this->_relative_segments ) ) {
      $this->_relative_segments = array_slice( self::segments(), count( self::front_segments() ) );
    }
    return $this->_relative_segments;
  }

  /**
   * @return array
   */
  function segments() {
    if ( ! isset( $this->_segments ) ) {
      $this->_segments = explode( '/', trim( $this->_url['path'], '/' ) );
    }
    return $this->_segments;
  }

  /**
   * @return array
   */
  function query_vars() {
    if ( empty( $this->_url['query_vars'] ) )
      self::query();
    return $this->_url['query_vars'];
  }

  /**
   * @return string
   */
  function query() {
    if ( empty( $this->_url['query'] ) ) {
      $this->_url['query'] = array();
      $this->_url['query_vars'] = false;
    } else {
      parse_str( $this->_url['query'], $this->_url['query_vars'] );
    }
    return $this->_url['query'];
  }

  /**
   * @param $property_name
   * @return mixed
   */
  function __get( $property_name ) {
    $value = null;
    if ( method_exists( $this, $method_name = $property_name ) ) {
      $value = call_user_func( array( $this, $method_name ) );
    } else if ( method_exists( $this, $property_name ) ) {
      $value = call_user_func( array( $this, $property_name ) );
    } else {
      trigger_error( sprintf( 'No method $url->%s() or \$url->%s().', $method_name, $property_name ) );
    }
    return $value;
   }

}

