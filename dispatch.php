<?php
/**
 * Plugin Name: URL Routing for WordPress
 */

require( __DIR__ . '/helpers/class-routing.php');
require( __DIR__ . '/classes/class-base.php');
require( __DIR__ . '/classes/class-url.php' );
require( __DIR__ . '/classes/class-parser.php');
require( __DIR__ . '/classes/class-request.php');
require( __DIR__ . '/classes/class-branch.php');

/**
 * Class Dispatch
 *
 * @mixin Dispatch_Routing
 */
class Dispatch {
  /**
   * @var array
   */
  private static $_inbound_url;

  /**
   * @var array
   */
  private static $_helpers = array();


  /**
   * Return an object that can be queried for properties of inbound URL.
   *
   * @return Dispatch_Url
   */
  static function inbound_url() {
    if ( ! isset( self::$_inbound_url ) ) {
      self::$_inbound_url = $inbound_url = new Dispatch_Url( $_SERVER['REQUEST_URI'] );
      foreach( $inbound_url->segments as $index => $segment ) {
        $inbound_url->segments[$index] = sanitize_key( $segment );
      }
    }
    return self::$_inbound_url;
  }

  /**
  * Register a Helper Class for the Main class.
  *
  * @param string $class_name
  */
 static function register_helper( $class_name ) {
   self::$_helpers[] = $class_name;
 }

  /**
  * Register a Helper Class for the Main class.
  *
   * @param string $method_name
   * @param bool|string $class_name
  */
   static function register_helper_method( $method_name, $class_name = false ) {
     if ( ! $class_name ) {
       $class_name = get_called_class();
    }
     self::$_helpers[$method_name] = $class_name;
   }

  /**
  * Delegate calls to other "helper" classes.
  *
  * @param string $method_name
  * @param array $args
  *
  * @return mixed
  *
  * @throws Exception
  */
  static function __callStatic( $method_name, $args ) {
    static $found = false;
    if ( ! $found ) {
      $found = array();
      foreach( self::$_helpers as $this_method_name => $this_class_name ) {
        if ( ! is_numeric( $this_method_name ) ) {
          $found[$this_method_name] = $this_class_name;
          unset( self::$_helpers[$this_method_name] );
        }
      }
    }
    if ( isset( $found[$method_name] ) ) {
      $value = call_user_func_array( array( $found[$method_name], $method_name ), $args );
    } else {
      foreach( self::$_helpers as $index => $class_name ) {
        if ( method_exists( $class_name, $method_name ) ) {
          $value = call_user_func_array( array( $class_name, $method_name ), $args );
          $found[$method_name] = $class_name;
          break;
        }
      }
      if ( ! isset( $found[$method_name] ) ) {
        $message = __( 'ERROR: Neither %s nor any of it\'s registered helper classes have the method %s().', 'dispatch' );
        trigger_error( sprintf( $message, get_called_class(), $method_name ), E_USER_WARNING );
        $value = null;
      }
    }
    return $value;
  }

}
